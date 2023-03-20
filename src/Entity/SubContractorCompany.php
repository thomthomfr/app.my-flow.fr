<?php

namespace App\Entity;

use App\Repository\SubContractorCompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SubContractorCompanyRepository::class)]
class SubContractorCompany
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 22, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'subContractorCompanies')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'subContractorCompanies')]
    private Company $company;

    #[ORM\ManyToMany(targetEntity: Job::class)]
    private Collection $jobs;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $billingMethod = 0;

    #[ORM\ManyToMany(targetEntity: Product::class, inversedBy: 'subContractorCompanies')]
    private Collection $products;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $emailSend = true;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBase58();
        $this->jobs = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $User): self
    {
        $this->user = $User;

        return $this;
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
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        $this->products->removeElement($product);

        return $this;
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

    /**
     * @return bool|null
     */
    public function getEmailSend(): ?bool
    {
        return $this->emailSend;
    }

    /**
     * @param bool|null $emailSend
     */
    public function setEmailSend(?bool $emailSend): void
    {
        $this->emailSend = $emailSend;
    }

}
