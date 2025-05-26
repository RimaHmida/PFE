<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affectation extends Model
{
    protected $fillable = [
        'site_id',
        'employe_id',
        'date_debut',
        'date_fin',
        'created_by',
        'statut',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}
