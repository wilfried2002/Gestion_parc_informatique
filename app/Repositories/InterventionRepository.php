<?php

namespace App\Repositories;

use App\Models\Intervention;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InterventionRepository extends BaseRepository
{
    public function __construct(Intervention $intervention)
    {
        parent::__construct($intervention);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Intervention::with([
            'ticket:id,reference,title,status',
            'technician:id,name,email',
        ])
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['ticket_id']), fn($q) => $q->where('ticket_id', $filters['ticket_id']))
            ->when(isset($filters['technician_id']), fn($q) => $q->where('technician_id', $filters['technician_id']))
            ->when(isset($filters['date_from']), fn($q) => $q->where('start_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->where('start_date', '<=', $filters['date_to']))
            ->when(isset($filters['search']), fn($q) => $q->where(function ($sq) use ($filters) {
                $sq->where('reference', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            }))
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Intervention
    {
        return Intervention::with([
            'ticket.user:id,name,email',
            'technician:id,name,email',
            'attachments',
        ])->findOrFail($id);
    }

    /**
     * Statistiques interventions par technicien.
     */
    public function statsByTechnician(): Collection
    {
        return Intervention::selectRaw('technician_id, COUNT(*) as total, AVG(duration_minutes) as avg_duration')
            ->with('technician:id,name')
            ->groupBy('technician_id')
            ->get();
    }

    public function generateReference(): string
    {
        $year   = date('Y');
        $prefix = "INT-{$year}-";

        $last = Intervention::withTrashed()
            ->where('reference', 'like', "{$prefix}%")
            ->max('reference');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }
}
