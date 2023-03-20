<?php

namespace App\Service;

use App\Entity\NotificationToSend;
use App\Entity\SystemEmail;
use App\Entity\User;
use App\Enum\FreqNotification;
use App\Enum\NotificationType;
use App\Repository\NotificationToSendRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotificationService
{
    public function __construct(
        private ShortcodeService $shortcodeService,
        private MailerInterface $mailer,
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $parameterBag,
        private NotificationToSendRepository $notificationToSendRepository,
        private EntityManagerInterface $entityManager,
    ){}

    public function create(SystemEmail $email, User|string $user, $emailUser = null, $emailCompany = null, $emailStep = null, $emailCampaign = null, $attachments = null, bool $sms = false, bool $both = false): void
    {
       
        if ($user instanceof User && in_array(NotificationType::EMAIL->value, $user->getNotificationType()) && (!$sms || $both)) {
            if (FreqNotification::ALL_NOTIFICATION->value === $user->getFreqNotification()) {
                $this->sendEmail(
                    from: new Address($email->getSender(), $email->getSenderName()),
                    to: new Address($user->getEmail()),
                    subject: $this->shortcodeService->parse($email->getSubject(), $emailUser, $emailCompany, $emailStep, $emailCampaign),
                    content: $this->shortcodeService->parse($email->getContent(), $emailUser, $emailCompany, $emailStep, $emailCampaign),
                    attachments: $attachments,
                );
            } elseif (null !== $user->getFreqNotification() && !$this->notificationToSendRepository->checkIfNotificationAwaiting($user)) {
                if ($user->getFreqNotification() === FreqNotification::ONE_PER_DAY->value) {
                    if (date('Hi') > 1730) {
                        $interval = 1;
                    } else {
                        $interval = 0;
                    }
                } else {
                    $interval = 5 - date('N');

                    if ($interval < 0) {
                        $interval = 7 - abs($interval);
                    }
                }

                $notification = (new NotificationToSend())
                    ->setSendAt((new \DateTimeImmutable())->add(new \DateInterval('P'.$interval.'D'))->setTime(17,30))
                    ->setSendTo($user)
                    ->setType($user->getFreqNotification() === FreqNotification::ONE_PER_DAY->value ? NotificationToSend::DAILY : NotificationToSend::WEEKLY)
                ;

                $this->entityManager->persist($notification);
            }
        } elseif (!($user instanceof User)) {
            $this->sendEmail(
                from: new Address($email->getSender(), $email->getSenderName()),
                to: new Address($user),
                subject: $this->shortcodeService->parse($email->getSubject(), $emailUser, $emailCompany, $emailStep, $emailCampaign),
                content: $this->shortcodeService->parse($email->getContent(), $emailUser, $emailCompany, $emailStep, $emailCampaign),
                attachments: $attachments,
            );
        }

        if ($sms && in_array(NotificationType::SMS->value, $user->getNotificationType()) && !empty($email->getSmsContent()) && !empty($user->getCellPhone()) && FreqNotification::ALL_NOTIFICATION->value === $user->getFreqNotification()) {
            $this->sendSms(
                text: $email->getSmsContent(),
                to: $user->getCellPhone(),
            );
        }
    }

    public function sendEmail(Address $from, Address $to, string $subject, string $content, $attachments)
    {
        $notification = (new NotificationEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->content($content)
            ->markAsPublic()
        ;

	if (null !== $attachments) {
	        foreach ($attachments as $file) {
        	    $notification->attachFromPath($this->parameterBag->get('kernel.project_dir').'/public/uploads/mission/'.$file->getMission()->getId().'/'.$file->getName());
	        }
	}

        try {
            $this->mailer->send($notification);
        } catch (\Exception $e) {}
    }

    public function sendSms(string $text, string $to): void
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
        } catch (\Exception $e) {}
    }
}
