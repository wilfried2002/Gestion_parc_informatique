<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\Stock;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\InterventionRepository;
use App\Repositories\TicketRepository;
use App\Repositories\StockRepository;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly InterventionRepository $interventionRepository,
        private readonly StockRepository $stockRepository
    ) {}

    /**
     * Statistiques globales pour l'administrateur.
     */
    public function getAdminStats(): array
    {
        return [
            'tickets'       => $this->getTicketStats(),
            'interventions' => $this->getInterventionStats(),
            'stock'         => $this->getStockStats(),
            'users'         => $this->getUserStats(),
            'charts'        => $this->getChartData(),
        ];
    }

    /**
     * Statistiques personnalisées pour un technicien.
     */
    public function getTechnicianStats(int $technicianId): array
    {
        $assignedTickets  = Ticket::where('technician_id', $technicianId)->count();
        $openTickets      = Ticket::where('technician_id', $technicianId)
            ->whereIn('status', ['assigned', 'in_progress'])->count();
        $resolvedTickets  = Ticket::where('technician_id', $technicianId)
            ->where('status', 'resolved')->count();
        $interventions    = Intervention::where('technician_id', $technicianId)->count();
        $avgDuration      = Intervention::where('technician_id', $technicianId)
            ->whereNotNull('duration_minutes')
            ->avg('duration_minutes');

        return [
            'tickets' => [
                'assigned' => $assignedTickets,
                'open'     => $openTickets,
                'resolved' => $resolvedTickets,
            ],
            'interventions' => [
                'total'        => $interventions,
                'avg_duration' => round($avgDuration ?? 0, 2),
            ],
            'recent_tickets' => Ticket::where('technician_id', $technicianId)
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get(['id', 'reference', 'title', 'status', 'priority', 'created_at']),
        ];
    }

    /**
     * Statistiques pour un utilisateur simple.
     */
    public function getUserDashboard(int $userId): array
    {
        return [
            'tickets' => [
                'total'       => Ticket::where('user_id', $userId)->count(),
                'open'        => Ticket::where('user_id', $userId)->where('status', 'open')->count(),
                'in_progress' => Ticket::where('user_id', $userId)->whereIn('status', ['assigned', 'in_progress'])->count(),
                'resolved'    => Ticket::where('user_id', $userId)->whereIn('status', ['resolved', 'closed'])->count(),
            ],
            'recent_tickets' => Ticket::where('user_id', $userId)
                ->with('technician:id,name')
                ->latest()
                ->limit(5)
                ->get(['id', 'reference', 'title', 'status', 'priority', 'created_at']),
        ];
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    private function getTicketStats(): array
    {
        $byStatus = $this->ticketRepository->countByStatus()
            ->pluck('total', 'status')
            ->toArray();

        return [
            'total'       => array_sum($byStatus),
            'open'        => $byStatus['open'] ?? 0,
            'assigned'    => $byStatus['assigned'] ?? 0,
            'in_progress' => $byStatus['in_progress'] ?? 0,
            'resolved'    => $byStatus['resolved'] ?? 0,
            'closed'      => $byStatus['closed'] ?? 0,
        ];
    }

    private function getInterventionStats(): array
    {
        return [
            'total'        => Intervention::count(),
            'completed'    => Intervention::where('status', 'completed')->count(),
            'in_progress'  => Intervention::where('status', 'in_progress')->count(),
            'avg_duration' => round(Intervention::whereNotNull('duration_minutes')->avg('duration_minutes') ?? 0, 2),
        ];
    }

    private function getStockStats(): array
    {
        return [
            'total'      => Stock::count(),
            'low_stock'  => Stock::lowStock()->count(),
            'hors_service' => Stock::where('status', 'hors_service')->count(),
            'by_category' => $this->stockRepository->statsByCategory(),
        ];
    }

    private function getUserStats(): array
    {
        return [
            'total'        => User::count(),
            'active'       => User::where('is_active', true)->count(),
            'technicians'  => User::whereHas('role', fn($q) => $q->where('name', 'technicien'))->count(),
        ];
    }

    private function getChartData(): array
    {
        // Tickets des 30 derniers jours (pour Chart.js)
        $ticketsLast30Days = Ticket::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Interventions par technicien
        $interventionsByTech = $this->interventionRepository->statsByTechnician();

        // Tickets par priorité
        $ticketsByPriority = $this->ticketRepository->countByPriority()
            ->pluck('total', 'priority');

        return [
            'tickets_last_30_days'    => $ticketsLast30Days,
            'interventions_by_tech'   => $interventionsByTech,
            'tickets_by_priority'     => $ticketsByPriority,
        ];
    }
}
