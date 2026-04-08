<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    /**
     * GET /api/dashboard/stats
     * Retourne les statistiques adaptées au rôle de l'utilisateur.
     */
    public function stats(): JsonResponse
    {
        $user = auth()->user();

        $stats = match ($user->role?->name) {
            Role::ADMIN      => $this->dashboardService->getAdminStats(),
            Role::TECHNICIEN => $this->dashboardService->getTechnicianStats($user->id),
            default          => $this->dashboardService->getUserDashboard($user->id),
        };

        return $this->success($stats, 'Statistiques chargées.');
    }
}
