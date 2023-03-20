<?php

namespace App\Entity;

use App\Repository\WorkflowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkflowRepository::class)]
#[ORM\Table(name: 'workflows')]
class Workflow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['mission_read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Merci de remplir ce champs')]
    private string $name = '';

    #[ORM\Column(type: 'boolean')]
    private bool $template = false;

    #[ORM\Column(type: 'boolean')]
    private bool $active = false;

    #[ORM\OneToMany(mappedBy: 'workflow', targetEntity: WorkflowStep::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['mission_read'])]
    private Collection $steps;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    private $company;

    #[ORM\OneToOne(mappedBy: 'workflow', targetEntity: Mission::class)]
    private ?Mission $mission = null;

    public function __construct()
    {
        $this->steps = new ArrayCollection();
    }

    public function __clone()
    {
        $stepsClone = new ArrayCollection();

        foreach ($this->steps as $s) {
            $step = clone $s;
            if (!$stepsClone->contains($step)) {
                $stepsClone[] = $step;
                $step->setWorkflow($this);
                $step->setStartDate(null);
                $step->setEndDate(null);
            }
        }

        $this->steps = $stepsClone;
    }

    public function getId(): ?int
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

    public function getTemplate(): ?bool
    {
        return $this->template;
    }

    public function setTemplate(bool $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function activate(): self
    {
        $this->active = true;

        return $this;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function deactivate(): self
    {
        $this->active = false;

        return $this;
    }

    /**
     * @return Collection|WorkflowSteps[]
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(WorkflowStep $step): self
    {
        if (!$this->steps->contains($step)) {
            $this->steps[] = $step;
            $step->setWorkflow($this);
        }

        return $this;
    }

    public function removeStep(WorkflowStep $step): self
    {
        if ($this->steps->removeElement($step)) {
            // set the owning side to null (unless already changed)
            if ($step->getWorkflow() === $this) {
                $step->setWorkflow(null);
            }
        }

        return $this;
    }

    public function getTotalCompletionTime(): float
    {
        $total = 0;

        foreach ($this->steps as $step) {
            $total += $step->getCompletionTime();
        }

        return $total;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

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

    public function getMission(): ?Mission
    {
        return $this->mission;
    }

    public function setMission(?Mission $mission): self
    {
        $this->mission = $mission;

        return $this;
    }

    public function getActiveStep(): WorkflowStep|bool
    {
        return $this->getSteps()->filter(function(WorkflowStep $step) {
            return $step->isActive();
        })->first();
    }

    public function getProgress(): int
    {
        if ($this->getMission()->getHistoriques()->count() === 0) {
            return 0;
        }

        $activeStep = $this->getActiveStep();

        if ($activeStep === false) {
            return 100;
        }

        if ($this->getSteps()->count() > 0) {
            return $this->getSteps()->indexOf($activeStep) / $this->getSteps()->count() * 100;
        }

        return 0;
    }
}
