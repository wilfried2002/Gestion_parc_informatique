<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = User::with('role:id,name,label')
            ->when(isset($filters['role_id']), fn($q) => $q->where('role_id', $filters['role_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['department']), fn($q) => $q->where('department', 'like', "%{$filters['department']}%"))
            ->when(isset($filters['search']), fn($q) => $q->where(function ($sq) use ($filters) {
                $sq->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            }))
            ->orderBy('name');

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return User::with('role:id,name,label')->findOrFail($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->with('role')->first();
    }

    /**
     * Liste des techniciens actifs (pour l'assignation).
     */
    public function getTechnicians(): Collection
    {
        return User::whereHas('role', fn($q) => $q->where('name', 'technicien'))
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'department')
            ->orderBy('name')
            ->get();
    }
}
