<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class, method: 'onLogout')]
class LogoutListener implements EventSubscriberInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $target = (null !== $request->query->get('from_front')) ? $this->parameterBag->get('front_website_url') : $this->parameterBag->get('front_website_url').'/wp-login.php?action=logout';

        $response = new RedirectResponse(
            $target,
            RedirectResponse::HTTP_SEE_OTHER
        );
        $event->setResponse($response);
    }
}
