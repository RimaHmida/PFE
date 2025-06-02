<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PresenceJournaliere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PresenceValidationController extends Controller
{
    /**
     * Show unvalidated presences for finished periods
     */
    public function index()
    {
        if (!in_array(Auth::user()->role, ['manager', 'administrateur_it'])) {
            abort(403, 'Accès refusé');
        }

        $today = Carbon::now()->toDateString(); // ✅ Fix: define $today

        // ✅ Get all unvalidated, present presences for periods that are finished
        $presences = PresenceJournaliere::with(['employe', 'affectationListe.site'])
            ->where('validated_by_manager', false)
            ->where('present', true)
            ->whereHas('affectationListe', function ($query) use ($today) {
                $query->whereDate('date_fin', '<', $today); // ✅ Must be < today, period must be over
            })
            ->orderBy('affectation_liste_id')
            ->orderBy('employe_id')
            ->orderBy('date')
            ->get();

        return view('manager.presences.index', compact('presences'));
    }

    /**
     * Validate selected presences
     */
    public function validateAll(Request $request)
    {
        if (!in_array(Auth::user()->role, ['manager', 'administrateur_it'])) {
            abort(403, 'Accès refusé');
        }

        $ids = $request->input('validate_ids', []);

        if (count($ids) > 0) {
            PresenceJournaliere::whereIn('id', $ids)
                ->update(['validated_by_manager' => true]);

            return redirect()->route('manager.presences.index')
                ->with('success', 'Présences sélectionnées validées.');
        }

        return redirect()->route('manager.presences.index')
            ->with('success', 'Aucune présence sélectionnée.');
    }
}
