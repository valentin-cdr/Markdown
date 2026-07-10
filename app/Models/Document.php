<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'content',
        'tags',
        'group_key',
        'user_id',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    /**
     * 🛡️ LE CERVEAU DE L'ÉTANCHÉITÉ
     */
    protected static function boot()
    {
        parent::boot();

        // 🚨 On ajoute un anti-slash \ devant Illuminate pour ne plus dépendre des imports du haut
        static::addGlobalScope('ancient_isolation', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('documents.group_key', session('active_group_key', 'retd'));
        });
    }

    // Le propriétaire du document
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Les utilisateurs avec qui le document est partagé
    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'document_user')
                    ->withPivot('can_edit')
                    ->withTimestamps();
    }

    public function getCleanPreviewAttribute()
    {
        if (empty($this->content)) return '';
        $text = preg_replace('/(#+\s+)|([\*\_\~]+)|(\[([^\]]+)\]\([^\)]+\))|(`[^`]+`)/', '$4', $this->content);
        $text = str_replace(["\r", "\n"], ' ', $text);
        return Str::limit($text, 60);
    }
}