<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ActivityLogService $logService
    ) {}

    /**
     * Authentifie un utilisateur et retourne le token JWT.
     */
    public function login(array $credentials): array
    {
        $token = JWTAuth::attempt([
            'email'    => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => true,
        ]);

        if (!$token) {
            throw new \Exception('Identifiants invalides ou compte désactivé.', 401);
        }

        $user = auth()->user();
        $this->logService->log('login', $user, null, null);

        return $this->buildTokenResponse($token, $user);
    }

    /**
     * Inscription d'un nouvel utilisateur (rôle utilisateur par défaut).
     */
    public function register(array $data): array
    {
        $role = Role::where('name', Role::UTILISATEUR)->firstOrFail();

        $user = User::create([
            'role_id'    => $role->id,
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'phone'      => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
        ]);

        $token = JWTAuth::fromUser($user);
        $this->logService->log('registered', $user, null, null);

        return $this->buildTokenResponse($token, $user->load('role'));
    }

    /**
     * Rafraîchit le token JWT.
     */
    public function refresh(): array
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());
        $user  = JWTAuth::setToken($token)->toUser();

        return $this->buildTokenResponse($token, $user);
    }

    /**
     * Déconnecte l'utilisateur (invalide le token).
     */
    public function logout(): void
    {
        $user = auth()->user();
        $this->logService->log('logout', $user, null, null);
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    // ─── Private ────────────────────────────────────────────────────────────

    private function buildTokenResponse(string $token, User $user): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role?->name,
                'role_label' => $user->role?->label,
            ],
        ];
    }
}
