<?php

namespace App\Controller;

use App\Entity\SystemEmail;
use App\Form\SystemEmailType;
use App\Repository\SystemEmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/emails/systeme')]
class SystemEmailController extends AbstractController
{
    /**
     * Display the system emails list
     *
     * @param SystemEmailRepository $systemEmailRepository
     *
     * @return Response - template system_email/index.html.twig
     */
    #[Route('/', name: 'system_email_index', methods: ['GET'])]
    public function index(SystemEmailRepository $systemEmailRepository): Response
    {
        return $this->render('system_email/index.html.twig', [
            'systemEmails' => $systemEmailRepository->findAll(),
        ]);
    }

    /**
     * Display and handle the system email's edit form
     *
     * @param SystemEmail $systemEmail - The email to modify
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     *
     * @return Response - template system_email/edit.html.twig
     */
    #[Route('/{id}', name: 'system_email_edit', methods: ['GET', 'POST'])]
    public function edit(SystemEmail $systemEmail, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SystemEmailType::class, $systemEmail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($systemEmail);
            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'Le message a bien été enregistré',
            );

            return $this->redirectToRoute('system_email_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('system_email/edit.html.twig', [
            'email' => $systemEmail,
            'form' => $form,
        ]);
    }
}
