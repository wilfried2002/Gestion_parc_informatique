<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploaded_by', 'attachable_type', 'attachable_id',
        'original_name', 'file_path', 'file_type', 'file_size',
    ];

    protected $appends = ['url', 'size_human'];

    // ─── Relations ──────────────────────────────────────────────────────────

    /** Relation polymorphique (ticket ou intervention) */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    /** URL publique du fichier */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /** Taille lisible (ex: 2.4 MB) */
    public function getSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size  = $this->file_size;
        $unit  = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }
}
