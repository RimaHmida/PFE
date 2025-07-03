<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AffectationListe;
use App\Models\PresenceJournaliere;
use Illuminate\Support\Facades\Auth;

class PresenceJournaliereController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $affectations = AffectationListe::with(['site', 'employes' => function($query) {
            $query->with(['presences' => function($q) {
                $q->whereDate('date', now()->toDateString());
            }]);
        }])
        ->whereDate('date_debut', '<=', $today)
        ->whereDate('date_fin', '>=', $today)
        ->get();

        $data = $affectations->map(function ($aff) {
            return [
                'id' => $aff->id,
                'site' => $aff->site,
                'date_debut' => $aff->date_debut,
                'date_fin' => $aff->date_fin,
                'dates' => $this->generateDateRange($aff->date_debut, $aff->date_fin),
                'employes' => $aff->employes,
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
        $current = \Carbon\Carbon::parse($start);
        $end = \Carbon\Carbon::parse($end);
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
                    PresenceJournaliere::firstOrCreate(
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
