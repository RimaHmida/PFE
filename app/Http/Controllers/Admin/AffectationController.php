<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Employe;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class AffectationController extends Controller
{
    public function index()
    {
        $affectations = Affectation::with('employe', 'site')->get();
        $employes = Employe::where('statut', 'travail')->get();
        $sites = Site::all();

        return view('admin.affectations.index', compact('affectations', 'employes', 'sites'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'employe_id' => 'required|exists:employes,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        Affectation::create([
            'site_id' => $validated['site_id'],
            'employe_id' => $validated['employe_id'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'created_by' => Auth::id(),
        ]);

        return Redirect::route('admin.affectations.index')->with('success', 'Affectation ajoutée avec succès.');
    }

    public function destroy(Affectation $affectation)
    {
        $affectation->delete();

        return Redirect::route('admin.affectations.index')->with('success', 'Affectation supprimée avec succès.');
    }
}
