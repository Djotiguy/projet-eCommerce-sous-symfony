<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Category;
use App\Entity\Reservation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Security('is_granted("ROLE_CLIENT")')]
#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'order_display')]
    public function orderDisplay(ManagerRegistry $doctrine): Response
    {
        //Cette méthode nous affiche la liste des commandes/Order en cours et validées

        //Nous récupérons l'Utilisateur en cours
        $user = $this->getUser(); //Vaut NULL si personne n'est authentifié
        //Afin de pouvoir récupérer les Order de notre BDD, nous avons de l'Entity Manager ainsi que du Repository de Order
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        //Nous récupérons nos Category afin de pouvoir les afficher sur le header
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous récupérons nos Order en mode panier et validées via deux requêtes successives
        $activeOrder = $orderRepository->findOneBy(['status' => 'panier', 'user' => $user,], ['id' => 'DESC']);
        $archivedOrders = $orderRepository->findBy(['status' => 'validee', 'user' => $user,], ['id' => 'DESC']);
        //Nous transmettons la liste de nos commandes à notre page twig dédiée
        return $this->render('order/order_display.html.twig', [
            'categories' => $categories,
            'activeOrder' => $activeOrder,
            'archivedOrders' => $archivedOrders,
        ]);
    }

    #[Route('/validate/{orderId}', name: 'order_validate')]
    public function validateOrder(int $orderId, ManagerRegistry $doctrine): Response
    {
        //Cette méthode a pour objectif de changer le statut d'une commande en cours de "Panier" à "Validée"

        //Nous récupérons l'Utilisateur en cours
        $user = $this->getUser();
        //Afin de récupérer l'Order que nous voulons valider, nous avons besoin de l'Entity Manager ainsi que du Repository de Order
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        //Si l'Order en question n'est pas retrouvé ou n'est pas en mode panier, nous retournons à notre tableau de bord commandes
        $order = $orderRepository->find($orderId);
        //La seconde condition n'est abordée que si la première n'est pas validée. Ainsi, nous ferons appel à la méthode getStatus UNIQUEMENT si la première condition (!$order) n'est pas validée, ce qui nous préserve d'un risque d'appel de méthode sur null
        if(!$order || $order->getStatus() != "panier" || $order->getUser() != $user){
            return $this->redirectToRoute('order_display');
        }
        //Si nous possédons bien notre order en mode "panier", il nous suffit de changer son statue à "validee" avant de persister le résultat
        $order->setStatus("validee");
        $entityManager->persist($order);
        $entityManager->flush();
        //Nous retournons à notre tableau de bord Commandes
        return $this->redirectToRoute('order_display');
    }

    #[Route('/delete/{orderId}', name: 'order_delete')]
    public function deleteOrder(int $orderId, ManagerRegistry $doctrine): Response
    {
        //Cette méthode supprime une Entity Order en mode panier de notre base de données, ainsi que toutes les Reservations associées, selon l'ID de la commande fournie dans l'URL

        //Nous récupérons l'Utilisateur en cours
        $user = $this->getUser();
        //Afin de pouvoir récupérer notre Order à supprimer, nous avons besoin de l'Entity Manager ainsi que du Repository de Order
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        //Nous récupérons la commande en question, si celle-ci n'est pas trouvée OU que la commande n'est pas en mode "panier", nous retournons au tableau de bord
        $order = $orderRepository->find($orderId);
        if(!$order || $order->getStatus() != "panier" || $order->getUser() != $user){
            return $this->redirectToRoute('order_display');
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
        //Nous retournons au tableau de bord Commandes
        return $this->redirectToRoute('order_display');
    }

    #[Route('/reservation/delete/{reservationId}', name: 'reservation_delete')]
    public function deleteReservation(int $reservationId, ManagerRegistry $doctrine): Response
    {
        //Cette méthode a pour objectif de supprimer de notre base de données une Reservation d'une commande en cours, identifiée via son ID fourni dans notre URL

        //Nous récupérons l'Utilisateur en cours
        $user = $this->getUser();
        //Afin de récupérer la Reservation que nous voulons supprimer, nous avons besoin de l'Entity Manager ainsi que du Repository de Reservation
        $entityManager = $doctrine->getManager();
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        //Si la Reservation en question n'est pas retrouvée ou que sa commande n'est pas en mode panier, nous retournons à notre tableau de bord commandes
        $reservation = $reservationRepository->find($reservationId);
        //La seconde condition n'est abordée que si la première n'est pas validée. Ainsi, nous ferons appel à la méthode getStatus UNIQUEMENT si la première condition (!$reservation) n'est pas validée, ce qui nous préserve d'un risque d'appel de méthode sur null
        if(!$reservation || $reservation->getOrder()->getStatus() != "panier" || $reservation->getOrder()->getUser() != $user){
            return $this->redirectToRoute('order_display');
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
        //Nous retournons à notre tableau de bord Commandes
        return $this->redirectToRoute('order_display');
    }
}
