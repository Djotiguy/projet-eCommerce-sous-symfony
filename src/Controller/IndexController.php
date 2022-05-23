<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Reservation;
use App\Service\EcomToolbox;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(ManagerRegistry $doctrine, EcomToolbox $toolbox): Response
    {
        //Nous présentons dans notre index() une liste d'Entity Product présentées dans une grille d'élément par rangées de trois.

        //Nous récupérons la liste de nos Products via notre BDD via l'Entity Manager et le Repository de Product
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        //Nous transmettons la liste de nos Categories via le Repository
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons la liste de nos Products
        $products = $productRepository->findAll();
        //Nous pouvons mélanger l'ordre de nos Products
        shuffle($products);
        //On crée une pseudo-catégorie pour notre section index
        $pseudoCategory = [
            'name' => 'Symfony eCommerce',
            'description' => 'Bienvenue sur la page d\'accueil de notre boutique !', 
        ];

        //Nous transmettons notre liste de Product à index.html.twig
        return $this->render('index/index.html.twig', [
            'time' => $toolbox->tellTime(),
            'categories' => $categories,
            'category' => $pseudoCategory,
            'products' => $products,
        ]);
    }

    #[Route('/category/{categoryName}', name: 'index_category')]
    public function indexCategory(string $categoryName, ManagerRegistry $doctrine, EcomToolbox $toolbox): Response
    {
        //Cette méthode permet d'afficher tous les Products liés à une Catégorie indiquée selon le critère de l'URL
        
        //Afin de pouvoir récupérer la catégorie en question de notre base de données, nous avons besoin de l'Entity Manager et du Repository de Category
        $entityManager = $doctrine->getManager();
        $categoryRepository = $entityManager->getRepository(Category::class);
        //Nous récupérons la liste des Categories pour notre navbar
        $categories = $categoryRepository->findAll();
        //Nous recherchons la Catégorie indiquée, si celle-ci n'est pas trouvée, nous retournons à l'index
        $category = $categoryRepository->findOneBy(['name' => $categoryName]);
        if(!$category){
            return $this->redirectToRoute('app_index');
        }
        //Si nous avons récupéré la Category, nous récupérons les produits liés que nous transmettons à index.html.twig
        $products = $category->getProducts();
        return $this->render('index/index.html.twig', [
            'time' => $toolbox->tellTime(),
            'categories' => $categories,
            'category' => $category,
            'products' => $products,
        ]);
    }

    #[Route('/tag/display/{tagName}', name: 'index_tag')]
    public function indexTag(string $tagName, ManagerRegistry $doctrine, Ecomtoolbox $toolbox): Response
    {
        //Cette méthode affiche tous les Product liés à un Tag dont le nom est indiqué dans notre URL

        //Afin de pouvoir retrouver le tag au sein de notre base de données, nous avons besoin de l'Entity Manager ainsi que du Repository de Tag
        $entityManager = $doctrine->getManager();
        $tagRepository = $entityManager->getRepository(Tag::class);
        //Nous récupérons la liste des Categories pour notre navbar
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous recherchons le Tag en question selon la valeur du champ "name", s'il n'est pas trouvé, nous retournons à l'index
        $tag = $tagRepository->findOneBy(['name' => $tagName]);
        if(!$tag){
            return $this->redirectToRoute('app_index');
        }
        //Nous récupérons la liste des Product contenus par le Tag
        $products = $tag->getProducts();
        //Nous créons notre fausse catégorie pour remplir le titre et la description de notre page
        $pseudoCategory = [
            'name' => $tag->getName(),
            'description' => 'Ceci est la liste de tous les Produits liés au tag <i>' . $tag->getName() . '</i>',
        ];
        //Nous transférons la liste de nos Product à notre page index
        return $this->render('index/index.html.twig', [
            'time' => $toolbox->tellTime(),
            'categories' => $categories,
            "category" => $pseudoCategory,
            "products" => $products,
        ]);
    }

    #[Route('/product/display/{productId}', name: 'product_display')]
    public function displayProduct(int $productId, Request $request, ManagerRegistry $doctrine, EcomToolbox $toolbox): Response
    {
        //Cette méthode affiche des informations sur un Product dont l'ID a été renseigné au sein de notre base de données

        //Nous récupérons l'Utilisateur en cours
        $user = $this->getUser(); //Vaut null si personne n'est authentifié
        //Afin de pouvoir récupérer le Product en question, nous devons avoir accès à la base de données, donc récupérer l'Entity Manager et le Repository de Product
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        //On utilise le Repository de Order pour retrouver une commande en cours
        $orderRepository = $entityManager->getRepository(Order::class);
        //Nous récupérons la liste des Categories pour notre navbar
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Une fois que nous avons le Repository, nous récupérons le Product désiré via le paramètre de route
        $product = $productRepository->find($productId);
        //Si le Product n'est pas retrouvé, nous retournons à l'index
        if(!$product){
            return $this->redirectToRoute('app_index');
        }
        //Une fois le Product récupéré, nous préparons un formulaire afin de permettre à l'Utilisateur de spécifier la quantité qu'il désire acheter
        $buyForm = $this->createFormBuilder()
            ->add('quantity', IntegerType::class, [
                    'label' => 'Quantité'
                ])
                ->add('submit', SubmitType::class, [
                    'label' => 'Acheter',
                    'attr' => [
                        'class' => 'w3-button w3-black w3-margin-bottom',
                        'style' => 'margin-top: 10px',
                    ]
                ])
            ->getForm()
        ;
        //Nous appliquons la Request à notre formulaire, et si celui-ci est valide, nous procédons à la simulation d'achat/réservation de produit
        $buyForm->handleRequest($request);
        //Si notre formulaire d'achat est rempli et validé, et que le stock produit est supérieur à zéro
        $productStock = $product->getStock();
        if($buyForm->isSubmitted() && $buyForm->isValid() && ($productStock > 0) && $user && in_array('ROLE_CLIENT', $user->getRoles())){
            //On récupère la valeur de la clef "quantity" obtenue de getData()
            $quantity = $buyForm->getData()['quantity'];
            //Demande de Reservation via le Service toolbox
            $toolbox->reserveProduct($product, $quantity); 
            return $this->redirectToRoute('product_display', ['productId' => $product->getId()]);
        }

        //Si nous avons bien retrouvé notre Product, nous le transmettons à la page product_display.html.twig
        return $this->render('index/product_display.html.twig', [
            'categories' => $categories,
            'buyForm' => $buyForm->createView(),
            'product' => $product,
        ]);
    }

    ##[Route('/product/buy/{productId}', name: 'product_buy')]
    public function buyProduct(int $productId, ManagerRegistry $doctrine): Response
    {
        //Cette méthode simule un achat en décrémentant le stock d'un Product indiqué via son ID dans notre URL

        //Afin de récupérer un élément Product, nous avons besoin de l'Entity Manager ainsi que du Repository pertinent:
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        //Nous récupérons le Product en question, si la recherche n'aboutit pas, nous retournons à l'index
        $product = $productRepository->find($productId);
        if(!$product){
            return $this->redirectToRoute('app_index');
        }
        //Si nous possédons bien notre Product en question, nous désirons décrémenter son stock de la valeur de la variable $quantity SEULEMENT si le résultat est supérieur ou égal à zéro
        $quantity = 9;
        $productStock = $product->getStock();
        //Si le stock du produit est supérieur à zéro, nous effectuons l'achat
        if(($productStock - $quantity) >= 0){ 
            $product->setStock($productStock - $quantity);
            //Nous validons le changement de stock via une persistance
            $entityManager->persist($product);
            $entityManager->flush();
        }
        //Nous retournons à la fiche Product en question, en indiquant l'ID du Product en question
        return $this->redirectToRoute('product_display', ['productId' => $product->getId()]);
    }

}
