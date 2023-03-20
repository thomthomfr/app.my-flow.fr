<?php

namespace App\Controller;

use App\Entity\EmailTemplate;
use App\Form\EmailTemplateType;
use App\Repository\EmailTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @param EmailTemplate $emailTemplate
 * @param Request $request
 * @return Response
 */
#[Route('/admin/email')]
class EmailTemplateController extends AbstractController
{
    /**
     * @param EmailTemplateRepository $emailTemplateRepository
     * @return Response
     */
    #[Route('/templates', name: 'email_template_index', methods: ['GET'])]
    public function index(EmailTemplateRepository $emailTemplateRepository): Response
    {
        return $this->render('email_template/index.html.twig', [
            'emailTemplates' => $emailTemplateRepository->findAll(),
        ]);
    }

    /**
     * @param EmailTemplate $emailTemplate
     * @param Request $request
     * @return Response
     */
    #[Route('/ajouter', name: 'email_new', methods: ['GET','POST'])]
    #[Route('/{id}', name: 'email_edit', methods: ['GET','POST'])]
    public function handleMail(EmailTemplate $emailTemplate = null, Request $request): Response
    {
        if ($emailTemplate === null){
            $emailTemplate = new EmailTemplate();
        }

        $form = $this->createForm(EmailTemplateType::class, $emailTemplate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            if($request->get('_route') === 'email_new') {
                $entityManager->persist($emailTemplate);
                $this->addFlash('success', 'Le Template a bien été ajouté');
            } else {
                $this->addFlash('success', 'Le Template a bien été modifié');
            }
            $entityManager->flush();
            return $this->redirectToRoute('email_template_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('email_template/handle.html.twig', [
            'form' => $form,
            'template' => $emailTemplate
        ]);
    }

    #[Route('/{id}/supprimer', name: 'email_delete', methods: ['GET','POST'])]
    public function deleteMail(EmailTemplate $emailTemplate, EntityManagerInterface $entityManager): Response
    {
        try {
            $entityManager->remove($emailTemplate);
            $entityManager->flush();

            $this->addFlash('success', 'Le template a bien été supprimé');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Le template est utilisé dans un workflow, il est impossible de le supprimer');
        }

        return $this->redirectToRoute('email_template_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{active<desactiver|activer>}', name: 'email_disabled', methods: ['GET','POST'])]
    public function disabledMail(EmailTemplate $emailTemplate, EntityManagerInterface $entityManager): Response
    {
        $emailTemplate->setActive(!$emailTemplate->isActive());
        $entityManager->flush();

        $this->addFlash('success', 'Le Template a bien été '. ($emailTemplate->isActive() ? 'activé' : 'désactivé'));
        return $this->redirectToRoute('email_template_index', [], Response::HTTP_SEE_OTHER);
    }

}
