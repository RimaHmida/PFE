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
    public function presences()
{
    return $this->hasMany(PresenceJournaliere::class);
}

public function affectations()
{
    return $this->belongsToMany(\App\Models\AffectationListe::class, 'affectation_liste_employe');
}




public function conges()
{
    return $this->hasMany(Conge::class);
}

}
