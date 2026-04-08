<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * @OA\Post(path="/api/auth/login", summary="Connexion utilisateur")
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->success($result, 'Connexion réussie.');
        } catch (\Exception $e) {
            $code     = (int) $e->getCode();
            $httpCode = ($code >= 100 && $code < 600) ? $code : 401;
            return $this->error($e->getMessage(), $httpCode);
        }
    }

    /**
     * @OA\Post(path="/api/auth/register", summary="Inscription utilisateur")
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return $this->success($result, 'Compte créé avec succès.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Retourne l'utilisateur authentifié avec son profil complet.
     */
    public function me(): JsonResponse
    {
        $user = auth()->user()->load('role');

        return $this->success([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'department' => $user->department,
            'role'       => $user->role?->name,
            'role_label' => $user->role?->label,
            'is_active'  => $user->is_active,
        ]);
    }

    /**
     * Rafraîchit le token JWT.
     */
    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refresh();

            return $this->success($result, 'Token rafraîchi.');
        } catch (\Exception $e) {
            return $this->error('Impossible de rafraîchir le token.', 401);
        }
    }

    /**
     * Déconnexion (invalide le token).
     */
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();

            return $this->success(null, 'Déconnexion réussie.');
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la déconnexion.', 500);
        }
    }
}
