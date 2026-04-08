<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\InterventionRepository;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InterventionService
{
    public function __construct(
        private readonly InterventionRepository $interventionRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly ActivityLogService $logService
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->interventionRepository->paginate($perPage, $filters);
    }

    public function show(int $id): Intervention
    {
        return $this->interventionRepository->findById($id);
    }

    public function create(array $data, User $creator): Intervention
    {
        return DB::transaction(function () use ($data, $creator) {
            $ticket = $this->ticketRepository->findById($data['ticket_id']);

            // Mettre le ticket en in_progress si ce n'est pas encore le cas
            if ($ticket->status === Ticket::STATUS_ASSIGNED) {
                $this->ticketRepository->update($ticket->id, ['status' => Ticket::STATUS_IN_PROGRESS]);
            }

            $intervention = $this->interventionRepository->create([
                'reference'     => $this->interventionRepository->generateReference(),
                'ticket_id'     => $data['ticket_id'],
                'technician_id' => $creator->isTechnicien() ? $creator->id : ($data['technician_id'] ?? $creator->id),
                'description'   => $data['description'],
                'status'        => Intervention::STATUS_PLANNED,
                'start_date'    => $data['start_date'] ?? null,
                'end_date'      => $data['end_date'] ?? null,
            ]);

            $this->logService->log('created', $creator, Intervention::class, $intervention->id, null, $intervention->toArray());

            return $intervention;
        });
    }

    public function update(int $id, array $data, User $updater): Intervention
    {
        return DB::transaction(function () use ($id, $data, $updater) {
            $old     = $this->interventionRepository->findById($id)->toArray();
            $updated = $this->interventionRepository->update($id, $data);

            // Calcul automatique de la durée si les deux dates sont présentes
            if ($updated->start_date && $updated->end_date) {
                $duration = $updated->calculateDuration();
                $updated->update(['duration_minutes' => $duration]);
            }

            $this->logService->log('updated', $updater, Intervention::class, $id, $old, $data);

            return $updated->fresh();
        });
    }

    /**
     * Démarrage d'une intervention (status → in_progress).
     */
    public function start(int $id, User $technician): Intervention
    {
        $intervention = $this->interventionRepository->findById($id);

        if ($intervention->status !== Intervention::STATUS_PLANNED) {
            throw new \Exception("L'intervention doit être planifiée pour être démarrée.", 422);
        }

        return $this->interventionRepository->update($id, [
            'status'     => Intervention::STATUS_IN_PROGRESS,
            'start_date' => now(),
        ]);
    }

    /**
     * Clôture d'une intervention avec rapport.
     */
    public function complete(int $id, string $report, User $technician): Intervention
    {
        return DB::transaction(function () use ($id, $report, $technician) {
            $intervention = $this->interventionRepository->findById($id);
            $endDate      = now();
            $duration     = $intervention->start_date
                ? $intervention->start_date->diffInMinutes($endDate)
                : null;

            $updated = $this->interventionRepository->update($id, [
                'status'           => Intervention::STATUS_COMPLETED,
                'report'           => $report,
                'end_date'         => $endDate,
                'duration_minutes' => $duration,
            ]);

            // Vérifier si toutes les interventions du ticket sont terminées
            $ticket      = $intervention->ticket;
            $openCount   = $ticket->interventions()
                ->whereNotIn('status', [Intervention::STATUS_COMPLETED, Intervention::STATUS_CANCELLED])
                ->count();

            if ($openCount === 0) {
                $this->ticketRepository->update($ticket->id, [
                    'status'      => Ticket::STATUS_RESOLVED,
                    'resolved_at' => now(),
                ]);
            }

            $this->logService->log('completed', $technician, Intervention::class, $id, null, ['report' => $report]);

            return $updated;
        });
    }

    public function delete(int $id, User $user): void
    {
        $this->interventionRepository->delete($id);
        $this->logService->log('deleted', $user, Intervention::class, $id, null, null);
    }
}
