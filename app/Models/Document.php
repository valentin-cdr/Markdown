<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'title', 'content', 'tags', 'group_key'];

    protected $casts = [
        'tags' => 'array',
    ];

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