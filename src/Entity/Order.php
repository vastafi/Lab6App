<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="json")
     */
    private $items = [];

    /**
     * @ORM\Column(type="json")
     */
    private $paymentDetails = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * @ORM\Column(type="json")
     */
    private $shippingDetails = [];

    /**
     * @ORM\Column(type="float")
     */
    private $total;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders")
     */
    private $user;

    private $creditCardDetails = [];

    /**
     * @return array
     */
    public function getCreditCardDetails(): array
    {
        return $this->creditCardDetails;
    }

    /**
     * @param array $creditCardDetails
     */
    public function setCreditCardDetails(array $creditCardDetails): void
    {
        $this->creditCardDetails = $creditCardDetails;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItems(): ?array
    {
        return $this->items;
    }

    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function getPaymentDetails(): ?array
    {
        return $this->paymentDetails;
    }

    public function setPaymentDetails(array $paymentDetails): self
    {
        $this->paymentDetails = $paymentDetails;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getShippingDetails(): ?array
    {
        return $this->shippingDetails;
    }

    public function setShippingDetails(array $shippingDetails): self
    {
        $this->shippingDetails = $shippingDetails;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
