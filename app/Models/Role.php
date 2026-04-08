<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'label', 'description'];

    // Constantes pour les noms de rôles
    const ADMIN      = 'admin';
    const TECHNICIEN = 'technicien';
    const UTILISATEUR = 'utilisateur';

    /**
     * Un rôle peut appartenir à plusieurs utilisateurs.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
