<?php

namespace App\Controller\API;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CompanyController extends AbstractController
{
    #[Rest\Post('/api/v2/companies/{id}/upload-logo', name: 'api_v2_company_upload_logo')]
    #[Rest\View(statusCode: Response::HTTP_OK)]
    public function postLogo(Company $company, Request $request, EntityManagerInterface $entityManager)
    {
        $company->setLogoFile($request->files->get('logo'));
        $entityManager->flush();

        return '';
    }

    #[Route('/api/companies/{id}/current_balance', name: 'api_companies_get_balance', methods: ['GET'])]
    public function getCompany(Company $company): JsonResponse
    {
        if ($company->getContract() === Company::CASH) {
            return new JsonResponse();
        }

        $text = match ((int) $company->getContract()) {
            Company::PACK_CREDIT => 'Votre solde actuel',
            Company::END_OF_MONTH_BILLING => 'Budget HT consommé depuis le 1er du mois',
            Company::MONTHLY_BILLING => 'Solde sur '.$company->getCreditHistories()->last()->getCredit().'€ HT/mois',
            default => throw new \Exception('Unexpected match value'),
        };

        $currency = match ((int) $company->getContract()) {
            Company::PACK_CREDIT => 'crédits',
            default => '€',
        };

        return new JsonResponse([
            'text' => $text,
            'currency' => $currency,
            'balance' => $company->getCurrentBalance(),
        ]);
    }
}
