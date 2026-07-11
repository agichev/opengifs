<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gif extends Model
{
    protected $fillable = [
        'title',
        'keywords',
        'original_name',
        'imgbb_url',
        'imgbb_delete_url',
        'proxy_path',
        'file_size',
        'mime_type',
        'views',
    ];

    public function scopeSearch($query, string $term)
    {
        return $query->where('keywords', 'LIKE', "%{$term}%")
            ->orWhere('title', 'LIKE', "%{$term}%");
    }

    public function scopeTrending($query)
    {
        return $query->orderBy('views', 'desc');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
