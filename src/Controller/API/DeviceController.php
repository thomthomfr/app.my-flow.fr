<?php

namespace App\Controller\API;

use App\Entity\Device;
use App\Form\DeviceType;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceController extends AbstractController
{
    #[Rest\Post('/api/v2/devices')]
    #[Rest\View(statusCode: Response::HTTP_CREATED, serializerGroups: ['device_write'])]
    #[OA\Tag(name: 'Devices')]
    #[OA\Parameter(
        name: 'deviceid',
        description: 'The devices\'s id',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'The device has been created',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Device::class, groups: ['device_write']))
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
    public function postDevice(Request $request, EntityManagerInterface $entityManager)
    {
        $device = (new Device())
            ->setUser($this->getUser());

        $form = $this->createForm(DeviceType::class, $device, ['csrf_protection' => false]);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $entityManager->persist($device);
            $entityManager->flush();

            return $device;
        }

        return $form;
    }
}
