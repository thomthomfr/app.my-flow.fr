<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'services')]
class Service
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $price = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'services')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    private Product $product;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $resale = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPrice(): ?string
    {
        if (empty(trim($this->price))) {
            return null;
        }

        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getResale(): ?string
    {
        return $this->resale;
    }

    public function setResale(?string $resale): self
    {
        $this->resale = $resale;

        return $this;
    }

}
