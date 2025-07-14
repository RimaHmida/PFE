<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employe extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'numero',
        'fonction',
        'adresse',
        'statut', 
        'jours_recuperation',
    ];

    /**
     * Les présences journalières de l'employé
     */
    public function presences()
    {
        return $this->hasMany(PresenceJournaliere::class);
    }

    /**
     * Les affectations de l'employé
     * Pivot : id, dates réelles, employé de remplacement
     */
    public function affectations()
    {
        return $this->belongsToMany(AffectationListe::class, 'affectation_liste_employe')
            ->withPivot('id', 'date_debut_reelle', 'date_fin_reelle', 'remplace_par_employe_id')
            ->withTimestamps();
    }

    /**
     * Les congés de l'employé
     */
    public function conges()
    {
        return $this->hasMany(Conge::class);
    }
}
