<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AffectationListe;
use App\Models\PresenceJournaliere;
use Illuminate\Support\Facades\Auth;

class PresenceJournaliereController extends Controller
{
    /**
     * Affiche uniquement les employés affectés aujourd'hui et dont la présence n'a pas encore été enregistrée.
     */
    public function index()
    {
        $today = now()->toDateString();

        // Récupère les listes d'affectation actives aujourd'hui, avec les employés n'ayant pas encore de présence enregistrée aujourd'hui
        $affectations = AffectationListe::with(['site', 'employes' => function ($query) use ($today) {
            $query->whereDoesntHave('presences', function ($q) use ($today) {
                $q->whereDate('date', $today);
            });
        }])
        ->whereDate('date_debut', '<=', $today)
        ->whereDate('date_fin', '>=', $today)
        ->get();

        return view('secretaire.presences.index', compact('affectations', 'today'));
    }

    /**
     * Enregistre uniquement les employés qui ont été cochés comme présents.
     */
    public function store(Request $request)
    {
        $today = now()->toDateString();
        $userId = Auth::id();

        // Récupère uniquement les employés cochés
        $checkedPresences = $request->input('presences', []);

        // Enregistre uniquement ceux qui ont été cochés comme présents
        foreach ($checkedPresences as $affectationId => $employes) {
            foreach ($employes as $employeId => $value) {
                PresenceJournaliere::updateOrCreate(
                    [
                        'affectation_liste_id' => $affectationId,
                        'employe_id' => $employeId,
                        'date' => $today,
                    ],
                    [
                        'present' => true,
                        'recorded_by' => $userId,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Présences enregistrées.');
    }
}
