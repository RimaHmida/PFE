<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\PresenceJournaliere;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeStatutService
{
    public static function verifierEtMettreAJourStatuts()
    {
        $today = Carbon::today();
        $employes = Employe::with(['affectations', 'conges', 'presencesJournaliere'])->get();

        foreach ($employes as $employe) {
            try {
                // ✅ Étape 1 : vérifier si l'employé est actuellement en congé justifié/maladie
                $congeActuel = $employe->conges->first(function ($c) use ($today) {
                    return $today->between(Carbon::parse($c->date_debut), Carbon::parse($c->date_fin))
                        && in_array($c->type, ['justifié', 'maladie']);
                });

                if ($congeActuel) {
                    $employe->update([
                        'statut' => 'congé',
                        'jours_recuperation_restants' => $employe->jours_recuperation_restants
                    ]);
                    continue;
                }

                // ✅ Étape 2 : dernière affectation avec dates réelles
                $derniereAffect = $employe->affectations
                    ->filter(function ($a) {
                        return $a->pivot && !empty($a->pivot->date_debut_reelle) && !empty($a->pivot->date_fin_reelle);
                    })
                    ->sortByDesc(fn($a) => $a->pivot->date_fin_reelle)
                    ->first();

                if ($derniereAffect && $derniereAffect->pivot) {
                    $debut = Carbon::parse($derniereAffect->pivot->date_debut_reelle);
                    $fin = Carbon::parse($derniereAffect->pivot->date_fin_reelle);
                    $joursAffectation = $debut->diffInDays($fin) + 1;

                    $presences = PresenceJournaliere::where('employe_id', $employe->id)
                        ->where('affectation_liste_id', $derniereAffect->id)
                        ->whereBetween('date', [$debut, $fin])
                        ->get();

                    $absencesNonJustifiees = 0;
                    foreach ($presences as $presence) {
                        $date = $presence->date;
                        $congeJustifie = $employe->conges->first(function ($c) use ($date) {
                            return in_array($c->type, ['justifié', 'maladie']) &&
                                Carbon::parse($date)->between($c->date_debut, $c->date_fin);
                        });

                        if (!$presence->present && !$congeJustifie) {
                            $absencesNonJustifiees++;
                        }
                    }

                    $joursTravailEffectif = max(0, $joursAffectation - $absencesNonJustifiees);
                    $recupDebut = $fin->copy()->addDay();
                    $joursDepuisFin = $recupDebut->diffInDays($today, false);

                    if ($joursTravailEffectif === 0) {
                        // Aucun jour travaillé, tout est absence non justifiée → standby
                        $employe->update([
                            'statut' => 'standby',
                            'jours_recuperation_restants' => 0
                        ]);
                    } elseif ($today->lte($fin)) {
                        // Période d'affectation en cours
                        $employe->update([
                            'statut' => 'travail',
                            'jours_recuperation_restants' => $joursTravailEffectif
                        ]);
                    } elseif ($joursDepuisFin >= 0 && $joursDepuisFin < $joursTravailEffectif) {
                        // Période de récupération
                        $employe->update([
                            'statut' => 'récupération',
                            'jours_recuperation_restants' => $joursTravailEffectif - $joursDepuisFin
                        ]);
                    } else {
                        // Après la récupération
                        $employe->update([
                            'statut' => 'standby',
                            'jours_recuperation_restants' => 0
                        ]);
                    }
                } else {
                    // Aucun historique d'affectation
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

        return Employe::with(['affectations', 'conges', 'presencesJournaliere'])->get();
    }
} 