<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use SLLH\IsoCodesValidator\Constraints as IsoCodesAssert;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'companies')]
/**
 * @Vich\Uploadable()
 */
class Company
{
    const PACK_CREDIT = 0;
    const END_OF_MONTH_BILLING = 1;
    const MONTHLY_BILLING = 2;
    const CASH = 3;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    #[Groups(['campaign', 'user_read', 'mission_read'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Ce champ est requis')]
    #[Groups(['user_read', 'mission_read'])]
    private string $name = '';

    /**
     * @Vich\UploadableField(mapping="company_image", fileNameProperty="logoName")
     */
    #[Assert\File(mimeTypes: ['image/png', 'image/jpeg', 'image/jpg'], mimeTypesMessage: 'Les formats supportés sont : PNG, JPEG, JPG')]
    private ?File $logoFile = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['mission_read'])]
    private ?string $logoName = null;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    #[ORM\Column(type: 'boolean')]
    private bool $CbPayment = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Ce champ est requis')]
    #[Groups(['user_read'])]
    private string $contract;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $customerDiscount;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $defaultCreditCost;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $costOfDiscountedCredit;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: User::class, cascade: ["persist"])]
    private $users;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Campaign::class)]
    private $campaigns;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: CreditHistory::class)]
    private $creditHistories;

    #[ORM\Column(type: 'string', length: 14, nullable: true)]
    #[Assert\AtLeastOneOf([
        new IsoCodesAssert\Siren(),
        new IsoCodesAssert\Siret(),
    ], message: 'Le numéro de SIREN ou de SIRET est invalide', includeInternalMessages: false)]
    private $siren;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: SubContractorCompany::class)]
    private $subContractorCompanies;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $frontId = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['user_read'])]
    private $currentCredit;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $currentBalance = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
        $this->users = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
        $this->creditHistories = new ArrayCollection();
        $this->subContractorCompanies = new ArrayCollection();
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

    /**
     * @return File|null
     */
    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    public function setLogoFile(?File $logoFile = null): void
    {
        $this->logoFile = $logoFile;

        if (null !== $logoFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    /**
     * @return string|null
     */
    public function getLogoName(): ?string
    {
        return $this->logoName;
    }

    /**
     * @param string|null $logoName
     */
    public function setLogoName(?string $logoName): void
    {
        $this->logoName = $logoName;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCbPayment(): ?bool
    {
        return $this->CbPayment;
    }

    public function setCbPayment(bool $CbPayment): self
    {
        $this->CbPayment = $CbPayment;

        return $this;
    }

    public function getContract(): ?string
    {
        return $this->contract;
    }

    public function setContract(?string $contract): self
    {
        $this->contract = $contract;

        return $this;
    }

    public function getCustomerDiscount(): ?string
    {
        return $this->customerDiscount;
    }

    public function setCustomerDiscount(?string $customerDiscount): self
    {
        $this->customerDiscount = $customerDiscount;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultCreditCost(): ?string
    {
        return $this->defaultCreditCost;
    }

    /**
     * @param string $defaultCreditCost
     * @return Company
     */
    public function setDefaultCreditCost(string $defaultCreditCost): Company
    {
        $this->defaultCreditCost = $defaultCreditCost;
        return $this;
    }

    /**
     * @return string
     */
    public function getCostOfDiscountedCredit(): string
    {
        return $this->costOfDiscountedCredit;
    }

    /**
     * @param string $costOfDiscountedCredit
     * @return Company
     */
    public function setCostOfDiscountedCredit(string $costOfDiscountedCredit): Company
    {
        $this->costOfDiscountedCredit = $costOfDiscountedCredit;
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
     * @return Company
     */
    public function setCreatedAt(?\DateTimeInterface $createdAt): Company
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeInterface|null $updatedAt
     * @return Company
     */
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): Company
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setCompany($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getCompany() === $this) {
                $user->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CreditHistory[]
     */
    public function getCreditHistories(): Collection
    {
        return $this->creditHistories;
    }

    public function addCreditHistory(CreditHistory $creditHistory): self
    {
        if (!$this->creditHistories->contains($creditHistory)) {
            $this->creditHistories[] = $creditHistory;
            $creditHistory->setCompany($this);
        }

        return $this;
    }

    public function removeCreditHistory(CreditHistory $creditHistory): self
    {
        if ($this->creditHistories->removeElement($creditHistory)) {
            // set the owning side to null (unless already changed)
            if ($creditHistory->getCompany() === $this) {
                $creditHistory->setCompany(null);
            }
        }

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): self
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * @return Collection|Campaign[]
     */
    public function getcampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $Campaign): self
    {
        if (!$this->campaigns->contains($Campaign)) {
            $this->campaigns[] = $Campaign;
            $Campaign->setCompany($this);
        }

        return $this;
    }

    public function removeCampaign(Campaign $Campaign): self
    {
        if ($this->campaigns->removeElement($Campaign)) {
            // set the owning side to null (unless already changed)
            if ($Campaign->getCompany() === $this) {
                $Campaign->setCompany(null);
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
            $subContractorCompany->setCompany($this);
        }

        return $this;
    }

    public function removeSubContractorCompany(SubContractorCompany $subContractorCompany): self
    {
        if ($this->subContractorCompanies->removeElement($subContractorCompany)) {
            // set the owning side to null (unless already changed)
            if ($subContractorCompany->getCompany() === $this) {
                $subContractorCompany->setCompany(null);
            }
        }

        return $this;
    }

    public function getFrontId(): ?int
    {
        return $this->frontId;
    }

    public function setFrontId(?int $frontId): self
    {
        $this->frontId = $frontId;

        return $this;
    }

    public function getCurrentCredit(): ?string
    {
        return $this->currentCredit;
    }

    public function setCurrentCredit(?string $currentCredit): self
    {
        $this->currentCredit = $currentCredit;

        return $this;
    }

    public function getCurrentBalance(): ?int
    {
        return $this->currentBalance ?? 0;
    }

    public function setCurrentBalance(?int $currentBalance): self
    {
        $this->currentBalance = $currentBalance;

        return $this;
    }

    public function addToBalance(?int $credit): self
    {
        $this->currentBalance += $credit;

        return $this;
    }
}
