<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogService
{
    /**
     * Enregistre une action dans le journal d'activité.
     */
    public function log(
        string $action,
        ?User $user,
        ?string $modelType,
        ?int $modelId,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        ActivityLog::create([
            'user_id'    => $user?->id,
            'action'     => $action,
            'model_type' => $modelType ?? 'App\Models\User',
            'model_id'   => $modelId ?? $user?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Récupère les logs pour un modèle donné.
     */
    public function getLogsForModel(string $modelType, int $modelId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
