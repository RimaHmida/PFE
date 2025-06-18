<?php
namespace App\Services;

use App\Models\Employe;
use Carbon\Carbon;

class EmployeStatutService
{
    public static function verifierEtMettreAJourStatuts()
    {
        $employes = Employe::all();

        foreach ($employes as $employe) {
            $derniereAffectation = $employe->affectations()
                ->orderByDesc('date_fin')
                ->first();

            if (!$derniereAffectation) {
                // ❗ Pas d’affectation → statut = travail
                if ($employe->statut !== 'travail') {
                    $employe->update(['statut' => 'travail']);
                }
                continue;
            }

            $dateDebut = Carbon::parse($derniereAffectation->date_debut);
            $dateFin = Carbon::parse($derniereAffectation->date_fin);
            $nbJoursTravail = $dateDebut->diffInDays($dateFin) + 1;

            $dateDebutRecup = $dateFin->copy()->addDay();
            $dateFinRecup = $dateFin->copy()->addDays($nbJoursTravail);

            if (now()->between($dateDebutRecup, $dateFinRecup)) {
                // ⏳ En récupération
                if ($employe->statut !== 'récupération') {
                    $employe->update(['statut' => 'récupération']);
                }
            } elseif (now()->greaterThan($dateFinRecup)) {
                // ✅ Récupération terminée
                if ($employe->statut !== 'travail') {
                    $employe->update(['statut' => 'travail']);
                }
            } else {
                // 🛠 Encore en période de travail → garder 'travail'
                if ($employe->statut !== 'travail') {
                    $employe->update(['statut' => 'travail']);
                }
            }
        }
    }
}
