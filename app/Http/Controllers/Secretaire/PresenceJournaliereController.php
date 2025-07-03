<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AffectationListe;
use App\Models\PresenceJournaliere;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PresenceJournaliereController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $affectations = AffectationListe::with([
            'site',
            'employes' => function ($query) {
                $query->select('employes.id', 'nom', 'prenom')
                      ->withPivot('date_debut_reelle', 'date_fin_reelle')
                      ->with(['presences']);
            }
        ])
        ->whereDate('date_debut', '<=', $today)
        ->whereDate('date_fin', '>=', $today)
        ->get();

        $data = $affectations->map(function ($aff) {
            // Range général de l’affectation
            $rangeAffectation = $this->generateDateRange($aff->date_debut, $aff->date_fin);

            return [
                'id' => $aff->id,
                'site' => $aff->site,
                'date_debut' => $aff->date_debut,
                'date_fin' => $aff->date_fin,
                'dates' => $rangeAffectation,
                'employes' => $aff->employes->map(function ($emp) {
                    // Range propre à cet employé
                    $datesEmp = $this->generateDateRange($emp->pivot->date_debut_reelle, $emp->pivot->date_fin_reelle);
                    return [
                        'id' => $emp->id,
                        'nom' => $emp->nom,
                        'prenom' => $emp->prenom,
                        'pivot' => $emp->pivot,
                        'presences' => $emp->presences,
                        'dates' => $datesEmp
                    ];
                })
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'affectations' => $data,
            ],
        ]);
    }

    private function generateDateRange($start, $end)
    {
        $dates = [];
        $current = Carbon::parse($start);
        $end = Carbon::parse($end);
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }
        return $dates;
    }

    public function store(Request $request)
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Utilisateur non authentifié.'], 401);
        }

        $presences = $request->input('presences');

        foreach ($presences as $affectationId => $dates) {
            foreach ($dates as $date => $employes) {
                foreach ($employes as $employeId => $present) {
                    PresenceJournaliere::updateOrCreate(
                        [
                            'affectation_liste_id' => $affectationId,
                            'employe_id' => $employeId,
                            'date' => $date,
                        ],
                        [
                            'present' => $present,
                            'recorded_by' => $userId,
                        ]
                    );
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Présences enregistrées.']);
    }
}
