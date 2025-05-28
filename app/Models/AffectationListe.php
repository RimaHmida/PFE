<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffectationListe extends Model
{
    protected $fillable = [
        'site_id',
        'date_debut',
        'date_fin',
        'statut',
        'created_by',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function employes()
    {
        return $this->belongsToMany(Employe::class, 'affectation_employes');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
