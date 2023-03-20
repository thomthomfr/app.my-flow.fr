<?php

namespace App\Event\Chat;

use App\Entity\Message;
use Symfony\Contracts\EventDispatcher\Event;

class MessageSentEvent extends Event
{
    public const NAME = 'message.sent';

    public function __construct(
        protected Message $message,
    ){}

    public function getMessage(): Message
    {
        return $this->message;
    }
}
