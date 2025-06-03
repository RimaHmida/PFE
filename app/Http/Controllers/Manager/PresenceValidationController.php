<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PresenceJournaliere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PresenceValidationController extends Controller
{
    public function index()
    {
        if (!in_array(Auth::user()->role, ['manager', 'administrateur_it'])) {
            abort(403, 'Accès refusé');
        }

        $today = Carbon::now()->toDateString();

        $presences = PresenceJournaliere::with(['employe', 'affectationListe.site'])
            ->where('validated_by_manager', false)
            ->where('present', true)
            ->whereHas('affectationListe', function ($query) use ($today) {
                $query->whereDate('date_fin', '<', $today);
            })
            ->orderBy('affectation_liste_id')
            ->orderBy('employe_id')
            ->orderBy('date')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $presences,
        ], 200);
    }

    public function validateAll(Request $request)
    {
        if (!in_array(Auth::user()->role, ['manager', 'administrateur_it'])) {
            abort(403, 'Accès refusé');
        }

        $ids = $request->input('validate_ids', []);

        if (count($ids) > 0) {
            PresenceJournaliere::whereIn('id', $ids)
                ->update(['validated_by_manager' => true]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Présences sélectionnées validées.'
            ], 200);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Aucune présence sélectionnée.'
        ], 200);
    }
}
