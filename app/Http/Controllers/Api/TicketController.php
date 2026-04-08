<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\TicketRequest;
use App\Models\Ticket;
use App\Services\AttachmentService;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
        private readonly AttachmentService $attachmentService
    ) {}

    /**
     * GET /api/tickets
     * Liste paginée avec filtres (status, priority, category, search).
     */
    public function index(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $filters = $request->only(['status', 'priority', 'category', 'search']);

        // Un utilisateur simple ne voit que ses tickets
        if ($user->isUtilisateur()) {
            $filters['user_id'] = $user->id;
        }

        // Un technicien voit ses tickets assignés
        if ($user->isTechnicien()) {
            $filters['technician_id'] = $user->id;
        }

        $tickets = $this->ticketService->list($filters, $request->get('per_page', 15));

        return $this->paginated($tickets);
    }

    /**
     * POST /api/tickets
     */
    public function store(TicketRequest $request): JsonResponse
    {
        try {
            $ticket = $this->ticketService->create($request->validated(), auth()->user());

            // Upload des pièces jointes si présentes
            if ($request->hasFile('attachments')) {
                $this->attachmentService->uploadMany(
                    $request->file('attachments'),
                    Ticket::class,
                    $ticket->id,
                    auth()->user()
                );
            }

            return $this->success(
                $this->ticketService->show($ticket->id),
                'Ticket créé avec succès.',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/tickets/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $ticket = $this->ticketService->show($id);
            $this->authorizeTicketAccess($ticket);

            return $this->success($ticket);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * PUT /api/tickets/{id}
     */
    public function update(TicketRequest $request, int $id): JsonResponse
    {
        try {
            $ticket = $this->ticketService->show($id);
            $this->authorizeTicketAccess($ticket);

            $updated = $this->ticketService->update($id, $request->validated(), auth()->user());

            return $this->success($updated, 'Ticket mis à jour.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /api/tickets/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->ticketService->delete($id, auth()->user());

            return $this->success(null, 'Ticket supprimé.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/tickets/{id}/assign   [Admin]
     */
    public function assign(AssignTicketRequest $request, int $id): JsonResponse
    {
        try {
            $ticket = $this->ticketService->assign($id, $request->technician_id, auth()->user());

            return $this->success($ticket, 'Ticket assigné avec succès.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * PATCH /api/tickets/{id}/status   [Technicien + Admin]
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in([
                Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED,
                Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
            ])],
        ]);

        try {
            $ticket = $this->ticketService->changeStatus($id, $request->status, auth()->user());

            return $this->success($ticket, 'Statut mis à jour.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/tickets/{id}/attachments
     */
    public function uploadAttachment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        try {
            $attachment = $this->attachmentService->upload(
                $request->file('file'),
                Ticket::class,
                $id,
                auth()->user()
            );

            return $this->success($attachment, 'Fichier uploadé.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    // ─── Private ────────────────────────────────────────────────────────────

    /**
     * Vérifie que l'utilisateur peut accéder au ticket.
     */
    private function authorizeTicketAccess(Ticket $ticket): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTechnicien() && $ticket->technician_id === $user->id) {
            return;
        }

        if ($user->isUtilisateur() && $ticket->user_id === $user->id) {
            return;
        }

        abort(403, 'Accès refusé à ce ticket.');
    }
}
