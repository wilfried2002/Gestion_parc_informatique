<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TicketRepository extends BaseRepository
{
    public function __construct(Ticket $ticket)
    {
        parent::__construct($ticket);
    }

    /**
     * Liste paginée avec filtres et recherche.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Ticket::with(['user:id,name,email', 'technician:id,name,email'])
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn($q) => $q->where('priority', $filters['priority']))
            ->when(isset($filters['category']), fn($q) => $q->where('category', $filters['category']))
            ->when(isset($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(isset($filters['technician_id']), fn($q) => $q->where('technician_id', $filters['technician_id']))
            ->when(isset($filters['search']), fn($q) => $q->where(function ($sq) use ($filters) {
                $sq->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('reference', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            }))
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Ticket
    {
        return Ticket::with([
            'user:id,name,email',
            'technician:id,name,email',
            'interventions.technician:id,name',
            'attachments',
        ])->findOrFail($id);
    }

    /**
     * Statistiques par statut pour le dashboard.
     */
    public function countByStatus(): Collection
    {
        return Ticket::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();
    }

    /**
     * Statistiques par priorité.
     */
    public function countByPriority(): Collection
    {
        return Ticket::selectRaw('priority, COUNT(*) as total')
            ->groupBy('priority')
            ->get();
    }

    /**
     * Tickets récents pour le dashboard.
     */
    public function recent(int $limit = 10): Collection
    {
        return Ticket::with(['user:id,name', 'technician:id,name'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Génère la prochaine référence de ticket.
     */
    public function generateReference(): string
    {
        $year = date('Y');
        $prefix = "TKT-{$year}-";

        $last = Ticket::withTrashed()
            ->where('reference', 'like', "{$prefix}%")
            ->max('reference');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
