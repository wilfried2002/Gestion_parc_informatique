<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly ActivityLogService $logService
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->ticketRepository->paginate($perPage, $filters);
    }

    public function show(int $id): Ticket
    {
        return $this->ticketRepository->findById($id);
    }

    public function create(array $data, User $creator): Ticket
    {
        return DB::transaction(function () use ($data, $creator) {
            $ticket = $this->ticketRepository->create([
                'reference'   => $this->ticketRepository->generateReference(),
                'user_id'     => $creator->id,
                'title'       => $data['title'],
                'description' => $data['description'],
                'priority'    => $data['priority'] ?? Ticket::PRIORITY_MEDIUM,
                'category'    => $data['category'] ?? 'autre',
                'status'      => Ticket::STATUS_OPEN,
            ]);

            $this->logService->log('created', $creator, Ticket::class, $ticket->id, null, $ticket->toArray());

            return $ticket;
        });
    }

    public function update(int $id, array $data, User $updater): Ticket
    {
        return DB::transaction(function () use ($id, $data, $updater) {
            $ticket   = $this->ticketRepository->findById($id);
            $oldValues = $ticket->only(['title', 'description', 'priority', 'status', 'category']);

            $updated = $this->ticketRepository->update($id, $data);

            $this->logService->log('updated', $updater, Ticket::class, $id, $oldValues, $data);

            return $updated;
        });
    }

    /**
     * Assigne un ticket à un technicien (Admin seulement).
     */
    public function assign(int $ticketId, int $technicianId, User $admin): Ticket
    {
        return DB::transaction(function () use ($ticketId, $technicianId, $admin) {
            $ticket = $this->ticketRepository->findById($ticketId);

            if (!$ticket->canBeAssigned() && $ticket->status !== Ticket::STATUS_ASSIGNED) {
                throw new \Exception("Ce ticket ne peut pas être assigné dans son état actuel.", 422);
            }

            $old = ['technician_id' => $ticket->technician_id, 'status' => $ticket->status];

            $ticket = $this->ticketRepository->update($ticketId, [
                'technician_id' => $technicianId,
                'status'        => Ticket::STATUS_ASSIGNED,
            ]);

            $this->logService->log('assigned', $admin, Ticket::class, $ticketId, $old, [
                'technician_id' => $technicianId,
                'status'        => Ticket::STATUS_ASSIGNED,
            ]);

            return $ticket;
        });
    }

    /**
     * Met à jour le statut d'un ticket.
     */
    public function changeStatus(int $ticketId, string $newStatus, User $user): Ticket
    {
        $ticket = $this->ticketRepository->findById($ticketId);
        $extra  = [];

        if ($newStatus === Ticket::STATUS_RESOLVED) {
            $extra['resolved_at'] = now();
        } elseif ($newStatus === Ticket::STATUS_CLOSED) {
            $extra['closed_at'] = now();
        }

        $old     = ['status' => $ticket->status];
        $updated = $this->ticketRepository->update($ticketId, array_merge(['status' => $newStatus], $extra));

        $this->logService->log('status_changed', $user, Ticket::class, $ticketId, $old, ['status' => $newStatus]);

        return $updated;
    }

    public function delete(int $id, User $user): void
    {
        $ticket = $this->ticketRepository->findById($id);
        $this->ticketRepository->delete($id);
        $this->logService->log('deleted', $user, Ticket::class, $id, $ticket->toArray(), null);
    }
}
