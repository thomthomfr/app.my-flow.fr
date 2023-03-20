<?php

namespace App\Entity;

use App\Repository\HistoriqueRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HistoriqueRepository::class)]
#[ORM\Table(name: 'historiques')]
class Historique
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $message;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: Mission::class, inversedBy: 'historiques')]
    private ?Mission $mission = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'historiques')]
    private ?User $user = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
        $this->createdAt = new \DateTime('now');
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getMission(): ?Mission
    {
        return $this->mission;
    }

    public function setMission(?Mission $mission): self
    {
        $this->mission = $mission;

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
