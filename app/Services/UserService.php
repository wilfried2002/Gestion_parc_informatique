<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ActivityLogService $logService
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage, $filters);
    }

    public function show(int $id): User
    {
        return $this->userRepository->findById($id);
    }

    public function create(array $data, User $admin): User
    {
        $user = $this->userRepository->create([
            'role_id'    => $data['role_id'],
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'phone'      => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'is_active'  => $data['is_active'] ?? true,
        ]);

        $this->logService->log('created', $admin, User::class, $user->id, null, ['name' => $user->name, 'email' => $user->email]);

        return $user->load('role');
    }

    public function update(int $id, array $data, User $admin): User
    {
        $old = $this->userRepository->findById($id)->only(['name', 'email', 'role_id', 'is_active']);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $updated = $this->userRepository->update($id, $data);
        $this->logService->log('updated', $admin, User::class, $id, $old, $data);

        return $updated->load('role');
    }

    public function delete(int $id, User $admin): void
    {
        if ($id === $admin->id) {
            throw new \Exception("Vous ne pouvez pas supprimer votre propre compte.", 422);
        }

        $this->userRepository->delete($id);
        $this->logService->log('deleted', $admin, User::class, $id, null, null);
    }

    public function toggleActive(int $id, User $admin): User
    {
        $user    = $this->userRepository->findById($id);
        $updated = $this->userRepository->update($id, ['is_active' => !$user->is_active]);

        $this->logService->log(
            $updated->is_active ? 'activated' : 'deactivated',
            $admin, User::class, $id, null, null
        );

        return $updated;
    }

    public function getTechnicians(): Collection
    {
        return $this->userRepository->getTechnicians();
    }
}
