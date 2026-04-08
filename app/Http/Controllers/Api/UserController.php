<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * GET /api/users   [Admin]
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['role_id', 'is_active', 'department', 'search']);
        $users   = $this->userService->list($filters, $request->get('per_page', 15));

        return $this->paginated($users);
    }

    /**
     * POST /api/users   [Admin]
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'role_id'    => ['required', 'exists:roles,id'],
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        try {
            $user = $this->userService->create($request->all(), auth()->user());

            return $this->success($user, 'Utilisateur créé.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/users/{id}   [Admin]
     */
    public function show(int $id): JsonResponse
    {
        try {
            return $this->success($this->userService->show($id));
        } catch (\Exception $e) {
            return $this->error('Utilisateur introuvable.', 404);
        }
    }

    /**
     * PUT /api/users/{id}   [Admin]
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role_id'    => ['sometimes', 'exists:roles,id'],
            'name'       => ['sometimes', 'string', 'max:255'],
            'email'      => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password'   => ['sometimes', 'string', 'min:8', 'confirmed'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        try {
            $user = $this->userService->update($id, $request->all(), auth()->user());

            return $this->success($user, 'Utilisateur mis à jour.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /api/users/{id}   [Admin]
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->delete($id, auth()->user());

            return $this->success(null, 'Utilisateur supprimé.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * PATCH /api/users/{id}/toggle-active   [Admin]
     */
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $user = $this->userService->toggleActive($id, auth()->user());
            $msg  = $user->is_active ? 'Compte activé.' : 'Compte désactivé.';

            return $this->success($user, $msg);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/users/technicians   [Admin]
     * Liste des techniciens disponibles pour l'assignation.
     */
    public function technicians(): JsonResponse
    {
        return $this->success($this->userService->getTechnicians());
    }
}
