<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AffectationListe;
use App\Models\Employe;
use App\Models\PresenceJournaliere;
use Illuminate\Support\Facades\Auth;

class PresenceJournaliereController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $affectations = AffectationListe::with(['employes', 'site'])
            ->whereDate('date_debut', '<=', $today)
            ->whereDate('date_fin', '>=', $today)
            ->get();

        return view('secretaire.presences.index', compact('affectations', 'today'));
    }

    public function store(Request $request)
    {
        $today = now()->toDateString();
        foreach ($request->input('presences', []) as $affectationId => $employes) {
            foreach ($employes as $employeId => $present) {
                PresenceJournaliere::updateOrCreate(
                    [
                        'affectation_liste_id' => $affectationId,
                        'employe_id' => $employeId,
                        'date' => $today,
                    ],
                    [
                        'present' => $present == 1,
                        'recorded_by' => Auth::id(),
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Présences enregistrées.');
    }
}
