<?php

namespace App\Services;

use App\Models\Employe;
use Carbon\Carbon;

class EmployeStatutService
{
    public static function verifierEtMettreAJourStatuts()
    {
        $today = Carbon::today();
        $employes = Employe::with(['affectations', 'conges'])->get();

        foreach ($employes as $employe) {
            $conge = $employe->conges->first(function ($c) use ($today) {
                return $today->between(Carbon::parse($c->date_debut), Carbon::parse($c->date_fin));
            });

            if ($conge) {
                if ($employe->statut !== 'congé') {
                    $employe->update(['statut' => 'congé']);
                }
                continue;
            }

            $derniereAffectation = $employe->affectations->sortByDesc('date_fin')->first();

            if ($derniereAffectation) {
                $dateFin = Carbon::parse($derniereAffectation->date_fin);
                $dateDebut = Carbon::parse($derniereAffectation->date_debut);
                $dureeTravail = $dateDebut->diffInDays($dateFin) + 1;
                $diff = $today->diffInDays($dateFin, false); // false pour résultat négatif si today < fin

                if ($diff < $dureeTravail && $diff >= 0) {
                    if ($employe->statut !== 'récupération') {
                        $employe->update(['statut' => 'récupération']);
                    }
                    continue;
                }
            }

            if ($employe->statut !== 'travail') {
                $employe->update(['statut' => 'travail']);
            }
        }
    }

    public static function getEmployesAvecRecup()
    {
        self::verifierEtMettreAJourStatuts();
        $today = Carbon::today();

        return Employe::with(['affectations', 'conges'])->get()->map(function ($emp) use ($today) {
            $joursRecup = 0;

            $derniereAffectation = $emp->affectations->sortByDesc('date_fin')->first();

            if ($derniereAffectation) {
                $debut = Carbon::parse($derniereAffectation->date_debut);
                $fin = Carbon::parse($derniereAffectation->date_fin);
                $duree = $debut->diffInDays($fin) + 1;
                $joursRecup = $duree;

                // Retirer jours écoulés depuis la fin de l'affectation
                $joursEcoules = $fin->diffInDays($today, false);
                if ($joursEcoules > 0) {
                    $joursRecup -= $joursEcoules;
                }

                // Retirer congés non justifiés pendant cette affectation
                foreach ($emp->conges as $c) {
                    if ($c->type === 'non justifié') {
                        $cDebut = Carbon::parse($c->date_debut);
                        $cFin = Carbon::parse($c->date_fin);

                        if ($cFin >= $debut && $cDebut <= $fin) {
                            $overlapStart = $cDebut->greaterThan($debut) ? $cDebut : $debut;
                            $overlapEnd = $cFin->lessThan($fin) ? $cFin : $fin;
                            $joursConge = $overlapStart->diffInDays($overlapEnd) + 1;
                            $joursRecup -= $joursConge;
                        }
                    }
                }
            }

            $emp->jours_recuperation_restants = max(0, $joursRecup);
            return $emp;
        });
    }
}
