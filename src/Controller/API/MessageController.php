<?php

namespace App\Controller\API;

use App\Entity\Campaign;
use App\Entity\Message;
use App\Form\MessageType;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends AbstractController
{
    /**
     * Add a message to a campaign
     *
     * @param Campaign $campaign
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Message|\Symfony\Component\Form\FormInterface
     */
    #[Rest\Post('/api/v2/campaigns/{id}/messages')]
    #[Rest\View(statusCode: Response::HTTP_CREATED, serializerGroups: ['message_write'])]
    #[OA\Tag(name: 'Campaigns')]
    #[OA\Parameter(
        name: 'content',
        description: 'The message\'s content',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The message has been created',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Message::class, groups: ['message_write']))
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
        description: 'The campaign doesn\'t exists',
    )]
    public function postMessage(Campaign $campaign, Request $request, EntityManagerInterface $entityManager)
    {
        $message = (new Message())
            ->setUser($this->getUser())
            ->setCampaign($campaign);

        $form = $this->createForm(MessageType::class, $message, ['csrf_protection' => false]);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            return $message;
        } else {
            return $form;
        }
    }
}
