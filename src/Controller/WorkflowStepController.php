<?php

namespace App\Controller;

use App\Entity\Historique;
use App\Enum\Manager;
use App\Event\Workflow\Step\WorkflowStepDeletedEvent;
use App\Event\Workflow\Step\WorkflowStepEnteredEvent;
use App\Event\Workflow\Step\WorkflowStepExitedEvent;
use App\Event\Workflow\Step\WorkflowStepRelaunchedEvent;
use App\Event\Workflow\Step\WorkflowStepReturnedEvent;
use App\Repository\MissionParticipantRepository;
use App\Repository\WorkflowStepRepository;
use App\Service\WorkflowStepService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class WorkflowStepController extends AbstractController
{
    /**
     * Get the step informations in ajax
     *
     * @param string $step the step id to get the informations
     * @param WorkflowStepRepository $workflowStepRepository
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse the step serialized in JSON
     */
    #[Route('/workflows/{workflow}/steps/{step}', name: 'workflow_step_get')]
    public function getAction(string $step, WorkflowStepRepository $workflowStepRepository, SerializerInterface $serializer): JsonResponse
    {
        $step = $workflowStepRepository->find($step);

        if (null === $step) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse([
            'step' => json_decode($serializer->serialize($step, 'json', [AbstractNormalizer::ATTRIBUTES => ['id', 'name', 'completionTime', 'customerDescription', 'supplierDescription', 'manager', 'job' => ['id']]])),
        ]);
    }

    /**
     * Delete a step
     *
     * @param string $workflow the step's workflow id
     * @param string $step the step id to delete
     * @param WorkflowStepRepository $workflowStepRepository
     * @param EntityManagerInterface $entityManager
     *
     * @return RedirectResponse redirects to the workflow view
     */
    #[Route('/workflows/{workflow}/steps/{step}/supprimer', name: 'workflow_step_delete')]
    public function delete(string $workflow, string $step, WorkflowStepRepository $workflowStepRepository, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): RedirectResponse
    {
        $step = $workflowStepRepository->find($step);

        if (null === $step) {
            throw new NotFoundHttpException();
        }

        $entityManager->remove($step);
        $entityManager->flush();

        $workflow = $step->getWorkflow();
        $event = new WorkflowStepDeletedEvent($workflow);
        $dispatcher->dispatch($event, WorkflowStepDeletedEvent::NAME);

        $this->addFlash(
            type: 'success',
            message: 'L\'étape a bien été supprimée',
        );

        return $this->redirectToRoute('workflow_edit', ['id' => $workflow->getId()]);
    }

    #[Route('/workflows/{workflow}/steps/{step}/validate', name: 'workflow_validate_step', methods: ['GET'])]
    public function validate(string $step, WorkflowStepRepository $workflowStepRepository, EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager, WorkflowStepService $workflowStepService, MissionParticipantRepository $missionParticipantRepository): RedirectResponse
    {
        $step = $workflowStepRepository->find($step);

        if (null === $step) {
            throw new NotFoundHttpException();
        }

        if ($workflowStepService->validate($step, $this->getUser())) {
            return $this->redirectToRoute('mission_transition', ['id' => $step->getWorkflow()->getMission()->getId(), 'transition' => 'archive']);
        }

        $this->addFlash(
            type: 'success',
            message: 'Votre validation a bien été pris en compte',
        );

        return $this->redirectToRoute('mission_edit', ['id' => $step->getWorkflow()->getMission()->getId()]);
    }

    #[Route('/workflows/{workflow}/steps/{step}/refuse', name: 'workflow_refuse_step', methods: ['GET'])]
    public function refuse(string $step, WorkflowStepRepository $workflowStepRepository, Request $request, EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager, WorkflowStepService $workflowStepService): RedirectResponse
    {
        $step = $workflowStepRepository->find($step);

        if (null === $step) {
            throw new NotFoundHttpException();
        }

        $workflowStepService->requestChange($step, $this->getUser());

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/workflows/{workflow}/steps/{step}/relaunch', name: 'workflow_relaunch_step', methods: ['GET'])]
    public function relaunch(string $step, WorkflowStepRepository $workflowStepRepository, Request $request, EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager): RedirectResponse
    {
        $step = $workflowStepRepository->find($step);

        if (null === $step) {
            throw new NotFoundHttpException();
        }

        $event = new WorkflowStepRelaunchedEvent($step);
        $dispatcher->dispatch($event, WorkflowStepRelaunchedEvent::NAME);

        $historique = (new Historique())
            ->setUser($this->getUser())
            ->setMission($step->getWorkflow()->getMission())
            ->setMessage($this->getUser().' a relancé le client pour l\'étape '.$step->getName());
        $entityManager->persist($historique);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le client a bien été relancé'
        );

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/workflows/{workflow}/steps/{step}/jump', name: 'workflow_jump_step', methods: ['GET'])]
    public function jump(string $step, WorkflowStepRepository $workflowStepRepository, Request $request, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): RedirectResponse
    {
        $step = $workflowStepRepository->find($step);

        if (null === $step) {
            throw new NotFoundHttpException();
        }

        foreach ($step->getWorkflow()->getSteps() as $previousStep) {
            if ($previousStep->isActive()) {
                $previousStep->setActive(false);
                $previousStep->setEndDate(new \DateTime());

                $event = new WorkflowStepExitedEvent($previousStep);
                $dispatcher->dispatch($event, WorkflowStepExitedEvent::NAME);

                break;
            }
        }

        if (null === $step->getStartDate()) {
            $event = new WorkflowStepEnteredEvent($step);
            $dispatcher->dispatch($event, WorkflowStepEnteredEvent::NAME);
        } else {
            $event = new WorkflowStepReturnedEvent($step);
            $dispatcher->dispatch($event, WorkflowStepReturnedEvent::NAME);
        }

        $step->setActive(true);
        $step->setStartDate(new \DateTime());
        $step->setEndDate(null);

        if ($step->getManager() === Manager::CLIENT) {
            $step->getWorkflow()->getMission()->setStateClient($step->getName());
            $step->getWorkflow()->getMission()->setStateProvider(null);
        } else {
            $step->getWorkflow()->getMission()->setStateProvider($step->getName());
            $step->getWorkflow()->getMission()->setStateClient(null);
        }

        $historique = (new Historique())
            ->setUser($this->getUser())
            ->setMission($step->getWorkflow()->getMission())
            ->setMessage($this->getUser().' a avancé dans le workflow de l\'étape '.$previousStep->getName().' à l\'étape '.$step->getName());
        $entityManager->persist($historique);

        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le workflow a bien été avancé'
        );

        return $this->redirect($request->headers->get('referer'));
    }
}
