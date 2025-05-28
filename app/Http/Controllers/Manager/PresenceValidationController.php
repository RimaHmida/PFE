<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PresenceJournaliere;
use Illuminate\Support\Facades\Auth;

class PresenceValidationController extends Controller
{
    public function index()
    {
        if (!in_array(Auth::user()->role, ['manager', 'administrateur_it'])) {
            abort(403, 'Accès refusé');
        }

        $today = now()->toDateString();

        $presences = PresenceJournaliere::with(['employe', 'affectationListe.site'])
            ->whereDate('date', $today)
            ->where('validated_by_manager', false)
            ->get();

        return view('manager.presences.index', compact('presences', 'today'));
    }

    public function validateAll()
    {
        if (!in_array(Auth::user()->role, ['manager', 'administrateur_it'])) {
            abort(403, 'Accès refusé');
        }

        $today = now()->toDateString();

        PresenceJournaliere::whereDate('date', $today)
            ->update(['validated_by_manager' => true]);

        return redirect()->route('manager.presences.index')->with('success', 'Présences validées');
    }
}
