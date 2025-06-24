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

    //public function employes()
    //{
       // return $this->belongsToMany(Employe::class, 'affectation_liste_employe');
    //}

    public function employes()
    {
        return $this->belongsToMany(Employe::class, 'affectation_liste_employe')
                    ->withPivot('id as pivot_id', 'date_debut_reelle', 'date_fin_reelle');
    }
    


    public function auteur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function getRecuperationRestanteParEmploye()
    {
        $resultats = [];
        $today = \Carbon\Carbon::today();
    
        foreach ($this->employes as $employe) {
            $dateDebut = \Carbon\Carbon::parse($employe->pivot->date_debut_reelle ?? $this->date_debut);
            $dateFin = \Carbon\Carbon::parse($employe->pivot->date_fin_reelle ?? $this->date_fin);
            $dureeTravail = $dateDebut->diffInDays($dateFin) + 1;
    
            if ($today->lte($dateFin)) {
                $joursRestants = 0; // récupération pas encore commencée
            } else {
                $joursDepuisFin = $dateFin->diffInDays($today);
                $joursRestants = max(0, $dureeTravail - $joursDepuisFin);
            }
    
            $resultats[] = [
                'employe_id' => $employe->id,
                'nom' => $employe->nom,
                'prenom' => $employe->prenom,
                'jours_recuperation_restants' => $joursRestants,
            ];
        }
    
        return $resultats;
    }
    
}
