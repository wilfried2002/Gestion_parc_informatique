<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    const UPLOAD_DIRECTORY = 'uploads';
    const MAX_FILE_SIZE_MB = 10;
    const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'zip', 'txt'];

    /**
     * Upload un fichier et crée l'enregistrement en base.
     */
    public function upload(UploadedFile $file, string $modelClass, int $modelId, User $uploader): Attachment
    {
        $this->validateFile($file);

        $path = $file->store(self::UPLOAD_DIRECTORY, 'public');

        return Attachment::create([
            'uploaded_by'     => $uploader->id,
            'attachable_type' => $modelClass,
            'attachable_id'   => $modelId,
            'original_name'   => $file->getClientOriginalName(),
            'file_path'       => $path,
            'file_type'       => $file->getMimeType(),
            'file_size'       => $file->getSize(),
        ]);
    }

    /**
     * Upload plusieurs fichiers en lot.
     */
    public function uploadMany(array $files, string $modelClass, int $modelId, User $uploader): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $attachments[] = $this->upload($file, $modelClass, $modelId, $uploader);
        }

        return $attachments;
    }

    /**
     * Supprime un fichier et son enregistrement.
     */
    public function delete(int $attachmentId, User $user): void
    {
        $attachment = Attachment::findOrFail($attachmentId);

        // Seul l'uploader ou un admin peut supprimer
        if ($attachment->uploaded_by !== $user->id && !$user->isAdmin()) {
            throw new \Exception("Permission refusée.", 403);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
    }

    /**
     * Retourne le chemin de stockage pour le téléchargement.
     */
    public function getFilePath(int $attachmentId): string
    {
        $attachment = Attachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            throw new \Exception("Fichier introuvable.", 404);
        }

        return storage_path("app/public/{$attachment->file_path}");
    }

    // ─── Private ────────────────────────────────────────────────────────────

    private function validateFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \Exception("Type de fichier non autorisé : {$extension}", 422);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE_MB * 1024 * 1024) {
            throw new \Exception("Le fichier dépasse la taille maximale de " . self::MAX_FILE_SIZE_MB . " MB.", 422);
        }
    }
}
