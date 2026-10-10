<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Website extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image_path',
        'url',
        'alt_url',
        'sort_order',
    ];

    protected $appends = ['image_url', 'location'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }

    /**
     * Which home-page tab the card sits in: 'minipc' when the URL points at the
     * mini-PC (its LAN address or its tailnet name), 'live' for everything else.
     */
    public function getLocationAttribute(): string
    {
        $host = strtolower((string) parse_url($this->url, PHP_URL_HOST));

        return $host === '192.168.0.164' || str_ends_with($host, '.ts.net')
            ? 'minipc'
            : 'live';
    }
}
