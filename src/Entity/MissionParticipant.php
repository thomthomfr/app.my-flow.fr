<?php

namespace App\Entity;

use App\Enum\Role;
use App\Repository\MissionParticipantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MissionParticipantRepository::class)]
#[ORM\Table(name: 'mission_participants')]
class MissionParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['mission_read', 'mission_participant_write'])]
    private $id;

    #[ORM\ManyToOne(targetEntity: Mission::class, inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private Mission $mission;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['campaign', 'mission_read', 'mission_participant_write'])]
    private User $user;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['campaign', 'mission_read', 'mission_participant_write'])]
    private string $role;

    #[ORM\ManyToOne(targetEntity: Job::class)]
    #[Groups(['campaign', 'mission_read'])]
    private ?Job $job = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedIncome;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $initialTime;

    #[ORM\Column(type: 'boolean', length: 255)]
    private bool $activated = false;

    #[ORM\Column(type: 'date', length: 255, nullable: true)]
    private ?\DateTimeInterface $activatedAt = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $validStep = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMission(): ?Mission
    {
        return $this->mission;
    }

    public function setMission(?Mission $mission): self
    {
        $this->mission = $mission;

        return $this;
    }

    public function getRole(): ?Role
    {
        return Role::tryFrom($this->role);
    }

    public function setRole(Role $role): self
    {
        $this->role = $role->value;

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

    public function getEstimatedIncome(): ?string
    {
        return $this->estimatedIncome;
    }

    public function setEstimatedIncome(?string $estimatedIncome): self
    {
        $this->estimatedIncome = $estimatedIncome;

        return $this;
    }

    public function getInitialTime(): ?string
    {
        return $this->initialTime;
    }

    public function setInitialTime(?string $initialTime): self
    {
        $this->initialTime = $initialTime;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activated;
    }

    /**
     * @param bool $activated
     */
    public function setActivated(bool $activated): void
    {
        $this->activated = $activated;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getActivatedAt(): ?\DateTimeInterface
    {
        return $this->activatedAt;
    }

    /**
     * @param \DateTimeInterface|null $activatedAt
     */
    public function setActivatedAt(?\DateTimeInterface $activatedAt): void
    {
        $this->activatedAt = $activatedAt;
    }


    /**
     * @return bool|null
     */
    public function getValidStep(): ?bool
    {
        return $this->validStep;
    }

    /**
     * @param bool|null $validStep
     */
    public function setValidStep(?bool $validStep): void
    {
        $this->validStep = $validStep;
    }

}
