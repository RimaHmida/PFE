<?php
namespace App\Services;

use App\Models\Employe;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeStatutService
{
    public static function verifierEtMettreAJourStatuts()
    {
        $today = Carbon::today();
        $employes = Employe::with(['affectations', 'conges'])->get();

        foreach ($employes as $employe) {
            try {
                // Vérifier congé en cours
                $congeActuel = $employe->conges->first(function ($c) use ($today) {
                    return $today->between(Carbon::parse($c->date_debut), Carbon::parse($c->date_fin));
                });

                if ($congeActuel) {
                    $employe->update([
                        'statut' => 'congé',
                        'jours_recuperation_restants' => 0
                    ]);
                    continue;
                }

                // Dernière affectation
                $derniereAffect = $employe->affectations
                    ->filter(fn($a) => $a->pivot->date_debut_reelle && $a->pivot->date_fin_reelle)
                    ->sortByDesc(fn($a) => $a->pivot->date_fin_reelle)
                    ->first();

                if ($derniereAffect) {
                    $debut = Carbon::parse($derniereAffect->pivot->date_debut_reelle);
                    $fin = Carbon::parse($derniereAffect->pivot->date_fin_reelle);
                    $joursAffectation = $debut->diffInDays($fin) + 1;

                    // Calcul des jours non justifiés pendant l'affectation
                    $joursNonJust = $employe->conges
                        ->where('type', 'non justifié')
                        ->reduce(function ($carry, $conge) use ($debut, $fin) {
                            $start = Carbon::parse($conge->date_debut)->max($debut);
                            $end = Carbon::parse($conge->date_fin)->min($fin);
                            return $carry + max(0, $start->diffInDays($end) + 1);
                        }, 0);

                    $joursTravail = max(0, $joursAffectation - $joursNonJust);

                    // Calcul récupération
                    $recupDebut = $fin->copy()->addDay();
                    $joursDepuisRecup = $recupDebut->diffInDays($today, false);

                    if ($today->between($debut, $fin)) {
                        // En travail
                        $employe->update([
                            'statut' => 'travail',
                            'jours_recuperation_restants' => 0
                        ]);
                    } elseif ($joursDepuisRecup >= 0 && $joursDepuisRecup < $joursTravail) {
                        // En récupération
                        $employe->update([
                            'statut' => 'récupération',
                            'jours_recuperation_restants' => $joursTravail - $joursDepuisRecup
                        ]);
                    } else {
                        // Fin récupération
                        $employe->update([
                            'statut' => 'standby',
                            'jours_recuperation_restants' => 0
                        ]);
                    }

                } else {
                    // Pas d'affectation récente
                    $employe->update([
                        'statut' => 'standby',
                        'jours_recuperation_restants' => 0
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Erreur statut employé ID {$employe->id} : " . $e->getMessage());
            }
        }
    }

    public static function getEmployesAvecRecup()
    {
        self::verifierEtMettreAJourStatuts();

        $employes = Employe::with(['affectations', 'conges'])->get();

        foreach ($employes as $employe) {
            $derniereAffect = $employe->affectations
                ->filter(fn($a) => $a->pivot->date_debut_reelle && $a->pivot->date_fin_reelle)
                ->sortByDesc(fn($a) => $a->pivot->date_fin_reelle)
                ->first();

            if ($derniereAffect) {
                $debut = Carbon::parse($derniereAffect->pivot->date_debut_reelle);
                $fin = Carbon::parse($derniereAffect->pivot->date_fin_reelle);
                $joursAffectation = $debut->diffInDays($fin) + 1;

                $joursNonJust = $employe->conges
                    ->where('type', 'non justifié')
                    ->reduce(function ($carry, $conge) use ($debut, $fin) {
                        $start = Carbon::parse($conge->date_debut)->max($debut);
                        $end = Carbon::parse($conge->date_fin)->min($fin);
                        return $carry + max(0, $start->diffInDays($end) + 1);
                    }, 0);

                $joursTravail = max(0, $joursAffectation - $joursNonJust);

                $recupDebut = $fin->copy()->addDay();
                $joursDepuisRecup = $recupDebut->diffInDays(Carbon::today(), false);

                if ($joursDepuisRecup >= 0 && $joursDepuisRecup < $joursTravail) {
                    $employe->jours_recuperation_restants = $joursTravail - $joursDepuisRecup;
                } else {
                    $employe->jours_recuperation_restants = 0;
                }

            } else {
                $employe->jours_recuperation_restants = 0;
            }
        }

        return $employes;
    }
}
