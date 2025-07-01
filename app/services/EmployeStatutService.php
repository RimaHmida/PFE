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
                // 🔹 Vérifier s'il est en congé aujourd'hui
                $congeActuel = $employe->conges->first(function ($c) use ($today) {
                    return $today->between(Carbon::parse($c->date_debut), Carbon::parse($c->date_fin));
                });

                if ($congeActuel) {
                    if ($employe->statut !== 'congé') {
                        $employe->update(['statut' => 'congé']);
                    }
                    continue; // Si congé, on ne va pas plus loin
                }

                // 🔹 Dernière affectation
                $derniereAffect = $employe->affectations
                    ->filter(fn($a) => $a->pivot->date_debut_reelle && $a->pivot->date_fin_reelle)
                    ->sortByDesc(fn($a) => $a->pivot->date_fin_reelle)
                    ->first();

                if ($derniereAffect) {
                    $debut = Carbon::parse($derniereAffect->pivot->date_debut_reelle);
                    $fin = Carbon::parse($derniereAffect->pivot->date_fin_reelle);
                    $joursAffectation = $debut->diffInDays($fin) + 1;

                    // 🔹 Jours non justifiés pendant l'affectation
                    $joursNonJust = $employe->conges
                        ->where('type', 'non justifié')
                        ->reduce(function ($carry, $conge) use ($debut, $fin) {
                            $start = Carbon::parse($conge->date_debut);
                            $end = Carbon::parse($conge->date_fin);
                            return $carry + max(0, $start->diffInDays(min($end, $fin)) + 1);
                        }, 0);

                    $joursTravail = $joursAffectation - $joursNonJust;

                    // 🔹 Période de récupération
                    $recupDebut = $fin->copy()->addDay();
                    $joursDepuisRecup = $recupDebut->diffInDays($today, false);

                    if ($today->lte($fin)) {
                        $employe->statut !== 'travail' && $employe->update(['statut' => 'travail']);
                        $employe->jours_recuperation_restants = 0;
                    } elseif ($joursDepuisRecup < $joursTravail) {
                        $employe->statut !== 'récupération' && $employe->update(['statut' => 'récupération']);
                        $employe->jours_recuperation_restants = $joursTravail - $joursDepuisRecup;
                    } else {
                        $employe->statut !== 'standby' && $employe->update(['statut' => 'standby']);
                        $employe->jours_recuperation_restants = 0;
                    }

                    $employe->save();

                } else {
                    // 🔹 Aucun travail récent = standby
                    if ($employe->statut !== 'standby') {
                        $employe->update(['statut' => 'standby']);
                    }
                    $employe->jours_recuperation_restants = 0;
                    $employe->save();
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

                $joursCongeNonJust = $employe->conges
                    ->where('type', 'non justifié')
                    ->reduce(function ($carry, $conge) use ($debut, $fin) {
                        $start = Carbon::parse($conge->date_debut);
                        $end = Carbon::parse($conge->date_fin);
                        return $carry + max(0, $start->diffInDays(min($end, $fin)) + 1);
                    }, 0);

                $joursTravail = $joursAffectation - $joursCongeNonJust;

                $recupDebut = $fin->copy()->addDay();
                $joursDepuisRecup = $recupDebut->diffInDays(Carbon::today(), false);

                if ($joursDepuisRecup < $joursTravail && $joursDepuisRecup >= 0) {
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
