<?php

namespace App\Entity;

use App\Repository\SystemEmailRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SystemEmailRepository::class)]
#[ORM\Table(name: "system_emails")]
class SystemEmail
{
    const CONFIRMATION_INSCRIPTION = 'CONFIRMATION_INSCRIPTION';
    const DEMANDE_NON_TERMINER_CLIENT = 'DEMANDE_NON_TERMINER_CLIENT';
    const DEMANDE_NON_TERMINER_CLIENT_DEVIS = 'DEMANDE_NON_TERMINER_CLIENT_DEVIS';
    const CREATION_NOUVEAU_CLIENT = 'CREATION_NOUVEAU_CLIENT';
    const CREATION_NOUVEAU_SOUS_TRAITANT = 'CREATION_NOUVEAU_SOUS_TRAITANT';
    const AJOUT_VALIDATEUR = 'AJOUT_VALIDATEUR';
    const AJOUT_OBSERVATEUR = 'AJOUT_OBSERVATEUR';
    const AJOUT_VALIDATEUR_OBSERVATEUR = 'AJOUT_VALIDATEUR_OBSERVATEUR';
    const CREATION_CAMPAGNE = 'CREATION_CAMPAGNE';
    const RELANCE_SOUS_TRAITANT = 'RELANCE_SOUS_TRAITANT';
    const NOTIFICATION_ADMIN = 'NOTIFICATION_ADMIN';
    const SOUS_TRAITANT_PROFIL_COMPLETE = 'SOUS_TRAITANT_PROFIL_COMPLETE';
    const SOUS_TRAITANT_NO_REPONSE_48H = 'SOUS_TRAITANT_NO_REPONSE_48H';
    const MISE_A_JOUR_PROFIL_ATTENDU = 'MISE_A_JOUR_PROFIL_ATTENDU';
    const ACTIVATION_PRESTATAIRE = 'ACTIVATION_PRESTATAIRE';
    const MISSION_ACTIVER_CLIENT = 'MISSION_ACTIVER_CLIENT';
    const MISSION_SOUS_TRAITANT_TOUS_ACTIVER = 'MISSION_SOUS_TRAITANT_TOUS_ACTIVER';
    const MISSION_SOUS_TRAITANT_PAS_ACTIVER_24H = 'MISSION_SOUS_TRAITANT_PAS_ACTIVER_24H';
    const MISSION_SANS_PARTENAIRE = 'MISSION_SANS_PARTENAIRE';
    const CONFIRMER_MISSION = 'CONFIRMER_MISSION';
    const MISSION_DEMANDE_EVALUATION = 'MISSION_DEMANDE_EVALUATION';
    const MISSION_ACCEPTEE_CLIENT = 'MISSION_ACCEPTEE_CLIENT';
    const MISSION_RELANCE_ACTIVATION_SOUS_TRAITANT = 'MISSION_RELANCE_ACTIVATION_SOUS_TRAITANT';
    const CLIENT_CAMPAGNE_CREEE = 'CLIENT_CAMPAGNE_CREEE';
    const CLIENT_CAMPAGNE_A_EVALUER_CREEE = 'CLIENT_CAMPAGNE_A_EVALUER_CREEE';
    const NOTIFICATION_ANNIVERSAIRE_CONTRAT = 'NOTIFICATION_ANNIVERSAIRE_CONTRAT';
    const CONFIRMATION_REFERENCEMENT = 'CONFIRMATION_REFERENCEMENT';
    const DEMANDE_DEVIS_NOTIF_ADMIN = 'DEMANDE_DEVIS_NOTIF_ADMIN';
    const DEMANDE_DEVIS_NOTIF_CLIENT = 'DEMANDE_DEVIS_NOTIF_CLIENT';
    const MISSION_DESIRED_DELIVERY_UPDATED_AFTER_VALIDATION = 'MISSION_DESIRED_DELIVERY_UPDATED_AFTER_VALIDATION';
    const MISSION_DESIRED_DELIVERY_UPDATED_BEFORE_VALIDATION = 'MISSION_DESIRED_DELIVERY_UPDATED_BEFORE_VALIDATION';
    const MISSION_REFUSEE_INTERVENANT = 'MISSION_REFUSEE_INTERVENANT';
    const MISSION_REFUSEE_ADMIN = 'MISSION_REFUSEE_ADMIN';
    const MISSION_CANCELLED = 'MISSION_CANCELLED';
    const MISSION_ARCHIVED_CLIENT = 'MISSION_ARCHIVED_CLIENT';
    const MISSION_ARCHIVED_PRESTATAIRE = 'MISSION_ARCHIVED_PRESTATAIRE';
    const MISSION_ARCHIVED_ADMIN = 'MISSION_ARCHIVED_ADMIN';
    const NOTIFICATION_VALIDATION_RECAP_CAMPAGNE = 'NOTIFICATION_VALIDATION_RECAP_CAMPAGNE';
    const MISSION_EN_PAUSE = 'MISSION_EN_PAUSE';
    const RESOUMISSION_PANIER = 'RESOUMISSION_PANIER';
    const RENSEIGNEZ_TEMPS_MISSION = 'RENSEIGNEZ_TEMPS_MISSION';
    const SOUS_TRAITANT_FORFAIT_INITIAL_TIME_UPDATED_AFTER_VALIDATION = 'SOUS_TRAITANT_FORFAIT_INITIAL_TIME_UPDATED_AFTER_VALIDATION';
    const SOUS_TRAITANT_ADD_SERVICE = 'SOUS_TRAITANT_ADD_SERVICE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Merci de remplir ce champ')]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Merci de remplir ce champ')]
    private string $sender = '';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Merci de remplir ce champ')]
    private string $senderName = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Merci de remplir ce champ')]
    private string $content;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Merci de remplir ce champ')]
    private string $subject;

    #[ORM\Column(type: 'string', length: 255)]
    private string $code;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $smsContent;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(string $senderName): self
    {
        $this->senderName = $senderName;

        return $this;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function setSender(string $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getSmsContent(): ?string
    {
        return $this->smsContent;
    }

    public function setSmsContent(?string $smsContent): self
    {
        $this->smsContent = $smsContent;

        return $this;
    }
}
