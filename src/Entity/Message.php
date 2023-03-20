<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'messages')]
class Message
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    #[Groups(['mission_read', 'message_write'])]
    private string $id;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['mission_read', 'message_write'])]
    private ?string $content = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messages')]
    #[Groups(['mission_read', 'message_write'])]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'messages')]
    private Campaign $campaign;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['mission_read', 'message_write'])]
    private \DateTimeInterface $createdAt;

    #[ORM\OneToMany(mappedBy: 'messages', targetEntity: FileMessage::class, cascade: ["persist"])]
    #[Groups(['mission_read'])]
    private $fileMessages;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
        $this->createdAt = new \DateTime('now');
        $this->fileMessages = new ArrayCollection();
    }
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

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

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): self
    {
        $this->campaign = $campaign;

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

    /**
     * @return Collection|FileMessage[]
     */
    public function getFileMessages(): Collection
    {
        return $this->fileMessages;
    }

    public function addFileMessage(FileMessage $fileMessage): self
    {
        if (!$this->fileMessages->contains($fileMessage)) {
            $this->fileMessages[] = $fileMessage;
            $fileMessage->setMessages($this);
        }

        return $this;
    }

    public function removeFileMessage(FileMessage $fileMessage): self
    {
        if ($this->fileMessages->removeElement($fileMessage)) {
            // set the owning side to null (unless already changed)
            if ($fileMessage->getMessages() === $this) {
                $fileMessage->setMessages(null);
            }
        }

        return $this;
    }


}
