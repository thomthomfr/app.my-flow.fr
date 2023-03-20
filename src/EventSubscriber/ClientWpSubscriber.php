<?php

namespace App\EventSubscriber;
use App\Entity\User;
use App\Event\ClientDeleteWpEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use GuzzleHttp\Client;

class ClientWpSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ClientDeleteWpEvent::NAME => 'onClientDelete',
        ];
    }

    /*
    * delete client wp if client in app is deleted
    */
    public function onClientDelete(ClientDeleteWpEvent $event){
        $user = $event->getUser();
        $apiUrl = $event->getUrlApi();
        if (!$user instanceof User) {
           return;
        }
        //suppression client Wordpress
        $client = new Client();
        $response = $client->request('POST',$apiUrl, [
             'form_params' => [
                    'email' => $user->getEmail(),
                ]
        ]);
    }
}
