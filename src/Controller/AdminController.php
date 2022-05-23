<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Category;
use App\Form\ProductType;
use App\Entity\Reservation;
use App\Service\EcomToolbox;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Security('is_granted("ROLE_ADMIN")')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_backoffice')]
    public function adminBackoffice(ManagerRegistry $doctrine): Response
    {
        //Cette méthode nous renvoie sur la page twig Backoffice Administrateur de notre application, laquelle nous présente la liste de nos Products liés à une image.

        //Afin de récupérer la liste de nos Products, nous avons besoin de l'Entity Manager ainsi que des Repository de nos deux Entities
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        //Nous transmettons la liste de nos Categories via le Repository
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons la liste de tous nos products, du plus ancien au plus récent:
        $products = $productRepository->findAll();
        //Nos Products étant récupérés, nous les envoyons vers notre fichier twig
        return $this->render('admin/admin_backoffice_pic.html.twig', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    #[Route('/pictures', name: 'admin_picture_backoffice')]
    public function adminPictureBackoffice(ManagerRegistry $doctrine): Response
    {
        //Cette méthode nous renvoie sur la page twig Backoffice Administrateur de notre application, laquelle nous présente la liste de nos Tags et de nos Products.

        //Afin de récupérer la liste de nos Tags et Products, nous avons besoin de l'Entity Manager ainsi que des Repository de nos deux Entities
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        $tagRepository = $entityManager->getRepository(Tag::class);
        //Nous transmettons la liste de nos Categories via le Repository
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons la liste de tous nos products et tags, du plus ancien au plus récent:
        $products = $productRepository->findAll();
        $tags = $tagRepository->findAll();
        //Nos Products et Tags étant récupérés, nous les envoyons vers notre fichier twig
        return $this->render('admin/admin_backoffice.html.twig', [
            'categories' => $categories,
            'products' => $products,
            'tags' => $tags,
        ]);
    }

    #[Route('/order-display', name: 'order_display_admin')]
    public function orderDisplayAdmin(ManagerRegistry $doctrine): Response
    {
        //Cette méthode nous affiche la liste des commandes/Order en cours et validées

        //Afin de pouvoir récupérer les Order de notre BDD, nous avons de l'Entity Manager ainsi que du Repository de Order
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        //Nous récupérons nos Category afin de pouvoir les afficher sur le header
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons nos Order en mode panier et validées via deux requêtes successives
        $activeOrders = $orderRepository->findBy(['status' => 'panier'], ['id' => 'DESC']);
        $archivedOrders = $orderRepository->findBy(['status' => 'validee'], ['id' => 'DESC']);
        //Nous transmettons la liste de nos commandes à notre page twig dédiée
        return $this->render('admin/order_display_admin.html.twig', [
            'categories' => $categories,
            'activeOrders' => $activeOrders,
            'archivedOrders' => $archivedOrders,
        ]);
    }

    #[Route('/validate/{orderId}', name: 'order_validate_admin')]
    public function validateOrderAdmin(int $orderId, ManagerRegistry $doctrine, EcomToolbox $toolbox): Response
    {
        //Cette méthode a pour objectif de changer le statut d'une commande en cours de "Panier" à "Validée"

        //Afin de récupérer l'Order que nous voulons valider, nous avons besoin de l'Entity Manager ainsi que du Repository de Order
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        //Si l'Order en question n'est pas retrouvé ou n'est pas en mode panier, nous retournons à notre tableau de bord commandes
        $order = $orderRepository->find($orderId);
        //La seconde condition n'est abordée que si la première n'est pas validée. Ainsi, nous ferons appel à la méthode getStatus UNIQUEMENT si la première condition (!$order) n'est pas validée, ce qui nous préserve d'un risque d'appel de méthode sur null
        if(!$order || $order->getStatus() != "panier"){
            //Nous ajoutons un Flashbag
            $toolbox->generateFlashbag('Cette commande ne peut pas être validée.', 'Commande', 'red');
            return $this->redirectToRoute('order_display_admin');
        }
        //Si nous possédons bien notre order en mode "panier", il nous suffit de changer son statue à "validee" avant de persister le résultat
        $order->setStatus("validee");
        $entityManager->persist($order);
        $entityManager->flush();
        //Nous ajoutons un Flashbag
        $toolbox->generateFlashbag('Votre commande a bien été validée.', 'Commande', 'green');
        //Nous retournons à notre tableau de bord Commandes
        return $this->redirectToRoute('order_display_admin');
    }

    #[Route('/delete/{orderId}', name: 'order_delete_admin')]
    public function deleteOrderAdmin(int $orderId, ManagerRegistry $doctrine, EcomToolbox $toolbox): Response
    {
        //Cette méthode supprime une Entity Order en mode panier de notre base de données, ainsi que toutes les Reservations associées, selon l'ID de la commande fournie dans l'URL

        //Afin de pouvoir récupérer notre Order à supprimer, nous avons besoin de l'Entity Manager ainsi que du Repository de Order
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        //Nous récupérons la commande en question, si celle-ci n'est pas trouvée OU que la commande n'est pas en mode "panier", nous retournons au tableau de bord
        $order = $orderRepository->find($orderId);
        if(!$order || $order->getStatus() != "panier"){
            //Nous ajoutons un Flashbag
            $toolbox->generateFlashbag('Cette commande ne peut pas être annulée.', 'Commande', 'red');
            return $this->redirectToRoute('order_display_admin');
        }
        //Si notre commande existe et est en mode Panier, nous itérons sa liste de Reservations liées que nous supprimons une par une
        foreach($order->getReservations() as $reservation){
            $reservation->returnStock();
            $entityManager->remove($reservation);
        }
        //Une fois que toutes les Reservations ont été supprimées, nous passsons à la requête de suppression de notre commande
        $entityManager->remove($order);
        //Nous appliquons les requêtes
        $entityManager->flush();
        //Nous ajoutons un Flashbag
        $toolbox->generateFlashbag('Cette commande a été annulée avec succès.', 'Commande', 'green');
        //Nous retournons au tableau de bord Commandes
        return $this->redirectToRoute('order_display_admin');
    }

    #[Route('/reservation/delete/{reservationId}', name: 'reservation_delete_admin')]
    public function deleteReservationAdmin(int $reservationId, ManagerRegistry $doctrine, EcomToolbox $toolbox): Response
    {
        //Cette méthode a pour objectif de supprimer de notre base de données une Reservation d'une commande en cours, identifiée via son ID fourni dans notre URL

        //Afin de récupérer la Reservation que nous voulons supprimer, nous avons besoin de l'Entity Manager ainsi que du Repository de Reservation
        $entityManager = $doctrine->getManager();
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        //Si la Reservation en question n'est pas retrouvée ou que sa commande n'est pas en mode panier, nous retournons à notre tableau de bord commandes
        $reservation = $reservationRepository->find($reservationId);
        //La seconde condition n'est abordée que si la première n'est pas validée. Ainsi, nous ferons appel à la méthode getStatus UNIQUEMENT si la première condition (!$reservation) n'est pas validée, ce qui nous préserve d'un risque d'appel de méthode sur null
        if(!$reservation || $reservation->getOrder()->getStatus() != "panier"){
            //Nous ajoutons un Flashbag
            $toolbox->generateFlashbag('Cette réservation ne peut pas être supprimée.', 'Réservation', 'red');
            return $this->redirectToRoute('order_display_admin');
        }
        //Si nous possédons notre Reservation d'une commande en mode panier, nous procédons à sa suppression
        //On commence par restituer au stock de notre référence Product associée la quantity retenue par notre Reservation
        $product = $reservation->getProduct();
        $reservation->returnStock();
        $entityManager->persist($product);
        //Nous récupérerons notre variable $order avant de détacher notre réservation de sa commande
        $order = $reservation->getOrder();
        $order->removeReservation($reservation); //retire la reservation de la commande
        //Nous vérifions si notre commande est vide de réservations. Si c'est bien le cas, nous procédons à la suppression de la commande/Order également.
        if($order->getReservations()->isEmpty()){
            $entityManager->remove($order);
        }
        //Requête de suppression de la Reservation
        $entityManager->remove($reservation);
        $entityManager->flush();
        //Nous ajoutons un Flashbag
        $toolbox->generateFlashbag('Cette réservation a été supprimée avec succès.', 'Réservation', 'green');
        //Nous retournons à notre tableau de bord Commandes
        return $this->redirectToRoute('order_display_admin');
    }

    #[Route('/categories/generate', name: 'category_generate')]
    public function generateCategories(ManagerRegistry $doctrine, EcomToolbox $toolbox): Response
    {
        //Cette méthode a pour objectif de générer automatiquement une série de catégories (Chaise, Bureau, Lit, Canapé, Armoire, et Autre) pour notre application, après avoir vidé la table MySQL au préalable.

        //Afin de pouvoir communiquer avec notre base de donner et notre table Category, nous allons avoir besoin de l'Entity Manager ainsi que du Repository de Category
        $entityManager = $doctrine->getManager();
        $categoryRepository = $entityManager->getRepository(Category::class);
        $productRepository = $entityManager->getRepository(Product::class);
        //Nous commençons par vider notre table: nous récupérons tous nos éléments avant de les supprimer un par un
        $categories = $categoryRepository->findAll(); //On récupère toutes les Catégories
        foreach($categories as $category){
            //Nous parcourons le tableau de Category afin de pouvoir effectuer des requêtes de retrait sur chaque Category
            //Afin d'éviter les violations de clef étrangère, nous devons être sûr qu'une catégorie n'est liée à aucun produit avant de procéder à leur suppression
            foreach($category->getProducts() as $product){
                $category->removeProduct($product); //Après cette boucle foreach, le catalogue de Products lié à notre Category sera vide
            }
            $entityManager->remove($category);
        }
        //Maintenant que la table est vide, il nous suffira de la remplir avec les éléments désirés
        $categoryNames = ["Chaise", "Bureau", "Lit", "Canape", "Armoire", "Autre"];
        $description = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam fringilla semper ligula vestibulum mattis. Ut in aliquam sapien, in fermentum turpis. Morbi vel mollis est. Nulla nec consequat nisi. Pellentesque auctor posuere enim in egestas. Donec suscipit augue eget pulvinar convallis.";
        $categories = [];
        foreach($categoryNames as $categoryName){
            //Création et persistance de chaque Category
            $category = new Category;
            $category->setName($categoryName);
            $category->setDescription($description);
            $entityManager->persist($category);
            $categories += [$category->getName() => $category];
        }
        //Nous récupérons la liste de nos Products, nous vérifions les nos, et nous les attachons en conséquence à nos nouvelles catégroies
        $products = $productRepository->findAll();
        foreach($products as $product){
            $productName = strtolower($product->getName()); //strtolower -> en minuscule pour éviter les soucis de casse
            //switch(true) nous permet de vérifier l'exactitude (un rendu de la valeur "true") de chacune des propositions ici. Si le string $name de nos products contient un extrait mentionné (chaise, bureau, etc), le Product est automatiquement lié à la Category correspondante
            switch(true){
                case str_contains($productName, "chaise"):
                    $product->setCategory($categories["Chaise"]);
                    break;
                case str_contains($productName, "bureau"):
                    $product->setCategory($categories["Bureau"]);
                    break;
                case str_contains($productName, "lit"):
                    $product->setCategory($categories["Lit"]);
                    break;
                case str_contains($productName, "canape"):
                    $product->setCategory($categories["Canape"]);
                    break;
                case str_contains($productName, "canapé"): //canapé avec accent
                    $product->setCategory($categories["Canape"]);
                    break;
                case str_contains($productName, "armoire"):
                    $product->setCategory($categories["Armoire"]);
                    break;
                default:
                    $product->setCategory($categories["Autre"]);
            }
            $entityManager->persist($product); //On met la Category du Product à jour
        }
        $entityManager->flush();
        //Nous retournons au Backoffice Administrateur
        $toolbox->generateFlashbag('Les Catégories ont bien été regénérées', 'Catégories', 'green');
        return $this->redirectToRoute("admin_backoffice");
    }

    #[Route('/product/create', name: 'product_create')]
    public function createProduct(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger, EcomToolbox $toolbox): Response
    {
        //Cette méthode permet la création et la persistance d'une instance d'Entity Product selon les informations entrées dans un formulaire de création par l'Utilisateur

        //Afin de pouvoir envoyer nos données vers notre BDD, nous avons besoin de l'Entity Manager
        $entityManager = $doctrine->getManager();
        //Nous transmettons la liste de nos Categories via le Repository
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous instancions un objet Product que nous lions à un formulaire ProductType
        $product = new Product;
        $productForm = $this->createForm(ProductType::class, $product);
        // Nous retirons la case à cocher "delete image"
        $productForm->remove('deleteImage');
        //Nous appliquons l'objet Request sur notre formulaire
        $productForm->handleRequest($request);
        //Si notre formulaire est rempli et valide, nous l'importons
        if($productForm->isSubmitted() && $productForm->isValid()){
            if($product->getPrice() >= 1){ //Prix minimum de 1€ pour effectuer la persistance
                //Nous récupérons la valeur du champ img
                $imgFile = $productForm->get('imageFile')->getData();
                //Si le champ 'imageFile' est vide, il est inutile d'essayer de le mettre en ligne
                if($imgFile){
                    $toolbox->generateFlashbag('Fichier image pris en charge', 'Catégories', 'green');
                    $originalFilename = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                    //Nous préparons le nom de fichier de manière à l'intégrer sans risque dans une URL. Nous devinons également automatiquement l'extension pour des raisons de sécurité
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imgFile->guessExtension();
                    //On déplace le fichier
                    try{
                        $imgFile->move(
                            $this->getParameter('img_directory'),
                            $newFilename
                        );
                        //On renseigne le nom de notre nouveau fichier image
                        $product->setPicAddress($newFilename); 
                        $toolbox->generateFlashbag('Fichier image déplacé');
                    } catch (FileException $e) {
                        //On capture l'erreur si un problème survient
                        $toolbox->generateFlashbag('Echec du déplacement');
                    }
                }
                $entityManager->persist($product);
                $entityManager->flush();
            }
            //Nous retournons au backoffice Administrateur
            return $this->redirectToRoute('admin_backoffice');
        }
        //Si notre formulaire n'est pas rempli, nous le présentons à l'Utilisateur
        return $this->render('index/dataform.html.twig', [
            'categories' => $categories,
            'formName' => 'Création de Product',
            'dataForm' => $productForm->createView(),
        ]);
    }

    #[Route('/product/update/{productId}', name: 'product_update')]
    public function updateProduct(int $productId, Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger, EcomToolbox $toolbox): Response
    {
        //Cette méthode permet de modifier les valeurs d'une entrée persistée de Product dont l'ID a été renseignée via l'URL

        //Afin de pouvoir rechercher l'élément Product que nous désirons supprimer, nous avons besoin de l'Entity Manager ainsi que du Repository pertinent
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        //Nous transmettons la liste de nos Categories via le Repository
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous recherchons le Product en question:
        $product = $productRepository->find($productId);
        //Si la recherche n'aboutit pas, nous retournons au Backoffice Administrateur
        if(!$product){
            return $this->redirectToRoute('admin_backoffice');
        }
        $productForm = $this->createForm(ProductType::class, $product);
        //Nous appliquons l'objet Request sur notre formulaire
        $productForm->handleRequest($request);
        //Si notre formulaire est rempli et valide, nous l'importons
        if($productForm->isSubmitted() && $productForm->isValid()){
            if($product->getPrice() >= 1){ //Prix minimum de 1€ pour effectuer la persistance
                //Nous récupérons la valeur des champs img et checkbox
                $imgFile = $productForm->get('imageFile')->getData();
                $productCheckbox = $productForm->get('deleteImage')->getData();
                //Si le champ 'imageFile' est vide, il est inutile d'essayer de le mettre en ligne
                if($imgFile && !$productCheckbox){
                    $toolbox->generateFlashbag('Fichier image pris en charge', 'Catégories', 'green');
                    $originalFilename = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                    //Nous préparons le nom de fichier de manière à l'intégrer sans risque dans une URL. Nous devinons également automatiquement l'extension pour des raisons de sécurité
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imgFile->guessExtension();
                    //On déplace le fichier
                    try{
                        $imgFile->move(
                            $this->getParameter('img_directory'),
                            $newFilename
                        );
                        //On renseigne le nom de notre nouveau fichier image
                        $product->setPicAddress($newFilename); 
                        $toolbox->generateFlashbag('Fichier image déplacé');
                    } catch (FileException $e) {
                        //On capture l'erreur si un problème survient
                        $toolbox->generateFlashbag('Echec du déplacement');
                    }
                } elseif($productCheckbox) {
                    // si le productCheckbox est ("deleteImage") est coché, nous procédons à la supression de l'image lié à notre Product avant de vider la valeur de son champs PicAdress
                    // Nous utilisons pour la supression une nouvelle classe, nommé FileSystem
                    $filesystem = new Filesystem;
                    // Nous procédons à la supression de l'image:
                    try{
                        $filesystem->remove($this->getParameter('img_directory') . '/' .
                        $product->getpicFileName());
                        $product->setPicAdress(null);
                        $entityManager->persist($product);
                        $entityManager->flush();
                    } catch (FileException $e) {

                        $toolbox->generateFlashbag('Echec de la supression');

                    } finally{
                        return $this->redirectToRoute('admin_picture_backoffice');
                    }


                }
                $entityManager->persist($product);
                $entityManager->flush();
            }
            //Nous retournons au backoffice Administrateur
            return $this->redirectToRoute('admin_backoffice');
        }
        //Si notre formulaire n'est pas rempli, nous le présentons à l'Utilisateur
        return $this->render('index/dataform.html.twig', [
            'categories' => $categories,
            'formName' => 'Création de Product',
            'dataForm' => $productForm->createView(),
        ]);
    }

    #[Route('/product/delete/{productId}', name: 'product_delete')]
    public function deleteProduct(int $productId, ManagerRegistry $doctrine): Response
    {
        //Cette méthode nous permet de supprimer une entrée de Product de notre base de données dont l'ID nous a été renseignée via notre paramètre de route

        //Afin de pouvoir rechercher l'élément Product que nous désirons supprimer, nous avons besoin de l'Entity Manager ainsi que du Repository pertinent
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        //Nous recherchons le Product en question:
        $product = $productRepository->find($productId);
        //Si la recherche n'aboutit pas, nous retournons au Backoffice Administrateur
        if(!$product){
            return $this->redirectToRoute('admin_backoffice');
        }
        //Si nous avons récupéré notre Product, nous procédons à sa suppression avant de revenir vers notre Backoffice
        //Avant de supprimer notre Product, nous devons au préalable détacher tous les Tags associés
        if(!empty($product->getTags())){
            foreach($product->getTags() as $tag){
                $product->removeTag($tag);
            }
            $entityManager->persist($product);
            $entityManager->flush();
        }
        //On supprime le Product
        $entityManager->remove($product);
        $entityManager->flush();
        return $this->redirectToRoute('admin_backoffice');
    }

    #[Route('/tag/create', name: 'tag_create')]
    public function createTags(Request $request, ManagerRegistry $doctrine): Response
    {
        //Cette méthode nous permet de créer plusieurs tags en un seul formulaire développé au sein de notre méthode

        //Afin de pouvoir envoyer les Tags vers notre base de données, nous avons besoin de l'Entity Manager
        $entityManager = $doctrine->getManager();
        //Nous récupérons le Repository de Tag pour vérifier l'absence de doublon
        $tagRepository = $entityManager->getRepository(Tag::class);
        //Nous créons notre formulaire champ par champ
        $tagsForm = $this->createFormBuilder()
            ->add('tag1', TextType::class, [
                'label' => 'Tag #1',
                'required' => false, //Champ non obligatoire
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('tag2', TextType::class, [
                'label' => 'Tag #2',
                'required' => false, //Champ non obligatoire
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('tag3', TextType::class, [
                'label' => 'Tag #3',
                'required' => false, //Champ non obligatoire
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('tag4', TextType::class, [
                'label' => 'Tag #4',
                'required' => false, //Champ non obligatoire
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('tag5', TextType::class, [
                'label' => 'Tag #5',
                'required' => false, //Champ non obligatoire
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => [
                    'class' => 'w3-button w3-black w3-margin-bottom',
                    'style' => 'margin-top: 10px',
                ]
            ])
            ->getForm()
        ;
        //Nous appliquons la Request sur notre formulaire
        $tagsForm->handleRequest($request);
        //Si le formulaire est rempli et valide
        if($tagsForm->isSubmitted() && $tagsForm->isValid()){
            //On récupère les informations de notre formulaire
            //La méthode getData() rend un tableau associatif qui possède les valeurs de chaque champ du formulaire, et donc nos cinq tags
            $data = $tagsForm->getData();
            for($i=1;$i<6;$i++){
                if(!empty($data['tag' . $i])){ //Si le champ itéré est rempli
                    $persistBool = true; //Allons-nous persister notre Tag?
                    for($j=1;$j<$i;$j++){
                        //Une boucle vérifie les éléments inférieurs à $i dans notre tableau
                        //Si un élément identique est trouvé, nous ne passons pas à l'étape suivante de la mise en persistance
                        if($data['tag' . $i] === $data['tag' . $j]){
                            $persistBool = false;
                        }
                    }
                    if($persistBool){
                        //Vérification d'absence de duplicata
                        $tagName = $data['tag' . $i]; //On récupère la valeur du champ
                        $duplicataTag = $tagRepository->findOneBy(['name' => $tagName]);
                        if(!$duplicataTag){ //Si le tag n'est pas présent dans la BDD
                            $tag = new Tag; //On instancie un nouvel objet Tag
                            $tag->setName($tagName); //On renseigne le Tag
                            $entityManager->persist($tag); //On le persiste
                        }
                    }
                }
            }
            $entityManager->flush(); //On applique les demandes de persistance
            return $this->redirectToRoute('admin_backoffice');
        }
        //Si le formulaire n'est pas validé, nous le présentons à l'utilisateur
        return $this->render('index/dataform.html.twig', [
            'formName' => 'Création de tags',
            'dataForm' => $tagsForm->createView(),
        ]);
    }

    #[Route('/tag/delete/{tagId}', name: 'tag_delete')]
    public function deleteTag(int $tagId, ManagerRegistry $doctrine): Response
    {
        //Cette méthode permet de supprimer une instance d'Entity Tag dont l'ID a été renseignée via notre base de données

        //Afin de pouvoir récupérer le Tag à supprimer de notre base de données, nous avons besoin de l'Entity Manager ainsi que du Repository de Tag
        $entityManager = $doctrine->getManager();
        $tagRepository = $entityManager->getRepository(Tag::class);
        //Nous récupérons le Tag indiqué via le paramètre de route
        $tag = $tagRepository->find($tagId);
        //Si le tag n'est pas trouvé, nous retournons au backoffice Admin
        if(!$tag){
            return $this->redirectToRoute('admin_backoffice');
        }
        //Si nous avons bien récupéré notre Tag, nous procédons à sa suppression
        $entityManager->remove($tag);
        $entityManager->flush();
        //Après la suppression, nous retournons au backoffice Admin
        return $this->redirectToRoute('admin_backoffice');
    }
}
