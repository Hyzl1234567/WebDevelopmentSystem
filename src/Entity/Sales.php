<?php

namespace App\Entity;

use App\Repository\SalesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalesRepository::class)]
class Sales
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sales')]
    private ?Product $product = null; // ✅ Fixed capitalization

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?float $totalAmount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $saleDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product // ✅ Fixed capitalization
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static // ✅ Fixed capitalization
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getSaleDate(): ?\DateTimeImmutable
    {
        return $this->saleDate;
    }

    public function setSaleDate(\DateTimeImmutable $saleDate): static
    {
        $this->saleDate = $saleDate;

        return $this;
    }
}
