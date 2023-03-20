<?php

namespace App\Entity;

use App\Repository\NotificationToSendRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationToSendRepository::class)]
class NotificationToSend
{
    const DAILY = 1;
    const WEEKLY = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'send_to', nullable: false)]
    private ?User $sendTo = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeInterface $sendAt = null;

    #[ORM\Column(type: 'smallint')]
    private ?int $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSendAt(): ?\DateTimeInterface
    {
        return $this->sendAt;
    }

    public function setSendAt(\DateTimeInterface $sendAt): self
    {
        $this->sendAt = $sendAt;

        return $this;
    }

    public function getSendTo(): ?User
    {
        return $this->sendTo;
    }

    public function setSendTo(?User $sendTo): self
    {
        $this->sendTo = $sendTo;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(?int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
