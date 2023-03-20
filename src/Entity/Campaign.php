<?php

namespace App\Entity;

use App\Enum\ProductType;
use App\Enum\Role;
use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'campaigns')]
class Campaign
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    #[Groups(['campaign', 'mission_list', 'mission_read'])]
    private string $id;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(message: 'Merci de remplir ce champs')]
    #[Groups(['campaign', 'mission_list', 'mission_read'])]
    private string $name = '';

    #[ORM\Column(type: 'string')]
    #[Groups(['campaign'])]
    private string $state = 'provisional';

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['campaign'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'campaigns')]
    #[Groups(['campaign', 'mission_read'])]
    private ?Company $company = null;

    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Mission::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['campaign'])]
    private Collection $missions;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'campaigns')]
    private ?User $orderedBy = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $invoiced = false;

    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Invoice::class, cascade: ['persist'])]
    private ?Collection $invoices;

    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Message::class)]
    #[Groups(['mission_read'])]
    private ?Collection $messages;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $totalCostCampaign = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cancelReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $brief = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
        $this->createdAt = new \DateTimeImmutable();
        $this->missions = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Campaign
     */
    public function setName(string $name): Campaign
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeImmutable|null $createdAt
     * @return Campaign
     */
    public function setCreatedAt(?\DateTimeImmutable $createdAt): Campaign
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return Collection|Mission[]
     */
    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(Mission $mission): self
    {
        if (!$this->missions->contains($mission)) {
            $this->missions[] = $mission;
            $mission->setCampaign($this);
        }

        return $this;
    }

    public function removeMission(Mission $mission): self
    {
        if ($this->missions->removeElement($mission)) {
            // set the owning side to null (unless already changed)
            if ($mission->getCampaign() === $this) {
                $mission->setCampaign(null);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return Campaign
     */
    public function setState(string $state): Campaign
    {
        $this->state = $state;
        return $this;
    }

    public function getOrderedBy(): ?User
    {
        return $this->orderedBy;
    }

    public function setOrderedBy(?User $orderedBy): self
    {
        $this->orderedBy = $orderedBy;

        return $this;
    }

    public function getProgress(): int
    {
        $progress = 0;

        foreach ($this->getMissions() as $mission) {
            if (!empty($mission->getWorkflow())){
                $progress += $mission->getWorkflow()->getProgress();
            }
        }

        if ($progress > 0) {
            return $progress / $this->getMissions()->count();
        }

        return round($progress);
    }

    public function getTotalCost(): float
    {
        $cost = 0;

        foreach ($this->getMissions() as $mission) {
            $cost += $mission->getPrice() * $mission->getQuantity();
        }

        return $cost;
    }

    public function getInvoiced(): ?bool
    {
        return $this->invoiced;
    }

    public function setInvoiced(?bool $invoiced): self
    {
        $this->invoiced = $invoiced;

        return $this;
    }

    /**
     * @return Collection|Invoice[]
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): self
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices[] = $invoice;
            $invoice->setCampaign($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): self
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getCampaign() === $this) {
                $invoice->setCampaign(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setCampaign($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getCampaign() === $this) {
                $message->setCampaign(null);
            }
        }

        return $this;
    }

    public function getTotalCostCampaign(): ?string
    {
        return $this->totalCostCampaign;
    }

    public function setTotalCostCampaign(?string $totalCostCampaign): self
    {
        $this->totalCostCampaign = $totalCostCampaign;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    /**
     * @param string|null $cancelReason
     */
    public function setCancelReason(?string $cancelReason): void
    {
        $this->cancelReason = $cancelReason;
    }

    /**
     * @return string|null
     */
    public function getBrief(): ?string
    {
        return $this->brief;
    }

    /**
     * @param string|null $brief
     */
    public function setBrief(?string $brief): void
    {
        $this->brief = $brief;
    }

    public function canActivate(bool $isAdmin = false, bool $isSubContractor = false): bool
    {
        if ($this->getMissions()->count() === 0) {
            return false;
        }

        if (!$isSubContractor && !$isAdmin){
            return false;
        }

        $subcontractors = [];
        foreach ($this->getMissions() as $mission) {
            if (!$mission->canActivate()) {
                return false;
            }

            foreach ($mission->getParticipants() as $participant) {
                if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR && !in_array($participant->getUser()->getId(), $subcontractors)) {
                    $subcontractors[] = $participant->getUser()->getId();
                }
            }
        }

        if (!$isAdmin && count($subcontractors) > 1) {
            return false;
        }

        return true;
    }
}
