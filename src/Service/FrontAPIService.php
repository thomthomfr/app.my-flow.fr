<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Job;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class FrontAPIService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $parameterBag,
        private FlashBagInterface $flashBag,
        private UploaderHelper $uploaderHelper,
        private UrlHelper $urlHelper,
    ){}

    public function pushSubcontractorToFront(User $subcontractor): void
    {
        $jobs = '';

        foreach ($subcontractor->getSubContractorCompanies() as $role) {
            $jobs .= '|'.$role->getCompany()->getName().$role->getJobs()->first()->getName().$role->getProducts()->first()->getName();
        }

        $jobs .= '|';

        try {
            $response = $this->httpClient->request('POST', $this->parameterBag->get('front_website_api_base_url').'/subcontractors', [
                'body' => [
                    'email' => $subcontractor->getEmail(),
                    'name' => $subcontractor->getFirstname(),
                    'jobs' => $jobs,
                    'profilePicture' => !empty($this->uploaderHelper->asset($subcontractor)) ? $this->urlHelper->getAbsoluteUrl($this->uploaderHelper->asset($subcontractor, 'picture')) : null,
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->flashBag->add(
                    type: 'danger',
                    message: 'Une erreur s\'est produite lors de la synchronisation de l\'intervenant sur le Front. Merci de contacter l\'admin',
                );
            }
        } catch (\Exception $e) {
            $this->flashBag->add(
                type: 'danger',
                message: 'Une erreur s\'est produite lors de la synchronisation de l\'intervenant sur le Front. Merci de contacter l\'admin',
            );
        }
    }

    public function pushCompanyToFront(Company $company): ?array
    {
        try {
            $response = $this->httpClient->request('POST', $this->parameterBag->get('front_website_api_base_url').'/companies', [
                'body' => [
                    'id' => $company->getFrontId(),
                    'name' => $company->getName(),
                    'contract' => $company->getContract(),
                    'backId' => $company->getId(),
                    'discount' => $company->getCustomerDiscount(),
                    'logo' => !empty($this->uploaderHelper->asset($company)) ? $this->urlHelper->getAbsoluteUrl($this->uploaderHelper->asset($company, 'logoFile')) : null,
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->flashBag->add(
                    type: 'danger',
                    message: 'Une erreur s\'est produite lors de la synchronisation de l\'entreprise sur le Front. Merci de contacter l\'admin',
                );
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->flashBag->add(
                type: 'danger',
                message: 'Une erreur s\'est produite lors de la synchronisation de l\'entreprise sur le Front. Merci de contacter l\'admin',
            );
        }

        return null;
    }

    public function pushClientToFront(User $client, ?string $plainPassword): void
    {
        try {
            $response = $this->httpClient->request('POST', $this->parameterBag->get('front_website_api_base_url').'/users', [
                'body' => [
                    'user_pass' => $plainPassword ?? $client->getPassword(),
                    'user_login' => $client->getEmail(),
                    'user_email' => $client->getEmail(),
                    'first_name' => $client->getFirstname(),
                    'last_name' => $client->getLastname(),
                    'company' => $client->getCompany()?->getName() ?? null,
                    'company_discount' => $client->getCompany()->getCustomerDiscount(),
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->flashBag->add(
                    type: 'danger',
                    message: 'Une erreur s\'est produite lors de la synchronisation du client sur le Front. Merci de contacter l\'admin',
                );
            }
        } catch (\Exception $e) {
            $this->flashBag->add(
                type: 'danger',
                message: 'Une erreur s\'est produite lors de la synchronisation du client sur le Front. Merci de contacter l\'admin',
            );
        }
    }

    public function editProductDeliveryTime(Product $product, float $deliveryTime): void
    {
        try {
            $response = $this->httpClient->request('POST', $this->parameterBag->get('front_website_api_base_url').'/products/'.$product->getFrontId(), [
                'body' => [
                    'deliveryTime' => $deliveryTime,
                ],
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->flashBag->add(
                    type: 'danger',
                    message: 'Une erreur s\'est produite lors de la synchronisation du produit sur le Front. Merci de contacter l\'admin',
                );
            }
        } catch (\Exception $e) {
            $this->flashBag->add(
                type: 'danger',
                message: 'Une erreur s\'est produite lors de la synchronisation du produit sur le Front. Merci de contacter l\'admin',
            );
        }
    }

    public function createJobOnFront(Job $job): void
    {
        try {
            $response = $this->httpClient->request('POST', $this->parameterBag->get('front_website_api_base_url').'/jobs', [
                'body' => [
                    'id' => $job->getId(),
                    'name' => $job->getName(),
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->flashBag->add(
                    type: 'danger',
                    message: 'Une erreur s\'est produite lors de la synchronisation du métier sur le Front. Merci de contacter l\'admin',
                );
            }
        } catch (\Exception $e) {
            $this->flashBag->add(
                type: 'danger',
                message: 'Une erreur s\'est produite lors de la synchronisation du métier sur le Front. Merci de contacter l\'admin',
            );
        }
    }

    public function updateJobOnFront(Job $job): void
    {
        try {
            $response = $this->httpClient->request('POST', $this->parameterBag->get('front_website_api_base_url').'/jobs/'.$job->getId(), [
                'body' => [
                    'name' => $job->getName(),
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->flashBag->add(
                    type: 'danger',
                    message: 'Une erreur s\'est produite lors de la synchronisation du métier sur le Front. Merci de contacter l\'admin',
                );
            }
        } catch (\Exception $e) {
            $this->flashBag->add(
                type: 'danger',
                message: 'Une erreur s\'est produite lors de la synchronisation du métier sur le Front. Merci de contacter l\'admin',
            );
        }
    }

    public function deleteJobOnFront(Job $job): void
    {
        try {
            $response = $this->httpClient->request('POST', $this->parameterBag->get('front_website_api_base_url').'/jobs/'.$job->getId().'/supprimer');

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->flashBag->add(
                    type: 'danger',
                    message: 'Une erreur s\'est produite lors de la suppression du métier sur le Front. Merci de contacter l\'admin',
                );
            }
        } catch (\Exception $e) {
            $this->flashBag->add(
                type: 'danger',
                message: 'Une erreur s\'est produite lors de la suppression du métier sur le Front. Merci de contacter l\'admin',
            );
        }
    }
}
