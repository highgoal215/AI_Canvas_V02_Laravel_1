<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class BackgroundRemoverModel extends Model
{
    protected $table = 'background_removals';

    protected $fillable = [
        'user_id',
        'original_url',
        'result_url',
        'raw_response',
    ];
}
