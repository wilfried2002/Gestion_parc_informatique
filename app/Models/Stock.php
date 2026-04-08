<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'reference', 'serial_number', 'category',
        'description', 'quantity', 'quantity_min', 'status',
        'location', 'brand', 'model', 'purchase_date',
        'purchase_price', 'warranty_end',
    ];

    protected $casts = [
        'purchase_date'  => 'date',
        'warranty_end'   => 'date',
        'purchase_price' => 'decimal:2',
    ];

    const CATEGORY_ORDINATEUR   = 'ordinateur';
    const CATEGORY_IMPRIMANTE   = 'imprimante';
    const CATEGORY_SERVEUR      = 'serveur';
    const CATEGORY_RESEAU       = 'reseau';
    const CATEGORY_PERIPHERIQUE = 'peripherique';
    const CATEGORY_CONSOMMABLE  = 'consommable';
    const CATEGORY_AUTRE        = 'autre';

    const STATUS_DISPONIBLE  = 'disponible';
    const STATUS_AFFECTE     = 'affecte';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_HORS_SERVICE = 'hors_service';

    // ─── Relations ──────────────────────────────────────────────────────────

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function activeAffectations(): HasMany
    {
        return $this->hasMany(Affectation::class)->where('status', 'active');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /** Matériel dont le stock est en dessous du seuil minimum */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= quantity_min');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->quantity_min;
    }

    public function isWarrantyExpired(): bool
    {
        return $this->warranty_end && $this->warranty_end->isPast();
    }
}
