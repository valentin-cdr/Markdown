<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Les attributs assignables en masse.
     */
    protected $fillable = [
        'name',
        'username', // 👈 AJOUTÉ
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
        // 👉 AJOUT : ->withPivot('can_edit')
        return $this->belongsToMany(Document::class, 'document_user')
                    ->withPivot('can_edit')
                    ->withTimestamps();
    }

    /**
     * 🚀 TRICHE DE DÉVELOPPEMENT : Simuler un nom de groupe basé sur l'identifiant
     */
    public function getGroupNameAttribute()
    {
        // 1. Vrai groupe en base de données (si tu l'enregistres un jour)
        if (!empty($this->attributes['group_name'])) {
            return strtoupper($this->attributes['group_name']);
        }

        // 2. Les comptes locaux : on utilise le VRAI nom de tes groupes de test
        if ($this->username === 'test_invite') {
            return 'TEST';
        }
        if ($this->username === 'test_invite_2') {
            return 'MARKETING';
        }

        // 3. 🚀 TON COMPTE KEYCLOAK : On récupère dynamiquement tes vrais groupes !
        if (auth()->check() && auth()->id() === $this->id) {
            $sessionGroups = session('keycloak_groups', []);
            
            // Si tu as au moins un groupe dans ta session
            if (!empty($sessionGroups)) {
                // On cherche d'abord si 'retd' existe dans le tableau
                if (in_array('retd', $sessionGroups)) {
                    return 'RETD';
                }
                
                // S'il n'y a pas 'retd', on prend le premier groupe de la liste par défaut
                return strtoupper($sessionGroups[0]); 
            }
        }

        // 4. Par défaut
        return 'GÉNÉRAL / SANS GROUPE';
    }
}