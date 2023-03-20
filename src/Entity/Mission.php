<?php

namespace App\Entity;

use App\Enum\ProductType;
use App\Enum\Role;
use App\Repository\MissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MissionRepository::class)]
#[ORM\Table(name: 'missions')]
class Mission
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    #[Groups(['campaign', 'mission_list', 'mission_read'])]
    private string $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $reference = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['mission_list'])]
    private ?string $stateClient;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['mission_list'])]
    private ?string $stateProvider;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['mission_read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['mission_read'])]
    private ?\DateTimeInterface $desiredDelivery = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'missions')]
    #[Groups(['mission_list', 'mission_read'])]
    private Campaign $campaign;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: InfoMission::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private ?Collection $infoMissions;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: FileMission::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private ?Collection $fileMissions;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'missions')]
    private ?User $contact;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: Historique::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private ?Collection $historiques;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $initialBriefing = '';

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['mission_list', 'mission_read'])]
    private Product $product;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: MissionParticipant::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['campaign', 'mission_read'])]
    private ?Collection $participants;

    #[ORM\OneToOne(inversedBy: 'mission', targetEntity: Workflow::class, cascade: ['persist', 'remove'])]
    #[Groups(['mission_read'])]
    private ?Workflow $workflow;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['mission_read'])]
    private ?string $guideStep;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $price = null;

    #[ORM\Column(type: 'string')]
    #[Groups(['mission_list', 'mission_read'])]
    private string $state = 'provisional';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cancelReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $libelleCustom = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $initialTime = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $realTime = null;

    #[ORM\Column(type: 'date', length: 255, nullable: true)]
    private ?\DateTimeInterface $preActivatedAt = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $adminTime = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $adminIncome = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
        $this->createdAt = new \DateTime();
        $this->messages = new ArrayCollection();
        $this->infoMissions = new ArrayCollection();
        $this->fileMissions = new ArrayCollection();
        $this->historiques = new ArrayCollection();
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        if (empty($this->reference)) {
            return null;
        }

        return str_pad($this->reference, 5, 0, STR_PAD_LEFT);
    }

    public function setReference(?int $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getStateClient(): ?string
    {
        return $this->stateClient;
    }

    public function setStateClient(?string $stateClient): self
    {
        $this->stateClient = $stateClient;

        return $this;
    }

    public function getStateProvider(): ?string
    {
        return $this->stateProvider;
    }

    public function setStateProvider(?string $stateProvider): self
    {
        $this->stateProvider = $stateProvider;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface|null $createdAt
     * @return Mission
     */
    public function setCreatedAt(?\DateTimeInterface $createdAt): Mission
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDesiredDelivery(): ?\DateTimeInterface
    {
        return $this->desiredDelivery;
    }

    public function setDesiredDelivery(?\DateTimeInterface $desiredDelivery): self
    {
        $this->desiredDelivery = $desiredDelivery;

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

    /**
     * @return Collection|InfoMission[]
     */
    public function getInfoMissions(): Collection
    {
        return $this->infoMissions;
    }

    public function addInfoMission(InfoMission $infoMission): self
    {
        if (!$this->infoMissions->contains($infoMission)) {
            $this->infoMissions[] = $infoMission;
            $infoMission->setMission($this);
        }

        return $this;
    }

    public function removeInfoMission(InfoMission $infoMission): self
    {
        if ($this->infoMissions->removeElement($infoMission)) {
            // set the owning side to null (unless already changed)
            if ($infoMission->getMission() === $this) {
                $infoMission->setMission(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|FileMission[]
     */
    public function getFileMissions(): Collection
    {
        return $this->fileMissions;
    }

    public function addFileMission(FileMission $fileMission): self
    {
        if (!$this->fileMissions->contains($fileMission)) {
            $this->fileMissions[] = $fileMission;
            $fileMission->setMission($this);
        }

        return $this;
    }

    public function removeFileMission(FileMission $fileMission): self
    {
        if ($this->fileMissions->removeElement($fileMission)) {
            // set the owning side to null (unless already changed)
            if ($fileMission->getMission() === $this) {
                $fileMission->setMission(null);
            }
        }

        return $this;
    }

    public function getContact(): ?User
    {
        return $this->contact;
    }

    public function setContact(?User $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return Collection|Historique[]
     */
    public function getHistoriques(): Collection
    {
        return $this->historiques;
    }

    public function addHistorique(Historique $historique): self
    {
        if (!$this->historiques->contains($historique)) {
            $this->historiques[] = $historique;
            $historique->setMission($this);
        }

        return $this;
    }

    public function removeHistorique(Historique $historique): self
    {
        if ($this->historiques->removeElement($historique)) {
            // set the owning side to null (unless already changed)
            if ($historique->getMission() === $this) {
                $historique->setMission(null);
            }
        }

        return $this;
    }

    public function getInitialBriefing(): ?string
    {
        return $this->initialBriefing;
    }

    public function setInitialBriefing(?string $initialBriefing): self
    {
        $this->initialBriefing = $initialBriefing;

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

    /**
     * @return Collection|MissionParticipant[]
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(MissionParticipant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants[] = $participant;
            $participant->setMission($this);
        }

        return $this;
    }

    public function removeParticipant(MissionParticipant $participant): self
    {
        if ($this->participants->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getMission() === $this) {
                $participant->setMission(null);
            }
        }

        return $this;
    }

    public function getWorkflow(): ?Workflow
    {
        return $this->workflow;
    }

    public function setWorkflow(?Workflow $workflow): self
    {
        $this->workflow = $workflow;

        return $this;
    }

    public function getGuideStep(): ?string
    {
        return $this->guideStep;
    }

    public function setGuideStep(?string $guideStep): self
    {
        $this->guideStep = $guideStep;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

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
    public function getLibelleCustom(): ?string
    {
        return $this->libelleCustom;
    }

    /**
     * @param string|null $libelleCustom
     */
    public function setLibelleCustom(?string $libelleCustom): void
    {
        $this->libelleCustom = $libelleCustom;
    }

    /**
     * @return string|null
     */
    public function getInitialTime(): ?string
    {
        return $this->initialTime;
    }

    /**
     * @param string|null $initialTime
     */
    public function setInitialTime(?string $initialTime): void
    {
        $this->initialTime = $initialTime;
    }

    /**
     * @return string|null
     */
    public function getRealTime(): ?string
    {
        return $this->realTime;
    }

    /**
     * @param string|null $realTime
     */
    public function setRealTime(?string $realTime): void
    {
        $this->realTime = $realTime;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPreActivatedAt(): ?\DateTimeInterface
    {
        return $this->preActivatedAt;
    }

    /**
     * @param \DateTimeInterface|null $preActivatedAt
     */
    public function setPreActivatedAt(?\DateTimeInterface $preActivatedAt): void
    {
        $this->preActivatedAt = $preActivatedAt;
    }

    public function canActivate(): bool
    {
        $count = 0;
        $activated = 0;
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR) {
                $count++;
                if ($participant->isActivated()) {
                    $activated++;
                }
            }
        }

        if ($count === 0 || $count === $activated) {
            return false;
        }

        if ($this->getProduct()->getType() === ProductType::A_EVALUER) {
            foreach ($this->getParticipants() as $participant) {
                if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR && empty($participant->getInitialTime())) {
                    return false;
                }
            }
        }else{
            foreach ($this->getCampaign()->getMissions() as $mission){
                if ($mission->getProduct()->getStatut() === ProductType::A_EVALUER){
                    if ($mission->getStateProvider() == 'A évaluer'){
                        return false;
                    }
                }
            }
        }

        if (null !== $this->getWorkflow()) {
            foreach ($this->getWorkflow()->getSteps() as $step) {
                if ($step->isActive()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return string|null
     */
    public function getAdminTime(): ?string
    {
        return $this->adminTime;
    }

    /**
     * @param string|null $adminTime
     */
    public function setAdminTime(?string $adminTime): void
    {
        $this->adminTime = $adminTime;
    }

    /**
     * @return string|null
     */
    public function getAdminIncome(): ?string
    {
        return $this->adminIncome;
    }

    /**
     * @param string|null $adminIncome
     */
    public function setAdminIncome(?string $adminIncome): void
    {
        $this->adminIncome = $adminIncome;
    }

}
