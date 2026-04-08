<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachmentService) {}

    /**
     * GET /api/attachments/{id}/download
     * Télécharge un fichier.
     */
    public function download(int $id): BinaryFileResponse|JsonResponse
    {
        try {
            $filePath = $this->attachmentService->getFilePath($id);

            return response()->download($filePath);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * DELETE /api/attachments/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->attachmentService->delete($id, auth()->user());

            return $this->success(null, 'Fichier supprimé.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
