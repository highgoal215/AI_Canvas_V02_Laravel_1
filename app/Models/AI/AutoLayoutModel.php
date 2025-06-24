<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AutoLayoutModel extends Model
{
    use HasFactory;

    protected $table = 'auto_layouts';

    protected $fillable = [
        'user_id',
        'content_type',
        'content_description',
        'layout_style',
        'aspect_ratio',
        'layout_json',
        'raw_response',
    ];

    protected $casts = [
        'layout_json' => 'array',
        'raw_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
