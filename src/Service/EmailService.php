<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Company;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function inviteUserToCompany(User $user, Company $company)
    {
        $message = (new TemplatedEmail())
            ->from(new Address('no-reply@my-flow.fr', 'myFlow'))
            ->to($user->getEmail())
            ->subject('Vous avez reçu une invitation à rejoindre une entreprise')
            ->htmlTemplate('emails/inviteUserCompany.html.twig')
            ->context([
                'user' => $user,
                'company' => $company,
            ]);

        try {
            $this->mailer->send($message);
        } catch (\Exception $e) { /* TODO: logger ou afficher une alerte que l'email n'a pas été envoyé */ }
    }
}
