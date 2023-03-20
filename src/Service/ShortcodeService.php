<?php

namespace App\Service;

use App\Entity\Campaign;
use App\Entity\Company;
use App\Entity\User;
use App\Entity\WorkflowStep;
use App\Enum\ProductType;
use App\Enum\Role;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ShortcodeService
{
    public function __construct(
        private RouterInterface $router,
        private ParameterBagInterface $parameterBag,
    ){}

    public function parse(string $content, User|string|null $user = null, ?Company $company = null, WorkflowStep|bool|null $step = null, ?Campaign $campaign = null)
    {
        if ($user instanceof User) {
            $content = preg_replace('#\[prenom\]#', $user->getFirstname(), $content);
            $content = preg_replace('#\[nom\]#', $user->getLastname(), $content);
            $content = preg_replace('#_lien_activation_profil_client_#', $this->parameterBag->get('front_website_url').'/creez-votre-compte-client/?token='.$user->getId(), $content);
            $content = preg_replace('#_lien_activation_profil_sous_traitant_#', $this->parameterBag->get('front_website_url').'/creer-votre-compte/?token='.$user->getId(), $content);
        }

        if ($user instanceof User && null !== $user->getGender()) {
            $content = preg_replace('#\[civilite\]#', $user->getGender(), $content);
        }

        if (null !== $company) {
            $content = preg_replace('#\[entreprise\]#', $company->getName(), $content);
        }

        $content = preg_replace('#\[cta_primaire text="([^"]+)" href="([^"]+)"\]#', '<a href="${2}" style="text-decoration:none;display:inline-block;color:#ffffff;background-color:#f975c4;border-radius:0px;width:auto;border-top:1px solid #f975c4;border-right:1px solid #f975c4;border-bottom:1px solid #f975c4;border-left:1px solid #f975c4;padding-top:10px;padding-bottom:10px;font-family:\'Cabin\', Arial, \'Helvetica Neue\', Helvetica, sans-serif;text-align:center;mso-border-alt:none;word-break:keep-all;" target="_blank"><span style="padding-left:40px;padding-right:40px;font-size:14px;display:inline-block;letter-spacing:normal;"><span style="font-size: 16px; line-height: 2; word-break: break-word; mso-line-height-alt: 32px;"><span data-mce-style="font-size: 14px; line-height: 28px;" style="font-size: 14px; line-height: 28px;">${1}</span></span></span></a>', $content);
        $content = preg_replace('#\[cta_secondaire text="([^"]+)" href="([^"]+)"\]#', '<a href="${2}" style="text-decoration:none;display:inline-block;color:#ffffff;background-color:#08085C;border-radius:0px;width:auto;border-top:1px solid #08085C;border-right:1px solid #08085C;border-bottom:1px solid #08085C;border-left:1px solid #08085C;padding-top:10px;padding-bottom:10px;font-family:\'Cabin\', Arial, \'Helvetica Neue\', Helvetica, sans-serif;text-align:center;mso-border-alt:none;word-break:keep-all;" target="_blank"><span style="padding-left:40px;padding-right:40px;font-size:14px;display:inline-block;letter-spacing:normal;"><span style="font-size: 16px; line-height: 2; word-break: break-word; mso-line-height-alt: 32px;"><span data-mce-style="font-size: 14px; line-height: 28px;" style="font-size: 14px; line-height: 28px;">${1}</span></span></span></a>', $content);

        if ($step === false) {
            $step = null;
        }

        if (null !== $step || null !== $campaign) {
            $campaign ??= $step->getWorkflow()->getMission()->getCampaign();

            if (null !== $step) {
                $mission = $step->getWorkflow()->getMission();

                $content = preg_replace('#\[valider_etape\]#', '<a href="'.$this->router->generate('workflow_validate_step', ['workflow' => $step->getWorkflow()->getId(), 'step' => $step->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'" style="text-decoration:none;display:inline-block;color:#ffffff;background-color:#f975c4;border-radius:0px;width:auto;border-top:1px solid #f975c4;border-right:1px solid #f975c4;border-bottom:1px solid #f975c4;border-left:1px solid #f975c4;padding-top:10px;padding-bottom:10px;font-family:\'Cabin\', Arial, \'Helvetica Neue\', Helvetica, sans-serif;text-align:center;mso-border-alt:none;word-break:keep-all;" target="_blank"><span style="padding-left:40px;padding-right:40px;font-size:14px;display:inline-block;letter-spacing:normal;"><span style="font-size: 16px; line-height: 2; word-break: break-word; mso-line-height-alt: 32px;"><span data-mce-style="font-size: 14px; line-height: 28px;" style="font-size: 14px; line-height: 28px;">Je valide l\'étape</span></span></span></a>', $content);
                $content = preg_replace('#\[demande_modification\]#', '<a href="'.$this->router->generate('workflow_refuse_step', ['workflow' => $step->getWorkflow()->getId(), 'step' => $step->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'" style="text-decoration:none;display:inline-block;color:#ffffff;background-color:#08085C;border-radius:0px;width:auto;border-top:1px solid #08085C;border-right:1px solid #08085C;border-bottom:1px solid #08085C;border-left:1px solid #08085C;padding-top:10px;padding-bottom:10px;font-family:\'Cabin\', Arial, \'Helvetica Neue\', Helvetica, sans-serif;text-align:center;mso-border-alt:none;word-break:keep-all;" target="_blank"><span style="padding-left:40px;padding-right:40px;font-size:14px;display:inline-block;letter-spacing:normal;"><span style="font-size: 16px; line-height: 2; word-break: break-word; mso-line-height-alt: 32px;"><span data-mce-style="font-size: 14px; line-height: 28px;" style="font-size: 14px; line-height: 28px;">Je demande des modifications</span></span></span></a>', $content);
                $content = preg_replace('#\[etape\]#', $step->getName(), $content);
                $content = preg_replace('#\[mission\]#', '<a href="'.$this->router->generate('mission_edit', ['id' => $mission->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'">'.$mission->getProduct()->getName(). ' ('.$mission->getCampaign()->getName().')</a>', $content);
                $content = preg_replace('#\[date_livraison_attendue\]#', $mission->getDesiredDelivery()->format('d/m/Y'), $content);
                $content = preg_replace('#_lien_mission_#', $this->router->generate('mission_edit', ['id' => $mission->getId()], UrlGeneratorInterface::ABSOLUTE_URL), $content);
                $content = preg_replace('#_lien_activation_mission_#', $this->router->generate('mission_activate', ['id' => $mission->getId()], UrlGeneratorInterface::ABSOLUTE_URL), $content);
                $content = preg_replace('#\[refcommande\]#', $mission->getReference(), $content);

                if (null !== $user) {
                    foreach ($mission->getParticipants() as $participant) {
                        if ($user == $participant->getUser()) {
                            $content = preg_replace('#\[metier\]#', $participant->getJob()?->getName(), $content);
                        }
                    }
                }
            }

            $content = preg_replace('#\[contenu_brief\]#', nl2br($campaign->getBrief()), $content);
            $content = preg_replace('#\[panier\]#', nl2br($campaign->getName()), $content);


            if (false !== $campaign->getMissions()->first()) {
                $content = preg_replace('#\[refpanier\]#', $campaign->getMissions()->first()->getReference(), $content);
            }

            $content = preg_replace('#_lien_revalidation_panier_#', $this->router->generate('campaign_recapitulatif', ['id' => $campaign->getId()], UrlGeneratorInterface::ABSOLUTE_URL), $content);

            $currency = ($campaign->getCompany()->getContract() == Company::PACK_CREDIT) ? 'crédits' : '€ HT';
            $products = '';
            foreach ($campaign->getMissions() as $mission) {
                $price = $mission->getProduct()->getType() === ProductType::A_EVALUER ? 'A définir' : $mission->getPrice().$currency;
                $products .= '
                <tr>
                    <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                        '.$mission->getProduct()->getName().'
                    </td>
                    <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                        '.$mission->getQuantity().'
                    </td>
                    <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                        '.$price.'
                    </td>
                </tr>
            ';
            }
            $contenu = '<table border="0" cellpadding="0" cellspacing="0" class="image_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                    <tr>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                            PRODUIT
                        </td>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                            QUANTITE
                        </td>
                        <td style="padding-bottom:15px;padding-top:15px;padding-right:0px;padding-left:0px;">
                            PRIX
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <hr style="height: 5px;background-color: #06065C;display: block;border: 0; margin-top: -15px;" width="100%">
                        </td>
                    </tr>
                    '.$products.'
                </table>';
            $content = preg_replace('#\[contenu_commande\]#', $contenu, $content);

            $validateurs = '';
            $intervenants = '';
            foreach ($campaign->getMissions() as $mission) {
                foreach ($mission->getParticipants() as $participant) {
                    if ($participant->getRole() === Role::ROLE_VALIDATOR) {
                        $validateurs .= $participant->getUser()->getFirstname().' '.$participant->getUser()->getLastname().' : '.$participant->getUser()->getEmail().' / '.$participant->getUser()->getCellPhone().'<br>';
                    }

                    if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR) {
                        $intervenants .= $participant->getUser()->getFirstname().' '.$participant->getUser()->getLastname().' : '.$participant->getUser()->getEmail().' / '.$participant->getUser()->getCellPhone().'<br>';
                    }
                }

                $content = preg_replace('#\[coordonnees_intervenant\]#', $intervenants, $content);
                $content = preg_replace('#\[coordonnees_validateur\]#', $validateurs, $content);
                $content = preg_replace('#\[coordonnees_contact_campagne\]#', $campaign->getOrderedBy()->getFirstname().' '.$campaign->getOrderedBy()->getLastname().' : '.$campaign->getOrderedBy()->getEmail().' / '.$campaign->getOrderedBy()->getCellPhone().'<br>', $content);
            }

            if ($campaign->getMissions()->count() > 0 && false !== $campaign->getMissions()->first()) {
                $content = preg_replace('#_lien_panier_#', $this->router->generate('mission_edit', ['id' => $campaign->getMissions()->first()->getId()], UrlGeneratorInterface::ABSOLUTE_URL), $content);
            }

            $content = preg_replace('#\[tel\]#', $campaign->getOrderedBy()->getCellPhone(), $content);
        }

        $content = preg_replace('#_lien_tableau_de_bord_#', $this->router->generate('mission_index', [], UrlGeneratorInterface::ABSOLUTE_URL), $content);

        return $content;
    }
}
