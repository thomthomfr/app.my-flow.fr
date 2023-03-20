<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class ClientDeleteWpEvent extends Event
{
    public const NAME = 'client.delete.wp';
    protected $urlApi = "https://dev.my-flow.fr/wp-json/my-flow/v1/DeleteMember";
    public function __construct(
        protected User $user,
    ){}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUrlApi() : string {
        return $this->urlApi;
    }
}