<?php

namespace App\Entity;

use App\Enum\NotificationType;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée par un autre compte')]
/**
 * @Vich\Uploadable()
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    #[Groups(['campaign', 'user_read', 'mission_read', 'mission_participant_write', 'message_write'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    #[Assert\NotBlank(message: 'Ce champs est requis')]
    #[Groups(['user_read', 'mission_read', 'mission_participant_write', 'message_write'])]
    private ?string $firstname = null;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    #[Assert\NotBlank(message: 'Ce champs est requis')]
    #[Groups(['user_read', 'mission_read', 'mission_participant_write', 'message_write'])]
    private ?string $lastname = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Regex(pattern: '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/')]
    #[AssertPhoneNumber(defaultRegion:'FR', message: 'Ce numéro de téléphone n\'est pas valide')]
    #[Groups(['user_read', 'mission_read', 'mission_participant_write'])]
    private ?string $cellPhone = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $enabled = false;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $dailyRate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\Expression(
        "'ROLE_SUBCONTRACTOR' not in this.getRoles() or this.getResaleRate() > this.getDailyRate()",
        message: 'Le tarif revente doit être supérieure au tarif journalier.'
    )]
    private ?float $resaleRate = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $billingMethod = 0;

    /**
     * @Vich\UploadableField(mapping="sub_contractor_image", fileNameProperty="pictureName")
     */
    #[Assert\File(mimeTypes: ['image/png', 'image/jpeg', 'image/jpg'], mimeTypesMessage: 'Les formats supportés sont : PNG, JPEG, JPG')]
    private ?File $picture = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['mission_read'])]
    private ?string $pictureName = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user_read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Ce champ est requis')]
    #[Assert\Email(message: 'Cette adresse n\'est pas valide')]
    #[Groups(['user_read', 'mission_read', 'mission_participant_write'])]
    private string $email = '';

    #[ORM\Column(type: 'json')]
    #[Groups(['user_read'])]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password = '';

    #[Assert\Length(min: 8, minMessage: 'Le mot de passe doit faire 8 charactères minimum')]
    #[Assert\NotCompromisedPassword(message: 'Votre mot de passe est trop faible, merci d\'en choisir un plus fort', threshold: 50000)]
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastConnectionAt = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $accountType;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'users')]
    #[Groups(['user_read'])]
    private ?Company $company;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Message::class)]
    private $messages;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: InfoMission::class)]
    private $infoMissions;

    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: Mission::class)]
    private $missions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Historique::class)]
    private $historiques;

    #[ORM\OneToMany(mappedBy: 'orderedBy', targetEntity: CreditHistory::class)]
    private $creditHistories;

    #[ORM\OneToMany(mappedBy: 'orderedBy', targetEntity: Campaign::class)]
    private $campaigns;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SubContractorCompany::class, cascade: ["persist"])]
    private $subContractorCompanies;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $notificationType = [0,1,2];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $notification = [0,1,2,3,4,5];

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $freqNotification = 1;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Device::class)]
    private $devices;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Service::class)]
    private $services;

    #[ORM\ManyToMany(targetEntity: Job::class, mappedBy: 'user')]
    private $jobs;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $isActiveNotification = false;

    #[ORM\Column(type: 'boolean')]
    private ?bool $deleted = false;

    #[ORM\Column(type: 'boolean')]
    private ?bool $referencingConfirmationNotification = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $gender;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
        $this->createdAt = new \DateTime('now');
        $this->messages = new ArrayCollection();
        $this->infoMissions = new ArrayCollection();
        $this->missions = new ArrayCollection();
        $this->historiques = new ArrayCollection();
        $this->creditHistories = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
        $this->subContractorCompanies = new ArrayCollection();
        $this->devices = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->jobs = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->getFirstname().' '.$this->getLastname();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param mixed $firstname
     */
    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCellPhone(): ?string
    {
        return $this->cellPhone;
    }

    /**
     * @param mixed $cellPhone
     */
    public function setCellPhone(?string $cellPhone): self
    {
        $this->cellPhone = $cellPhone;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param int $enabled
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDailyRate(): ?string
    {
        return $this->dailyRate;
    }

    /**
     * @param mixed $dailyRate
     */
    public function setDailyRate(?string $dailyRate): void
    {
        $this->dailyRate = $dailyRate;
    }

    /**
     * @return mixed
     */
    public function getBillingMethod(): ?string
    {
        return $this->billingMethod;
    }

    /**
     * @param mixed $billingMethod
     */
    public function setBillingMethod(?string $billingMethod): void
    {
        $this->billingMethod = $billingMethod;
    }

    public function serialize()
    {
        $this->picture = base64_encode($this->picture);
    }

    public function unserialize($serialized)
    {
        $this->picture = base64_decode($this->picture);
    }

    /**
     * @return File|null
     */
    public function getPicture(): ?File
    {
        return $this->picture;
    }

    public function setPicture(?File $picture = null): void
    {
        $this->picture = $picture;

        if (null !== $picture) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPictureName(): ?string
    {
        return $this->pictureName;
    }

    /**
     * @param string|null $pictureName
     */
    public function setPictureName(?string $pictureName): void
    {
        $this->pictureName = $pictureName;
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
     */
    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastConnectionAt(): ?\DateTimeInterface
    {
        return $this->lastConnectionAt;
    }

    /**
     * @param \DateTimeInterface|null $lastConnectionAt
     * @return User
     */
    public function setLastConnectionAt(?\DateTimeInterface $lastConnectionAt): User
    {
        $this->lastConnectionAt = $lastConnectionAt;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $password): self
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccountType(): ?string
    {
        return $this->accountType;
    }

    /**
     * @param string|null $accountType
     * @return User
     */
    public function setAccountType(?string $accountType): User
    {
        $this->accountType = $accountType;
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
            $message->setUser($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getUser() === $this) {
                $message->setUser(null);
            }
        }

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
            $infoMission->setUser($this);
        }

        return $this;
    }

    public function removeInfoMission(InfoMission $infoMission): self
    {
        if ($this->infoMissions->removeElement($infoMission)) {
            // set the owning side to null (unless already changed)
            if ($infoMission->getUser() === $this) {
                $infoMission->setUser(null);
            }
        }

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
            $mission->setContact($this);
        }

        return $this;
    }

    public function removeMission(Mission $mission): self
    {
        if ($this->missions->removeElement($mission)) {
            // set the owning side to null (unless already changed)
            if ($mission->getContact() === $this) {
                $mission->setContact(null);
            }
        }

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
            $historique->setUser($this);
        }

        return $this;
    }

    public function removeHistorique(Historique $historique): self
    {
        if ($this->historiques->removeElement($historique)) {
            // set the owning side to null (unless already changed)
            if ($historique->getUser() === $this) {
                $historique->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|SubContractorCompany[]
     */
    public function getSubContractorCompanies(): Collection
    {
        return $this->subContractorCompanies;
    }

    public function addSubContractorCompany(SubContractorCompany $subContractorCompany): self
    {
        if (!$this->subContractorCompanies->contains($subContractorCompany)) {
            $this->subContractorCompanies[] = $subContractorCompany;
            $subContractorCompany->setUser($this);
        }

        return $this;
    }

    public function removeSubContractorCompany(SubContractorCompany $subContractorCompany): self
    {
        if ($this->subContractorCompanies->removeElement($subContractorCompany)) {
            // set the owning side to null (unless already changed)
            if ($subContractorCompany->getUser() === $this) {
                $subContractorCompany->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Device[]
     */
    public function getDevices(): Collection
    {
        return $this->devices;
    }

    public function addDevice(Device $device): self
    {
        if (!$this->devices->contains($device)) {
            $this->devices[] = $device;
            $device->setUser($this);
        }

        return $this;
    }

    public function removeDevice(Device $device): self
    {
        if ($this->devices->removeElement($device)) {
            // set the owning side to null (unless already changed)
            if ($device->getUser() === $this) {
                $device->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Service[]
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services[] = $service;
            $service->setUser($this);
        }

        return $this;
    }

    public function removeService(Service $service): self
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getUser() === $this) {
                $service->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return array|null
     */
    public function getNotificationType(): ?array
    {
        return $this->notificationType;
    }

    /**
     * @param array|null $notificationType
     */
    public function setNotificationType(?array $notificationType): void
    {
        $this->notificationType = $notificationType;
    }

    /**
     * @return array|null
     */
    public function getNotification(): ?array
    {
        return $this->notification;
    }

    /**
     * @param array|null $notification
     */
    public function setNotification(?array $notification): void
    {
        $this->notification = $notification;
    }

    /**
     * @return Collection|Job[]
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    public function addJob(Job $job): self
    {
        if (!$this->jobs->contains($job)) {
            $this->jobs[] = $job;
            $job->addUser($this);
        }

        return $this;
    }

    public function removeJob(Job $job): self
    {
        if ($this->jobs->removeElement($job)) {
            $job->removeUser($this);
        }

        return $this;
    }

    public function getIsActiveNotification(): ?bool
    {
        return $this->isActiveNotification;
    }

    public function setIsActiveNotification(bool $isActiveNotification): self
    {
        $this->isActiveNotification = $isActiveNotification;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFreqNotification(): ?int
    {
        return $this->freqNotification;
    }

    /**
     * @param int|null $freqNotification
     */
    public function setFreqNotification(?int $freqNotification): void
    {
        $this->freqNotification = $freqNotification;
    }

    /**
     * @return float|null
     */
    public function getResaleRate(): ?float
    {
        return $this->resaleRate;
    }

    /**
     * @param float|null $resaleRate
     */
    public function setResaleRate(?float $resaleRate): void
    {
        $this->resaleRate = $resaleRate;
    }

    public function getReferencingConfirmationNotification(): ?bool
    {
        return $this->referencingConfirmationNotification;
    }

    public function setReferencingConfirmationNotification(bool $referencingConfirmationNotification): self
    {
        $this->referencingConfirmationNotification = $referencingConfirmationNotification;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }
}
