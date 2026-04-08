<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Intervention\InterventionRequest;
use App\Models\Intervention;
use App\Services\AttachmentService;
use App\Services\InterventionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    public function __construct(
        private readonly InterventionService $interventionService,
        private readonly AttachmentService $attachmentService
    ) {}

    /**
     * GET /api/interventions
     */
    public function index(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $filters = $request->only(['status', 'ticket_id', 'date_from', 'date_to', 'search']);

        // Un technicien voit uniquement ses interventions
        if ($user->isTechnicien()) {
            $filters['technician_id'] = $user->id;
        }

        $interventions = $this->interventionService->list($filters, $request->get('per_page', 15));

        return $this->paginated($interventions);
    }

    /**
     * POST /api/interventions
     */
    public function store(InterventionRequest $request): JsonResponse
    {
        try {
            $intervention = $this->interventionService->create($request->validated(), auth()->user());

            if ($request->hasFile('attachments')) {
                $this->attachmentService->uploadMany(
                    $request->file('attachments'),
                    Intervention::class,
                    $intervention->id,
                    auth()->user()
                );
            }

            return $this->success(
                $this->interventionService->show($intervention->id),
                'Intervention créée.',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/interventions/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $intervention = $this->interventionService->show($id);

            return $this->success($intervention);
        } catch (\Exception $e) {
            return $this->error('Intervention introuvable.', 404);
        }
    }

    /**
     * PUT /api/interventions/{id}
     */
    public function update(InterventionRequest $request, int $id): JsonResponse
    {
        try {
            $updated = $this->interventionService->update($id, $request->validated(), auth()->user());

            return $this->success($updated, 'Intervention mise à jour.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /api/interventions/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->interventionService->delete($id, auth()->user());

            return $this->success(null, 'Intervention supprimée.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * PATCH /api/interventions/{id}/start  [Technicien]
     */
    public function start(int $id): JsonResponse
    {
        try {
            $intervention = $this->interventionService->start($id, auth()->user());

            return $this->success($intervention, 'Intervention démarrée.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * PATCH /api/interventions/{id}/complete  [Technicien]
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'report' => ['required', 'string', 'min:10'],
        ]);

        try {
            $intervention = $this->interventionService->complete($id, $request->report, auth()->user());

            return $this->success($intervention, 'Intervention clôturée.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/interventions/{id}/attachments
     */
    public function uploadAttachment(Request $request, int $id): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        try {
            $attachment = $this->attachmentService->upload(
                $request->file('file'),
                Intervention::class,
                $id,
                auth()->user()
            );

            return $this->success($attachment, 'Fichier uploadé.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
