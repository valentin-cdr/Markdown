<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Les attributs assignables en masse.
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'group_ldap',
        'franchise_id',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Tes documents (Propriétaire)
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Les documents partagés avec toi (Invité)
    public function sharedDocuments()
    {
        return $this->belongsToMany(Document::class, 'document_user')
                    ->withPivot('can_edit')
                    ->withTimestamps();
    }

    /**
     * Résolution du nom de groupe pour l'affichage
     */
    public function getGroupNameAttribute()
    {
        // 1. Vrai groupe en base de données (si synchronisé)
        if (!empty($this->attributes['group_name'])) {
            return strtoupper($this->attributes['group_name']);
        }

        // 2. Déduction dynamique via la session Keycloak pour l'utilisateur actuellement connecté
        if (auth()->check() && auth()->id() === $this->id) {
            $sessionGroups = session('keycloak_groups', []);
            
            if (!empty($sessionGroups)) {
                // Priorité au groupe admin 'retd' s'il est présent
                if (in_array('retd', $sessionGroups)) {
                    return 'RETD';
                }
                
                // Sinon, on prend le premier groupe de la liste renvoyée par Keycloak
                return strtoupper($sessionGroups[0]); 
            }
        }

        // 3. Valeur de secours par défaut
        return 'GÉNÉRAL / SANS GROUPE';
    }
}