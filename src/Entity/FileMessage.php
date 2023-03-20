<?php

namespace App\Entity;

use App\Repository\FileMessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FileMessageRepository::class)]
#[ORM\Table(name: 'files_messages')]
class FileMessage
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    #[Groups(['mission_read'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['mission_read'])]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'fileMessages')]
    private Message $messages;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMessages(): ?Message
    {
        return $this->messages;
    }

    public function setMessages(?Message $messages): self
    {
        $this->messages = $messages;

        return $this;
    }
}
