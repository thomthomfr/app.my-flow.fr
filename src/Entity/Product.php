<?php

namespace App\Entity;

use App\Enum\ProductType;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['mission_list', 'mission_read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['mission_list', 'mission_read'])]
    private string $name = '';

    #[ORM\Column(type: 'integer')]
    private int $frontId;

    #[ORM\Column(type: 'boolean')]
    private bool $deleted = false;

    #[ORM\ManyToMany(targetEntity: SubContractorCompany::class, mappedBy: 'products')]
    private Collection $subContractorCompanies;

    #[ORM\Column(type: 'smallint')]
    private ?int $type = null;

    #[ORM\ManyToMany(targetEntity: Job::class, inversedBy: 'products')]
    private ?Collection $jobs;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $price = null;

    public function __construct()
    {
        $this->subContractorCompanies = new ArrayCollection();
        $this->jobs = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
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

    public function getFrontId(): ?int
    {
        return $this->frontId;
    }

    public function setFrontId(int $frontId): self
    {
        $this->frontId = $frontId;

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
            $subContractorCompany->addProduct($this);
        }

        return $this;
    }

    public function removeSubContractorCompany(SubContractorCompany $subContractorCompany): self
    {
        if ($this->subContractorCompanies->removeElement($subContractorCompany)) {
            $subContractorCompany->removeProduct($this);
        }

        return $this;
    }

    public function getType(): ?ProductType
    {
        return ProductType::tryFrom($this->type);
    }

    public function setType(?ProductType $type): self
    {
        $this->type = $type->value;

        return $this;
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
        }

        return $this;
    }

    public function removeJob(Job $job): self
    {
        $this->jobs->removeElement($job);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatut(): ?string
    {
        return $this->statut;
    }

    /**
     * @param string|null $statut
     */
    public function setStatut(?string $statut): void
    {
        $this->statut = $statut;
    }

    public function getPrice(): ?string
    {
        if (empty(trim($this->price))) {
            return null;
        }

        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

        return $this;
    }

}
