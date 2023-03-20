<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class ClientUpdatedEvent extends Event
{
    public const NAME = 'client.updated';

    public function __construct(
        protected User $client,
        protected bool $sendNotification = false,
        protected ?string $plainPassword = null,
        protected bool $thankYouNotification = false,
        protected bool $sendToApi = true,
    ){}

    public function getClient(): User
    {
        return $this->client;
    }

    public function getSendNotification(): bool
    {
        return $this->sendNotification;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function getThankYouNotification(): bool
    {
        return $this->thankYouNotification;
    }

    public function getSendToApi(): bool
    {
        return $this->sendToApi;
    }
}
