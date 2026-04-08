<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affectation extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id', 'user_id', 'assigned_by',
        'quantity', 'notes', 'status',
        'assigned_at', 'returned_at',
    ];

    protected $casts = [
        'assigned_at'  => 'datetime',
        'returned_at'  => 'datetime',
    ];

    const STATUS_ACTIVE   = 'active';
    const STATUS_RETURNED = 'returned';
    const STATUS_LOST     = 'lost';

    // ─── Relations ──────────────────────────────────────────────────────────

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
