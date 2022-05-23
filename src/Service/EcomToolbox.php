<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class EcomToolbox{
    //Notre classe Service pour notre application Symfony eCommerce

    private $requestStack;
    private $security;
    private $manager;

    public function __construct(RequestStack $requestStack, Security $security, EntityManagerInterface $manager){
        //Nous récupérons l'instance RequestStack afin de pouvoir utiliser l'objet Request au sein de notre classe de service
        //Pour pouvoir récupérer la Request, nous utiliserons la méthode getCurrentRequest()
        $this->requestStack = $requestStack;
        //On récupère l'outil de sécurité
        $this->security = $security;
        //On récupère l'Entity Manager
        $this->manager = $manager;
    }

    public function reserveProduct(Product $product, int $quantity): void
    {
        //Cette méthode prend en charge la création de Reservation ainsi que leur attachement à une commande (Order), selon les utilisateurs

        //On récupère l'Entity Manager ainsi que le Repository de Order, ainsi que notre instance de User active
        $entityManager = $this->manager;
        $orderRepository = $entityManager->getRepository(Order::class);
        $user = $this->security->getUser();
        //Nous créons notre Reservation et nous la lions au Product
        $productStock = $product->getStock();
        $reservation = new Reservation;
        $reservation->setProduct($product);
        //Nous vérifions s'il existe une commande (Order) avec un status "panier". Si non, nous la créons et la lions à notre Reservation
        $order = $orderRepository->findOneBy(["status" => "panier", 'user' => $user]);
        if(!$order){
            //Si la commande n'existe pas, nous la créons et nous la lions à l'Utilisateur
            $order = new Order($user);
        } 
        $reservation->setOrder($order); //Nous lions la commande et la réservation
        //Si le stock du produit est supérieur à zéro, nous effectuons l'achat
        if($productStock >= $quantity){ 
            $product->setStock($productStock - $quantity);
            //La Reservation prend la valeur de $quantity
            $reservation->setQuantity($quantity);
            //On prépare un flashbag
            $this->generateFlashbag('Votre commande a bien été effectuée.', 'Achat', 'green');
            $this->generateFlashbag('Vous avez réservé ' . $reservation->getQuantity() . ' éléments.');
        } else {
            //Si la quantité réclamée est supérieure au stock, on transmet le stock entier à notre Reservation
            $reservation->setQuantity($product->getStock());
            $product->setStock(0);
            //On prépare un flashbag
            $this->generateFlashbag('Stock insuffisant, les éléments restants ont été attribués à votre commande', 'Achat', 'yellow');
            $this->generateFlashbag('Vous avez réservé ' . $reservation->getQuantity() . ' éléments.');
        }
        //Nous validons le changement de stock via une persistance avant redirection
        $entityManager->persist($product);
        $entityManager->persist($reservation);
        $entityManager->persist($order);
        $entityManager->flush();
    }

    public function generateFlashbag(string $message, string $title = "", string $status = ""): void
    {
        //Cette méthode aura pour objectif de générer un Flashbag avec les indications passées en paramètre

        //Tout d'abord, nous récupérons notre objet Request
        $request = $this->requestStack->getCurrentRequest();
        //On active le Panel
        $request->getSession()->set('infopanel', true);
        //Nous renseignons ensuite notre Flashbad avec les informations passées en paramètre
        $request->getSession()->getFlashBag()->add('info', $message);
        //Si des informations supplémentaires concernant les variables de session sont passées via les paramètres, nous mettons ces variables à jour
        if($title){
            $request->getSession()->set('message_title', $title);
        }
        if($status){
            $request->getSession()->set('status', $status);
        }
    }

    public function tellTime(): \DateTime
    {
        //Cette méthode retourne l'heure à laquelle elle a été appelée, sous la forme d'un objet DateTime
        return new \DateTime("now");
    }

}