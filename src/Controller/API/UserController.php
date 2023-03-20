<?php

namespace App\Controller\API;

use App\Entity\Company;
use App\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    /**
     * Returns the information of the current user
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface|null
     */
    #[Rest\Get('/api/v2/users/me')]
    #[Rest\View(serializerGroups: ['user_read'])]
    #[OA\Response(
        response: 200,
        description: 'Returns the information of the current user',
        content: new Model(type: User::class, groups: ['user_read'])
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
    #[OA\Tag(name: 'Users')]
    public function getMe()
    {
        return $this->getUser();
    }
}
