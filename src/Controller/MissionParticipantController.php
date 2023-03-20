<?php

namespace App\Controller;

use App\Entity\Mission;
use App\Entity\MissionParticipant;
use App\Entity\User;
use App\Enum\Role;
use App\Event\ClientUpdatedEvent;
use App\Event\Mission\ContactAddedEvent;
use App\Event\SubContractor\SubContractorMissionAddedEvent;
use App\Form\AddMissionContactType;
use App\Form\AddMissionSubContractorType;
use App\Form\EditSubcontractorParticipantType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MissionParticipantController extends AbstractController
{
    #[Route('/missions/{mission}/participants/{missionParticipant}/supprimer', name: 'mission_participant_delete')]
    public function delete(MissionParticipant $missionParticipant, EntityManagerInterface $entityManager, Request $request): RedirectResponse
    {
        $entityManager->remove($missionParticipant);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le participant a bien été enlevé de la mission'
        );

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/missions/{mission}/participants', name: 'mission_participant_add', methods: ['POST'])]
    public function add(Mission $mission, EntityManagerInterface $entityManager, Request $request, EventDispatcherInterface $dispatcher): RedirectResponse
    {
        $missionParticipant = (new MissionParticipant())
            ->setMission($mission);

        $form = $this->createForm(AddMissionContactType::class, $missionParticipant);
        $form->handleRequest($request);

        $formSubContractor = $this->createForm(AddMissionSubContractorType::class, $missionParticipant);
        $formSubContractor->handleRequest($request);

        if ($formSubContractor->isSubmitted() && $formSubContractor->isValid()){
            $missionParticipant->setRole(Role::ROLE_SUBCONTRACTOR);
            $entityManager->persist($missionParticipant);
            $entityManager->flush();

            $user = $missionParticipant->getUser();
            $event = new SubContractorMissionAddedEvent($user, $mission);
            $dispatcher->dispatch($event, SubContractorMissionAddedEvent::NAME);

            $this->addFlash(
                type: 'success',
                message: 'Le sous-traitant a bien été ajouté à la mission'
            );
            return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
        } elseif ($formSubContractor->isSubmitted()) {
            $errors = $formSubContractor->getErrors(true);
            if (count($errors) === 1 && $errors[0]->getCause()->getCause() instanceof TransformationFailedException) {
                $user = (new User())
                    ->setCompany($mission->getCampaign()->getCompany())
                    ->setEmail($request->request->get('add_mission_sub_contractor_user')['user'])
                    ->setEnabled(false)
                    ->setRoles([Role::ROLE_SUBCONTRACTOR->value]);

                $missionParticipant->setUser($user);

                $entityManager->persist($user);
                $entityManager->persist($missionParticipant);
                $entityManager->flush();

                $event = new ClientUpdatedEvent($user, true);
                $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);

                $this->addFlash(
                    type: 'success',
                    message: 'Le contact client a bien été ajouté à la mission'
                );

                return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
            }

            $this->addFlash(
                type: 'error',
                message: 'Une erreur a eu lieu, merci de réessayer dans quelques instants',
            );

            return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($missionParticipant);
            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'Le contact client a bien été ajouté à la mission'
            );

            $event = new ContactAddedEvent($mission, $missionParticipant->getUser(), $missionParticipant);
            $dispatcher->dispatch($event, ContactAddedEvent::NAME);

            return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            if (count($errors) === 1 && $errors[0]->getCause()->getCause() instanceof TransformationFailedException) {
                $user = (new User())
                    ->setCompany($mission->getCampaign()->getCompany())
                    ->setEmail($request->request->get('add_mission_contact')['user'])
                    ->setEnabled(false)
                    ->setRoles([Role::ROLE_CLIENT->value]);

                $missionParticipant->setUser($user);

                $entityManager->persist($user);
                $entityManager->persist($missionParticipant);
                $entityManager->flush();

                $event = new ClientUpdatedEvent($user, true);
                $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);

                $this->addFlash(
                    type: 'success',
                    message: 'Le contact client a bien été ajouté à la mission'
                );

                return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
            }

            $this->addFlash(
                type: 'error',
                message: 'Une erreur a eu lieu, merci de réessayer dans quelques instants',
            );

            return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
        }

        $entityManager->remove($missionParticipant);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le contact client a bien été ajouté à la mission'
        );

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    #[Route('/missions/{mission}/participants/{missionParticipant}', name: 'mission_participant_edit', methods: ['POST'])]
    public function edit(MissionParticipant $missionParticipant, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $form = $this->createForm(EditSubcontractorParticipantType::class, $missionParticipant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'Le partenaire a bien été mis à jour',
            );

            return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
        }

        $this->addFlash(
            type: 'error',
            message: 'Une erreur s\'est produite',
        );

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }
}
