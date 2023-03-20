<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\Invoice;
use App\Form\TotalCostCampaignType;
use App\Form\UploadInvoiceType;
use App\Repository\CampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AccountingController extends AbstractController
{
    #[Route('/comptabilite', name: 'accounting_index')]
    public function index(Invoice $invoice = null, Request $request, CampaignRepository $campaignRepository, Campaign $campaign = null, EntityManagerInterface $entityManager): Response
    {
        $campaigns = [];
        $price = [];

            foreach ($campaignRepository->findBy([], ['createdAt' => 'DESC']) as $campaign) {
            if (!isset($campaigns[$campaign->getCreatedAt()->format('Y-m-01')])) {
                $campaigns[$campaign->getCreatedAt()->format('Y-m-01')] = [];
            }
            $campaigns[$campaign->getCreatedAt()->format('Y-m-01')][] = $campaign;
            foreach ($campaign->getMissions() as $mission){
                if (!isset($campaigns[$campaign->getCreatedAt()->format('Y-m-01')])) {
                    $price[$campaign->getCreatedAt()->format('Y-m-01')][] = [];
                }
                if ($campaign->getInvoiced() == false){
                    $price[$campaign->getCreatedAt()->format('Y-m-01')][] = $mission->getPrice();
                }else{
                    $price[$campaign->getCreatedAt()->format('Y-m-01')][] = null;
                }
            }

        }
        $formTotalCost = $this->createForm(TotalCostCampaignType::class, $campaign);
        $form = $this->createForm(UploadInvoiceType::class, $invoice);

        return $this->renderForm('accounting/index.html.twig', [
            'form' => $form,
            'formTotalCost' => $formTotalCost,
            'campaigns' => $campaigns,
            'price' => $price,
        ]);
    }

    #[Route('/comptabilite/campagnes/{id}/change-total-cost', name: 'accounting_total_cost')]
    public function changeTotalCost(Request $request, Campaign $campaign, EntityManagerInterface $entityManager)
    {
        $formTotalCost = $this->createForm(TotalCostCampaignType::class, $campaign);
        $formTotalCost->handleRequest($request);
            $campaign->setTotalCostCampaign($request->request->get('total_cost_campaign'));

            $entityManager->persist($campaign);
            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'La montant total a bien été enregistrée',
            );


        return $this->redirectToRoute('accounting_index');
    }

    #[Route('/comptabilite/campagnes/{id}/upload-facture', name: 'accounting_upload')]
    public function upload(Request $request, Campaign $campaign, EntityManagerInterface $entityManager)
    {
        $invoice = new Invoice();
        $form = $this->createForm(UploadInvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaign->addInvoice($invoice);
            $campaign->setInvoiced(true);

            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'La facture a bien été enregistrée',
            );
        }

        return $this->redirectToRoute('accounting_index');
    }
}
