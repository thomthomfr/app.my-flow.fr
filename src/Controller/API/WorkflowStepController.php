<?php

namespace App\Controller\API;

use App\Entity\WorkflowStep;
use App\Repository\MissionParticipantRepository;
use App\Service\WorkflowStepService;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class WorkflowStepController extends AbstractController
{
    /**
     * Validates a specific step
     *
     * @param WorkflowStep $step
     * @param WorkflowStepService $workflowStepService
     * @return WorkflowStep
     */
    #[Rest\Post('/api/v2/workflows/{workflow}/steps/{step}/validate')]
    #[Rest\View(serializerGroups: ['step_write'])]
    #[OA\Tag(name: 'Workflow Step')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The step has been validated',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: WorkflowStep::class, groups: ['step_write']))
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'Unauthorized - the user isn\'t logged in',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'code', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ], type: 'object')
        )
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'The workflow or the step doesn\'t exists',
    )]
    public function validate(WorkflowStep $step, WorkflowStepService $workflowStepService, MissionParticipantRepository $missionParticipantRepository, EntityManagerInterface $entityManager)
    {
        $workflowStepService->validate($step, $this->getUser());

        return $step;
    }

    /**
     * Request changes for a specific step
     *
     * @param WorkflowStep $step
     * @param WorkflowStepService $workflowStepService
     * @return WorkflowStep
     */
    #[Rest\Post('/api/v2/workflows/{workflow}/steps/{step}/request-change')]
    #[Rest\View(serializerGroups: ['step_write'])]
    #[OA\Tag(name: 'Workflow Step')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The changes has been requested',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: WorkflowStep::class, groups: ['step_write']))
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'Unauthorized - the user isn\'t logged in',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'code', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ], type: 'object')
        )
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'The workflow or the step doesn\'t exists',
    )]
    public function requestChange(WorkflowStep $step, WorkflowStepService $workflowStepService)
    {
        $workflowStepService->requestChange($step, $this->getUser());

        return $step;
    }
}
