<?php

namespace App\Entity;

use App\Enum\Operation;
use App\Enum\Operator;
use App\Enum\Period;
use App\Enum\Trigger;
use App\Repository\WorkflowTriggerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkflowTriggerRepository::class)]
#[ORM\Table(name: 'workflow_triggers')]
class WorkflowTrigger
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotNull(message: 'Ce champs ne peut être vide')]
    private int $triggerType;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $operator;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $timePeriod;

    #[ORM\ManyToOne(targetEntity: WorkflowAction::class, inversedBy: 'triggers')]
    private ?WorkflowAction $action;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $operation;

    #[ORM\ManyToOne(targetEntity: EmailTemplate::class)]
    private ?EmailTemplate $emailTemplate;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childs')]
    private ?self $parent;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist'], orphanRemoval: true)]
    private ?Collection $childs;

    public function __construct()
    {
        $this->childs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTriggerType(): ?Trigger
    {
        return Trigger::from($this->triggerType);
    }

    public function setTriggerType(Trigger $triggerType): self
    {
        $this->triggerType = $triggerType->value;

        return $this;
    }

    public function getOperator(): ?Operator
    {
        return Operator::tryFrom($this->operator);
    }

    public function setOperator(?Operator $operator): self
    {
        $this->operator = $operator?->value;

        return $this;
    }

    public function getTimePeriod(): ?Period
    {
        return Period::tryFrom($this->timePeriod);
    }

    public function setTimePeriod(?Period $timePeriod): self
    {
        $this->timePeriod = $timePeriod?->value;

        return $this;
    }

    public function getAction(): ?WorkflowAction
    {
        return $this->action;
    }

    public function setAction(?WorkflowAction $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getOperation(): ?Operation
    {
        return Operation::from($this->operation);
    }

    public function setOperation(?Operation $operation): self
    {
        $this->operation = $operation?->value;

        return $this;
    }

    public function getEmailTemplate(): ?EmailTemplate
    {
        return $this->emailTemplate;
    }

    public function setEmailTemplate(?EmailTemplate $emailTemplate): self
    {
        $this->emailTemplate = $emailTemplate;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChilds(): Collection
    {
        return $this->childs;
    }

    public function addChild(self $child): self
    {
        if (!$this->childs->contains($child)) {
            $this->childs[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->childs->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }
}
