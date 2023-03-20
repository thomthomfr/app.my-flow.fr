<?php

namespace App\Entity;

use App\Enum\Role;
use App\Repository\WorkflowActionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkflowActionRepository::class)]
#[ORM\Table(name: 'workflow_actions')]
class WorkflowAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Merci de remplir de champs')]
    private string $name = '';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotNull(message: 'Merci de remplir de champs')]
    private string $recipient;

    #[ORM\ManyToOne(targetEntity: Job::class)]
    private ?Job $job = null;

    #[ORM\ManyToOne(targetEntity: WorkflowStep::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WorkflowStep $step = null;

    #[ORM\OneToMany(mappedBy: 'action', targetEntity: WorkflowTrigger::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $triggers;

    public function __construct()
    {
        $this->triggers = new ArrayCollection();
    }

    public function __clone()
    {
        $triggersClone = new ArrayCollection();

        foreach ($this->triggers as $t) {
            $trigger = clone $t;
            if (!$triggersClone->contains($trigger)) {
                $triggersClone[] = $trigger;
                $trigger->setAction($this);
            }
        }

        $this->triggers = $triggersClone;
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

    public function getRecipient(): ?Role
    {
        return Role::from($this->recipient);
    }

    public function setRecipient(Role $recipient): self
    {
        $this->recipient = $recipient->value;

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

    public function getStep(): ?WorkflowStep
    {
        return $this->step;
    }

    public function setStep(?WorkflowStep $step): self
    {
        $this->step = $step;

        return $this;
    }

    /**
     * @return Collection|WorkflowTrigger[]
     */
    public function getTriggers(): Collection
    {
        return $this->triggers;
    }

    public function addTrigger(WorkflowTrigger $trigger): self
    {
        if (!$this->triggers->contains($trigger)) {
            $this->triggers[] = $trigger;
            $trigger->setAction($this);
        }

        return $this;
    }

    public function removeTrigger(WorkflowTrigger $trigger): self
    {
        if ($this->triggers->removeElement($trigger)) {
            // set the owning side to null (unless already changed)
            if ($trigger->getAction() === $this) {
                $trigger->setAction(null);
            }
        }

        return $this;
    }
}
