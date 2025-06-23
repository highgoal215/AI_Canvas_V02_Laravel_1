<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class AutoLayoutModel extends Model
{
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
}
