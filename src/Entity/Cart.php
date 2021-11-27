<?php

namespace App\Entity;

use App\Repository\CartRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CartRepository::class)
 */
class Cart
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
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="cart", cascade={"persist", "remove"})
     */
    private $user;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function addItem(string $code, int $amount, int $available)
    {
        $item = ["code"=>$code, "amount"=>$amount];
        $items = $this->getItems();
        $position = array_search($code, array_map(function($item){
            return $item['code'];
        }, $items));
        if($position !== false)
        {
            if(($items[$position]['amount'] + $amount) > $available){
                return false;
            }
            $items[$position]['amount'] += $amount;
        }
        else{
            array_push($items, $item);
        }
        $this->setItems($items);
        return true;
    }

    public function removeItem(string $code)
    {
        $items = $this->getItems();
        unset($items[array_search($code, array_map(function($item) {
                return $item['code'];
            }, $items))]);
        $this->setItems($items);
    }

    public function setAmount(string $code, int $amount)
    {
        $items = $this->getItems();
        $position = array_search($code, array_map(function($item){
            return $item['code'];
        }, $items));
        if($position !== false)
        {
            $items[$position]['amount'] = $amount;
        }
        $this->setItems($items);
    }
}
