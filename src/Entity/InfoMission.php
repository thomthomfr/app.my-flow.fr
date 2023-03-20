<?php

namespace App\Entity;

use App\Repository\InfoMissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: InfoMissionRepository::class)]
#[ORM\Table(name: 'infos_missions')]
class InfoMission
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    private string $id;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Ce champ est requis')]
    private string $content;

    #[ORM\ManyToOne(targetEntity: Mission::class, inversedBy: 'infoMissions')]
    private Mission $mission;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'infoMissions')]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
        $this->createdAt = new \DateTime('now');
    }
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
