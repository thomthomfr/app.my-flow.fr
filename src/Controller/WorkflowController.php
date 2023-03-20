<?php

namespace App\Controller;

use App\Entity\Historique;
use App\Entity\Workflow;
use App\Entity\WorkflowAction;
use App\Entity\WorkflowStep;
use App\Event\Workflow\Step\WorkflowStepEditedEvent;
use App\Form\WorkflowActionType;
use App\Form\WorkflowStepType;
use App\Form\WorkflowType;
use App\Repository\WorkflowActionRepository;
use App\Repository\WorkflowRepository;
use App\Repository\WorkflowStepRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/workflows')]
class WorkflowController extends AbstractController
{
    /**
     * Displays the index view of the templates
     *
     * @param WorkflowRepository $workflowRepository
     *
     * @return Response template /workflow/index.html.twig
     */
    #[Route('', name: 'workflow_index')]
    public function index(WorkflowRepository $workflowRepository): Response
    {
        return $this->render('workflow/index.html.twig', [
            'workflows' => $workflowRepository->findBy(['template' => true], ['name' => 'ASC']),
        ]);
    }

    /**
     * Displays the handle view for the workflows
     *
     * @param Workflow|null $workflow if defined, the workflow to edit
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param WorkflowActionRepository $workflowActionRepository
     * @param WorkflowStepRepository $workflowStepRepository
     *
     * @return Response template /workflow/handle.html.twig
     */
    #[Route('/ajouter', name: 'workflow_new')]
    #[Route('/{id}', name: 'workflow_edit')]
    public function handle(Workflow $workflow = null, Request $request, EntityManagerInterface $entityManager, WorkflowActionRepository $workflowActionRepository, WorkflowStepRepository $workflowStepRepository, EventDispatcherInterface $dispatcher): Response
    {
        if (null === $workflow) {
            $workflow = (new Workflow())
                ->setTemplate(true);
        }

        /*
         * Handling the workflow informations form
         */
        $form = $this->createForm(WorkflowType::class, $workflow);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($workflow->getTemplate() === false && null !== $workflow->getMission()) {
                $historique = (new Historique())
                    ->setUser($this->getUser())
                    ->setMission($workflow->getMission())
                    ->setMessage($this->getUser().' a modifié le workflow');
                $entityManager->persist($historique);
            }

            $entityManager->persist($workflow);
            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'Le workflow a bien été enregistré',
            );

            return $this->redirectToRoute('workflow_edit', ['id' => $workflow->getId()]);
        } elseif ($form->isSubmitted()) {
            $this->addFlash(
                type: 'danger',
                message: 'Merci de corriger les erreurs',
            );
        }

        /*
         * Handling the new step form
         *
         * If the form is submitted when editing a step, we load it and throw a bad request exception if the step is not found
         * If not, a new step is created
         */
        if (null !== $request->request->get('workflow_step') && isset($request->request->get('workflow_step')['stepId']) && !empty($request->request->get('workflow_step')['stepId'])) {
            $step = $workflowStepRepository->find($request->request->get('workflow_step')['stepId']);

            if (null === $step) {
                throw new BadRequestException();
            }
        } else {
            $step = new WorkflowStep();
        }
        $step->setWorkflow($workflow);
        $addStepForm = $this->createForm(WorkflowStepType::class, $step);
        $addStepForm->handleRequest($request);

        if ($addStepForm->isSubmitted() && $addStepForm->isValid()) {
            $entityManager->persist($step);
            $entityManager->flush();

            $event = new WorkflowStepEditedEvent($step);
            $dispatcher->dispatch($event, WorkflowStepEditedEvent::NAME);

            $this->addFlash(
                type: 'success',
                message: 'L\'étape a bien été enregistrée',
            );

            return $this->redirectToRoute('workflow_edit', ['id' => $workflow->getId()]);
        } elseif ($addStepForm->isSubmitted()) {
            $this->addFlash(
                type: 'danger',
                message: 'Merci de corriger les erreurs',
            );
        }

        /*
         * Handling the new action form
         *
         * If the form is submitted when editing an action, we load it and throw a bad request exception if the action is not found
         * If not, a new action is created
         */
        if (null !== $request->request->get('workflow_action') && isset($request->request->get('workflow_action')['actionId']) && !empty($request->request->get('workflow_action')['actionId'])) {
            $action = $workflowActionRepository->find($request->request->get('workflow_action')['actionId']);

            if (null === $action) {
                throw new BadRequestException();
            }
        } else {
            $action = new WorkflowAction();
        }
        $addActionForm = $this->createForm(WorkflowActionType::class, $action, ['product' => $workflow->getProduct()]);
        $addActionForm->handleRequest($request);

        if ($addActionForm->isSubmitted() && $addActionForm->isValid()) {
            $entityManager->persist($action);
            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'Le workflow a bien été enregistré',
            );

            return $this->redirectToRoute('workflow_edit', ['id' => $workflow->getId()]);
        } elseif ($addStepForm->isSubmitted()) {
            $this->addFlash(
                type: 'danger',
                message: 'Merci de corriger les erreurs',
            );
        }

        return $this->renderForm('workflow/handle.html.twig', [
            'workflow' => $workflow,
            'form' => $form,
            'addStepForm' => $addStepForm,
            'addActionForm' => $addActionForm,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'workflow_delete' , methods: ['GET'])]
    public function delete(Workflow $workflow, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($workflow);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le workflow a bien été supprimé',
        );

        return $this->redirectToRoute('workflow_index');
    }

    #[Route('/{id}/dupliquer', name: 'workflow_duplicate', methods: ['GET'])]
    public function duplicate(Workflow $workflow, EntityManagerInterface $entityManager)
    {
        $workflowClone = clone $workflow;
        $workflowClone->setName($workflowClone->getName().'_clone');
        $entityManager->persist($workflowClone);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le workflow a bien été cloné',
        );

        return $this->redirectToRoute('workflow_index');
    }
}
