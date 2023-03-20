<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SmsService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $parameterBag,
    ){}

    /**
     * Sends an SMS using SMS Factor API
     *
     * @param string $text - the content of the sms to send
     * @param string $to - the number to send the sms to
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function send(string $text, string $to): void
    {
        try {
            $this->httpClient->request('GET', $this->parameterBag->get('sms_api_base_url').'/send', [
                'headers' => [
                    'Accept: application/json',
                ],
                'auth_bearer' => $this->parameterBag->get('sms_api_token'),
                'query' => [
                    'text' => $text,
                    'to' => $to,
                ],
            ]);
        } catch (\Exception $e) {
            // TODO : catch sms sending errors
        }
    }
}
