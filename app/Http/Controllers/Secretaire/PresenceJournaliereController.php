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

        $affectations = AffectationListe::with(['site', 'employes' => function ($query) use ($today) {
            $query->whereDoesntHave('presences', function ($q) use ($today) {
                $q->whereDate('date', $today);
            });
        }])
        ->whereDate('date_debut', '<=', $today)
        ->whereDate('date_fin', '>=', $today)
        ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'affectations' => $affectations,
                'today'        => $today,
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        // Récupère l'ID de l'utilisateur authentifié
        $userId = Auth::id(); // ou $request->user()->id si le middleware d'authentification est utilisé

        // Vérifie si l'utilisateur est authentifié
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Utilisateur non authentifié.'], 401);
        }

        $presences = $request->input('presences');
        foreach ($presences as $affectationListeId => $presentGroup) {
            foreach ($presentGroup as $employeId => $present) {
                PresenceJournaliere::updateOrCreate(
                    [
                        'affectation_liste_id' => $affectationListeId,
                        'employe_id'           => $employeId,
                        'date'                 => date('Y-m-d'),
                    ],
                    [
                        'present'    => $present,
                        'recorded_by'=> $userId, // ajoute l'ID de l'utilisateur
                    ]
                );
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Présences enregistrées.']);
    }
}
