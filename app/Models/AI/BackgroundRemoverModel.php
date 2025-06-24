<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class BackgroundRemoverModel extends Model
{
    use HasFactory;

    protected $table = 'background_removals';

    protected $fillable = [
        'user_id',
        'original_url',
        'result_url',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}