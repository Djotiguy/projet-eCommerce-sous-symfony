<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $quantity;

    #[ORM\Column(type: 'datetime')]
    private $creationDate;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Order', inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: true)]
    private $order;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Product', inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: true)]
    private $product;

    public function __construct()
    {
        $this->creationDate = new \DateTime("now");
    }

    public function getTotalPrice(): float
    {
        //Cette méthode renvoie le prix total de la Reservation selon le prix actuel de la référence Product
        if($this->product){
            return $this->quantity * $this->product->getPrice();
        } else return 0;
    }

    public function returnStock(): ?int
    {
        //Cette méthode incrément la valeur de l'attribut $stock de l'objet Product lié avec la valeur de $quantity, placé ensuite à une valeur de zéro
        if($this->product){ //On vérifie déjà si notre Reservation a un Product lié
            $this->product->grantStock(-($this->quantity)); //grantStock() ici incrémente la valeur du stock de Product lié de la valeur de la quantité de notre Reservation, du fait de la valeur négative de cette dernière passée en paramètre
            $this->quantity = 0; //On place la valeur de notre quantity à zéro, à présent que le stock a été retourné au Product
            return $this->product->getStock(); //On retourne le nouveau stock du Product
        } else return null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }
}
