<?php

namespace App\Entity;

use App\Enum\Manager;
use App\Repository\WorkflowStepRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkflowStepRepository::class)]
#[ORM\Table(name: 'workflow_steps')]
class WorkflowStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['mission_read', 'step_write'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Workflow::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(nullable: false)]
    private Workflow $workflow;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le champs "Nom de l\'étape" doit être rempli"')]
    #[Groups(['mission_read'])]
    private string $name = '';

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Assert\NotBlank(message: 'Le champs "Temps de réalisation" doit être rempli"')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Le champs "Temps de réalisation" doit être supérieur ou égal à 0')]
    private ?float $completionTime = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerDescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $supplierDescription = null;

    #[ORM\OneToMany(mappedBy: 'step', targetEntity: WorkflowAction::class, cascade: ['persist'], orphanRemoval: true)]
    private ?Collection $actions;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['mission_read'])]
    private int $manager;

    #[ORM\ManyToOne(targetEntity: Job::class)]
    #[Groups(['mission_read'])]
    private ?Job $job = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['mission_read'])]
    private bool $active = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endDate;

    public function __construct()
    {
        $this->actions = new ArrayCollection();
        $this->manager = Manager::CLIENT->value;
    }

    public function __clone()
    {
        $actionsClone = new ArrayCollection();

        foreach ($this->actions as $a) {
            $action = clone $a;
            if (!$actionsClone->contains($action)) {
                $actionsClone[] = $action;
                $action->setStep($this);
            }
        }

        $this->actions = $actionsClone;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCompletionTime(): ?string
    {
        return $this->completionTime;
    }

    public function setCompletionTime(string $completionTime): self
    {
        $this->completionTime = $completionTime;

        return $this;
    }

    public function getCustomerDescription(): ?string
    {
        return $this->customerDescription;
    }

    public function setCustomerDescription(?string $customerDescription): self
    {
        $this->customerDescription = $customerDescription;

        return $this;
    }

    public function getSupplierDescription(): ?string
    {
        return $this->supplierDescription;
    }

    public function setSupplierDescription(?string $supplierDescription): self
    {
        $this->supplierDescription = $supplierDescription;

        return $this;
    }

    /**
     * @return Collection|WorkflowAction[]
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function addAction(WorkflowAction $action): self
    {
        if (!$this->actions->contains($action)) {
            $this->actions[] = $action;
            $action->setStep($this);
        }

        return $this;
    }

    public function removeAction(WorkflowAction $action): self
    {
        if ($this->actions->removeElement($action)) {
            // set the owning side to null (unless already changed)
            if ($action->getStep() === $this) {
                $action->setStep(null);
            }
        }

        return $this;
    }

    public function getManager(): ?Manager
    {
        return Manager::tryFrom($this->manager);
    }

    public function setManager(Manager $manager): self
    {
        $this->manager = $manager->value;

        return $this;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(?Job $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }
}
