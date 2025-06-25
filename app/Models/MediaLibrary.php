<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaLibrary extends Model
{
    protected $fillable = [
        'name',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'type',
        'category',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tags' => 'array',
        'file_size' => 'integer',
    ];

    /**
     * Scope to get only active media
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by category
     */
    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the full URL for the media file
     */
    public function getUrlAttribute()
    {
        return \Storage::disk('public')->url($this->file_path);
    }

    /**
     * Get file size in human readable format
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
} 