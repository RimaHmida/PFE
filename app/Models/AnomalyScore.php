<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnomalyScore extends Model
{
    protected $fillable = ['employe_id','week_start','score','extras'];

    protected $casts = [
        'week_start' => 'date',
        'extras' => 'array',
    ];

    public function employe()
    {
        return $this->belongsTo(\App\Models\Employe::class, 'employe_id');
    }
}
