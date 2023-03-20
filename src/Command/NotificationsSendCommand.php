<?php

namespace App\Command;

use App\Entity\NotificationToSend;
use App\Enum\Role;
use App\Repository\CampaignRepository;
use App\Repository\MessageRepository;
use App\Repository\ChatNotificationRepository;
use App\Repository\MissionRepository;
use App\Repository\NotificationToSendRepository;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(
    name: 'notifications:send',
)]
class NotificationsSendCommand extends Command
{
    public function __construct(
        private ChatNotificationRepository $chatNotificationRepository,
        private MessageRepository $messageRepository,
        private RouterInterface $router,
        private MailerInterface $mailer,
        private NotificationToSendRepository $notificationToSendRepository,
        private MissionRepository $missionRepository,
        private CampaignRepository $campaignRepository,
        string $name = null,
    ){
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = $this->router->getContext();
        $context->setHost('app.my-flow.fr');
        $context->setScheme('https');

        $notifications = $this->chatNotificationRepository->toSendNow();

        foreach ($notifications as $notification) {
            $messages = $this->messageRepository->getLast30MinutesFromCampaign($notification->getCampaign());

            $html = '';
            foreach ($messages as $message) {
                $html .= '<p><strong>'.$message->getUser()->getFirstname().'</strong>, le '.$message->getCreatedAt()->format('d/m/Y à H:i').'<br>';
                $html .= nl2br($message->getContent()).'</p>';
            }

            $email = (new NotificationEmail())
                ->to(new Address($notification->getSendTo()->getEmail()))
                ->subject('Des messages ont été déposés sur votre mission')
                ->content('
                    <p>Bonjour,</p>
                    <p>Des nouveaux messages ont été déposés sur votre commande "'.$notification->getCampaign()->getName().'" :</p>
                    '.$html.'
                ')
                ->replyTo('operation@my-flow.fr')
                ->context(['replyTo' => true, 'campaignId' => $notification->getCampaign()?->getId()])
                ->action('Voir la mission', $this->router->generate('mission_edit', ['id' => $notification->getCampaign()->getMissions()->first()->getId()], UrlGeneratorInterface::ABSOLUTE_URL))
                ->markAsPublic()
            ;

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {}
        }

        $notifications = $this->notificationToSendRepository->toSendNow();

        foreach ($notifications as $notification) {
            if (in_array(Role::ROLE_SUBCONTRACTOR->value, $notification->getSendTo()->getRoles())) {
                $missions = $this->missionRepository->findMissionsFor(Role::ROLE_SUBCONTRACTOR, $notification->getSendTo(), true);
            } else {
                $missions = $this->missionRepository->findMissionsFor(Role::ROLE_CLIENT, $notification->getSendTo(), true);
            }
            $missionsHtml = '';
            $campaignIds = [];

            foreach ($missions as $mission) {
                if (!empty($mission->getStateProvider())) {
                    $badge = '
                        <span class="badge bg-state-provider text-custom-blue text-badge">
                            '.$mission->getStateProvider().'
                        </span>
                    ';
                } else {
                    $badge = '
                        <span class="badge bg-info-custom text-white text-badge position-relative me-5">
                            En attente de validation
                        </span>
                    ';
                }
                $missionsHtml .= '
                    <tr>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                            '.$mission->getProduct()->getName().'
                        </td>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                            '.$badge.'
                        </td>
                    </tr>
                ';
                $campaignIds[] = $mission->getCampaign()->getId();
            }

            $missionsHtml = '
                <table border="0" cellpadding="0" cellspacing="0" class="image_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                    <tr>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;" colspan="2">
                            STATUTS DE MISSIONS
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <hr style="height: 5px;background-color: #06065C;display: block;border: 0; margin-top: -15px;" width="100%">
                        </td>
                    </tr>
                    '.$missionsHtml.'
                </table>
            ';

            if ($notification->getType() == NotificationToSend::DAILY) {
                $messages = $this->messageRepository->getLastDayFromCampaigns($campaignIds);
            } else {
                $messages = $this->messageRepository->getLastWeekFromCampaigns($campaignIds);
            }

            $messagesHtml = '';
            foreach ($messages as $message) {
                $campaign = $this->campaignRepository->find($message['id']);
                $messagesHtml .= '
                    <tr>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                            <strong>'.$message[1].' nouveaux commentaires</strong> sur '.$message['name'].'
                        </td>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                            <div align="center">
                                <!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.$this->router->generate('mission_edit', ['id' => $campaign->getMissions()->first()?->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'" style="height:54px;width:223px;v-text-anchor:middle;" arcsize="0%" strokeweight="0.75pt" strokecolor="#f975c4" fillcolor="#f975c4"><w:anchorlock/><v:textbox inset="0px,0px,0px,0px"><center style="color:#ffffff; font-family:Arial, sans-serif; font-size:14px"><![endif]--><a href="'.$this->router->generate('mission_edit', ['id' => $campaign->getMissions()->first()?->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'" style="text-decoration:none;display:inline-block;color:#ffffff;background-color:#f975c4;border-radius:0px;width:auto;border-top:1px solid #f975c4;border-right:1px solid #f975c4;border-bottom:1px solid #f975c4;border-left:1px solid #f975c4;padding-top:10px;padding-bottom:10px;font-family:"Cabin", Arial, "Helvetica Neue", Helvetica, sans-serif;text-align:center;mso-border-alt:none;word-break:keep-all;" target="_blank"><span style="padding-left:40px;padding-right:40px;font-size:14px;display:inline-block;letter-spacing:normal;"><span style="font-size: 16px; line-height: 2; word-break: break-word; mso-line-height-alt: 32px;"><span data-mce-style="font-size: 14px; line-height: 28px;" style="font-size: 14px; line-height: 28px;">Lire</span></span></span></a>
                                <!--[if mso]></center></v:textbox></v:roundrect><![endif]-->
                            </div>
                        </td>
                    </tr>
                ';
            }

            $messagesHtml = '
                <table border="0" cellpadding="0" cellspacing="0" class="image_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                    <tr>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;" colspan="2">
                            MESSAGES
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <hr style="height: 5px;background-color: #06065C;display: block;border: 0; margin-top: -15px;" width="100%">
                        </td>
                    </tr>
                    '.$messagesHtml.'
                </table>
            ';

            $email = (new NotificationEmail())
                ->to(new Address($notification->getSendTo()->getEmail()))
                ->subject('Récapitulatif myFlow')
                ->content('
                    <table border="0" cellpadding="0" cellspacing="0" class="image_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                    <tr>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;text-align: center">
                            LES NOUVELLES '.($notification->getType() == NotificationToSend::DAILY ? "DU JOUR" : "DE LA SEMAINE").'
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <hr style="height: 5px;background-color: #06065C;display: block;border: 0; margin-top: -15px;" width="100%">
                        </td>
                    </tr>
                </table>
                <p>Bonjour '.$notification->getSendTo()->getFirstname().'</p>
                <p>Retrouvez ci-dessous les dernières mises à jour sur vos projets.</p>
                '.$messagesHtml.'
                '.$missionsHtml.'
                ')
                ->action('J\'accède à mon tableau de bord', $this->router->generate('mission_index', [], UrlGeneratorInterface::ABSOLUTE_URL))
                ->markAsPublic()
            ;

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {}
        }

        return Command::SUCCESS;
    }
}
