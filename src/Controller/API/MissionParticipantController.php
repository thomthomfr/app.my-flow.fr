<?php

namespace App\Controller\API;

use App\Entity\Mission;
use App\Entity\MissionParticipant;
use App\Entity\User;
use App\Enum\Role;
use App\Event\ClientUpdatedEvent;
use App\Form\AddMissionContactType;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MissionParticipantController extends AbstractController
{
    /**
     * Add a participant to the mission. Creates a new user if the participant's email address doesn't exist in the database
     *
     * @param Mission $mission - The mission's ID to which to add the participant
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param EventDispatcherInterface $dispatcher
     * @return mixed
     */
    #[Rest\Post('/api/v2/missions/{id}/participants')]
    #[Rest\View(statusCode: Response::HTTP_CREATED, serializerGroups: ['mission_participant_write'])]
    #[OA\Tag(name: 'Missions')]
    #[OA\Parameter(
        name: 'firstname',
        description: 'The participant\'s firstname',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'lastname',
        description: 'The participant\'s lastname',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'user',
        description: 'The participant\'s email address',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'phone',
        description: 'The participant\'s phone number',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'role',
        description: 'The participant\'s role in the mission. Can only be "ROLE_VALIDATOR" or "ROLE_OBSERVER"',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The participant has been added to the mission',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: MissionParticipant::class, groups: ['mission_participant_write']))
        )
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'The form is invalid and contains errors',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'code', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'children', type: 'array', items: new OA\Items(properties: [])),
                ])),
            ], type: 'object')
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
        description: 'The mission doesn\'t exists',
    )]
    public function postParticipant(Mission $mission, Request $request, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
    {
        $missionParticipant = (new MissionParticipant())
            ->setMission($mission);

        $form = $this->createForm(AddMissionContactType::class, $missionParticipant, ['api' => true, 'csrf_protection' => false]);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $entityManager->persist($missionParticipant);
            $entityManager->flush();

            return $missionParticipant;
        } else {
            $errors = $form->getErrors(true);
            if (count($errors) === 1 && $errors[0]->getCause()->getCause() instanceof TransformationFailedException) {
                $user = (new User())
                    ->setCompany($mission->getCampaign()->getCompany())
                    ->setEmail($request->request->get('user'))
                    ->setFirstname($form->get('firstname')->getData())
                    ->setLastname($form->get('lastname')->getData())
                    ->setCellPhone($form->get('phone')->getData())
                    ->setEnabled(false)
                    ->setRoles([Role::ROLE_CLIENT->value]);

                $missionParticipant->setUser($user);

                $entityManager->persist($user);
                $entityManager->persist($missionParticipant);
                $entityManager->flush();

                $event = new ClientUpdatedEvent($user, true);
                $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);

                return $missionParticipant;
            }

            return $form;
        }
    }

    /**
     * Updates a participant
     */
    #[Rest\Patch('/api/v2/missions/{mission}/participants/{participant}')]
    #[Rest\View(statusCode: Response::HTTP_CREATED, serializerGroups: ['mission_participant_write'])]
    #[OA\Tag(name: 'Missions')]
    #[OA\Parameter(
        name: 'role',
        description: 'The participant\'s role in the mission. Can only be "ROLE_VALIDATOR" or "ROLE_OBSERVER"',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The participant has been updated',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: MissionParticipant::class, groups: ['mission_participant_write']))
        )
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'The form is invalid and contains errors',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'code', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'children', type: 'array', items: new OA\Items(properties: [])),
                ])),
            ], type: 'object')
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
        description: 'The mission or the participant doesn\'t exists',
    )]
    public function patchParticipant(Mission $mission, MissionParticipant $participant, Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(AddMissionContactType::class, $participant, ['api' => true, 'csrf_protection' => false]);
        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            $entityManager->flush();

            return $participant;
        } else {
            return $form;
        }
    }
}
