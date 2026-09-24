<?php

namespace App\Notifications;

use App\Models\DemandeSinistre;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StatutDemandeSinistreModifie extends Notification
{
    public function __construct(
        public readonly DemandeSinistre $demande,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $demande = $this->demande;

        $message = (new MailMessage)
            ->subject("MIACI — Mise à jour de votre demande de sinistre n°{$demande->id}")
            ->greeting("Bonjour {$demande->adherent->prenom},")
            ->line("Le statut de votre demande de prise en charge ({$demande->typeSinistre->libelle}) a été mis à jour :")
            ->line('Nouveau statut : '.$demande->statut->libelle());

        if ($demande->statut->value === 'approuvee' && $demande->montant_accorde !== null) {
            $message->line('Montant accordé : '.number_format($demande->montant_accorde, 0, ',', ' ').' FCFA');
        }

        if ($demande->motif_decision) {
            $message->line('Commentaire : '.$demande->motif_decision);
        }

        return $message->line('Vous pouvez consulter le détail de votre demande depuis votre espace adhérent.');
    }
}
