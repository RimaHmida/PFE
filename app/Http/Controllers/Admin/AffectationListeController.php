<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffectationListe;
use App\Models\Employe;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class AffectationListeController extends Controller
{
    public function index()
    {
        $listes = AffectationListe::with('site', 'employes')->get();
        $employes = Employe::where('statut', 'travail')->get();
        $sites = Site::all();

        return view('admin.affectations.index', compact('listes', 'employes', 'sites'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_id' => 'required|exists:sites,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'employes' => 'required|array|min:1',
        ]);

        $liste = AffectationListe::create([
            'site_id' => $request->site_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'created_by' => Auth::id(),
        ]);

        $liste->employes()->attach($request->employes);

        return Redirect::route('admin.affectation_listes.index')->with('success', 'Liste créée avec succès.');
    }

    public function destroy(AffectationListe $affectationListe)
    {
        $affectationListe->delete();
        return Redirect::route('admin.affectation_listes.index')->with('success', 'Liste supprimée.');
    }
}
