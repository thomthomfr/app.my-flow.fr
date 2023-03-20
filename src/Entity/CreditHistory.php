<?php

namespace App\Entity;

use App\Repository\CreditHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CreditHistoryRepository::class)]
class CreditHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'creditHistories')]
    private $company;

    #[ORM\Column(type: 'datetime')]
    private  ?\DateTimeInterface $createdAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $credit;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mensualite;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $annuite;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $report;

    #[ORM\Column(type: 'string', length: 255)]
    private string $cost;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creditExpiredAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'creditHistories')]
    private $orderedBy;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $typePack;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private \DateTimeInterface $startDateContract;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $Company): self
    {
        $this->company = $Company;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    public function getCredit(): ?int
    {
        return $this->credit;
    }

    public function setCredit(?int $credit): self
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * @return int
     */
    public function getMensualite(): ?int
    {
        return $this->mensualite;
    }

    /**
     * @param int $mensualite
     */
    public function setMensualite(?int $mensualite): self
    {
        $this->mensualite = $mensualite;

        return $this;
    }

    /**
     * @return int
     */
    public function getAnnuite(): ?int
    {
        return $this->annuite;
    }

    /**
     * @param int $annuite
     */
    public function setAnnuite(?int $annuite): self
    {
        $this->annuite = $annuite;

        return $this;
    }


    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(string $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCreditExpiredAt(): ?\DateTimeInterface
    {
        return $this->creditExpiredAt;
    }

    /**
     * @param \DateTimeInterface|null $creditExpiredAt
     * @return CreditHistory
     */
    public function setCreditExpiredAt(?\DateTimeInterface $creditExpiredAt): CreditHistory
    {
        $this->creditExpiredAt = $creditExpiredAt;
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

    public function getTypePack(): ?string
    {
        return $this->typePack;
    }

    public function setTypePack(?string $typePack): self
    {
        $this->typePack = $typePack;

        return $this;
    }

    public function getStartDateContract(): ?\DateTimeInterface
    {
        return $this->startDateContract;
    }

    public function setStartDateContract(?\DateTimeInterface $startDateContract): self
    {
        $this->startDateContract = $startDateContract;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getReport(): ?int
    {
        return $this->report;
    }

    /**
     * @param int|null $report
     */
    public function setReport(?int $report): self
    {
        $this->report = $report;

        return $this;
    }

}
