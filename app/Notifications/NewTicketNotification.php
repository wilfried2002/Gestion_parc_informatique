<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTicketNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $priorityLabels = [
            'low'      => 'Faible',
            'medium'   => 'Moyenne',
            'high'     => 'Haute',
            'critical' => 'Critique',
        ];

        $categoryLabels = [
            'materiel' => 'Materiel',
            'logiciel' => 'Logiciel',
            'reseau'   => 'Reseau',
            'securite' => 'Securite',
            'autre'    => 'Autre',
        ];

        $reference   = $this->ticket->reference;
        $title       = $this->ticket->title;
        $description = $this->ticket->description;
        $creatorName = $this->ticket->user ? $this->ticket->user->name : 'Inconnu';

        $p        = $this->ticket->priority;
        $c        = $this->ticket->category;
        $priority = isset($priorityLabels[$p]) ? $priorityLabels[$p] : $p;
        $category = isset($categoryLabels[$c]) ? $categoryLabels[$c] : $c;

        return (new MailMessage)
            ->subject("Nouveau ticket #" . $reference . " - " . $priority)
            ->greeting("Bonjour " . $notifiable->name . ",")
            ->line("Un nouveau ticket vient d'etre soumis et requiert votre attention.")
            ->line("Reference : " . $reference)
            ->line("Titre : " . $title)
            ->line("Priorite : " . $priority)
            ->line("Categorie : " . $category)
            ->line("Soumis par : " . $creatorName)
            ->line("Description : " . $description)
            ->action('Voir le ticket', url('/app'))
            ->line('Merci de traiter ce ticket dans les meilleurs delais.')
            ->salutation('Cordialement, Gestion Parc IT');
    }

    public function toDatabase(object $notifiable): array
    {
        $creatorName = $this->ticket->user ? $this->ticket->user->name : 'Inconnu';
        $reference   = $this->ticket->reference;

        return [
            'type'       => 'new_ticket',
            'ticket_id'  => $this->ticket->id,
            'reference'  => $reference,
            'title'      => $this->ticket->title,
            'priority'   => $this->ticket->priority,
            'category'   => $this->ticket->category,
            'created_by' => $creatorName,
            'message'    => 'Nouveau ticket ' . $reference . ' soumis par ' . $creatorName,
        ];
    }
}
