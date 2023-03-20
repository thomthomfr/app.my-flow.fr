<?php

namespace App\Controller;

use App\Repository\WorkflowActionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class WorkflowActionController extends AbstractController
{
    /**
     * Get the action informations in ajax
     *
     * @param string $action the action id to get the informations
     * @param WorkflowActionRepository $workflowActionRepository
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse the action serialized in JSON
     */
    #[Route('/workflows/{workflow}/steps/{step}/actions/{action}', name: 'workflow_action_get')]
    public function getAction(string $action, WorkflowActionRepository $workflowActionRepository, SerializerInterface $serializer): JsonResponse
    {
        $action = $workflowActionRepository->find($action);

        if (null === $action) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse([
            'action' => json_decode($serializer->serialize($action, 'json', [
                AbstractNormalizer::ATTRIBUTES => [
                    'id',
                    'name',
                    'recipient',
                    'job' => [
                        'id'
                    ],
                    'step' => [
                        'id'
                    ],
                    'triggers' => [
                        'triggerType',
                        'operator',
                        'timePeriod',
                        'operation',
                        'emailTemplate' => [
                            'id'
                        ],
                        'childs' => [
                            'triggerType',
                            'operator',
                            'timePeriod',
                            'operation',
                            'emailTemplate' => [
                                'id'
                            ],
                        ]
                    ]
                ]
            ])),
        ]);
    }

    /**
     * Delete an action
     *
     * @param string $workflow the actions's workflow id
     * @param string $action the action id to delete
     * @param WorkflowActionRepository $workflowActionRepository
     * @param EntityManagerInterface $entityManager
     *
     * @return RedirectResponse redirects to the workflow view
     */
    #[Route('/workflows/{workflow}/steps/{step}/actions/{action}/supprimer', name: 'workflow_action_delete')]
    public function delete(string $workflow, string $action, WorkflowActionRepository $workflowActionRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $action = $workflowActionRepository->find($action);

        if (null === $action) {
            throw new NotFoundHttpException();
        }

        $entityManager->remove($action);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'L\'action a bien été supprimée',
        );

        return $this->redirectToRoute('workflow_edit', ['id' => $workflow]);
    }
}
