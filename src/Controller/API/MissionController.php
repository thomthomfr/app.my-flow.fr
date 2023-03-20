<?php

namespace App\Controller\API;

use App\Entity\Mission;
use App\Enum\Role;
use App\Repository\MissionRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[OA\Tag(name: 'Missions')]
class MissionController extends AbstractController
{
    /**
     * List all the missions for the authenticated user
     *
     * @param MissionRepository $missionRepository
     * @param ParamFetcherInterface $paramFetcher
     * @return \App\Entity\Mission[]|float|int|mixed|string
     */
    #[Rest\Get('/api/v2/missions')]
    #[Rest\View(serializerGroups: ['mission_list'])]
    #[Rest\QueryParam(
        name: 'archived',
        requirements: '\d',
        default: 0,
        description: 'Filter the results to show only the archived or cancelled missions. Only accepts 0 (shows missions in progress) or 1 (shows archived missions).'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns all the missions for the authenticated user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Mission::class, groups: ['mission_list']))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized - the user isn\'t logged in',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'code', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ], type: 'object')
        )
    )]
    public function getMissions(MissionRepository $missionRepository, ParamFetcherInterface $paramFetcher)
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $missionRepository->findMissionsFor(Role::ROLE_ADMIN, $this->getUser(), $paramFetcher->get('archived') == 0);
        } elseif ($this->isGranted('ROLE_CLIENT')) {
            return $missionRepository->findMissionsFor(Role::ROLE_CLIENT, $this->getUser(), $paramFetcher->get('archived') == 0);
        }

        return $missionRepository->findMissionsFor(Role::ROLE_SUBCONTRACTOR, $this->getUser(), $paramFetcher->get('archived') == 0);
    }

    /**
     * Get all the details about a mission
     */
    #[Rest\Get('/api/v2/missions/{id}')]
    #[Rest\View(serializerGroups: ['mission_read'])]
    #[OA\Response(
        response: 200,
        description: 'Get all the details about a mission',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Mission::class, groups: ['mission_read']))
        )
    )]
    #[OA\Response(
        response: 401,
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
        response: 404,
        description: 'The mission with id doesn\'t exists',
    )]
    public function getOne(Mission $mission)
    {
        return $mission;
    }
}
