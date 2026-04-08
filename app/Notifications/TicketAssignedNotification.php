<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly User   $assignedBy
    ) {}

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
            'critical' => 'Critique - Action immediate requise',
        ];

        $reference   = $this->ticket->reference;
        $title       = $this->ticket->title;
        $description = $this->ticket->description;
        $creatorName = $this->ticket->user ? $this->ticket->user->name : 'Inconnu';
        $assignedBy  = $this->assignedBy->name;

        $p        = $this->ticket->priority;
        $priority = isset($priorityLabels[$p]) ? $priorityLabels[$p] : $p;

        return (new MailMessage)
            ->subject("Ticket " . $reference . " vous a ete assigne")
            ->greeting("Bonjour " . $notifiable->name . ",")
            ->line("Un ticket vous a ete assigne par " . $assignedBy . ".")
            ->line("Reference : " . $reference)
            ->line("Titre : " . $title)
            ->line("Priorite : " . $priority)
            ->line("Soumis par : " . $creatorName)
            ->line("Description : " . $description)
            ->action('Voir le ticket', url('/app'))
            ->line('Merci de prendre en charge ce ticket des que possible.')
            ->salutation('Cordialement, Gestion Parc IT');
    }

    public function toDatabase(object $notifiable): array
    {
        $reference  = $this->ticket->reference;
        $assignedBy = $this->assignedBy->name;

        return [
            'type'        => 'ticket_assigned',
            'ticket_id'   => $this->ticket->id,
            'reference'   => $reference,
            'title'       => $this->ticket->title,
            'priority'    => $this->ticket->priority,
            'assigned_by' => $assignedBy,
            'message'     => 'Le ticket ' . $reference . ' vous a ete assigne par ' . $assignedBy,
        ];
    }
}
