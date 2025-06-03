<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffectationListe;
use App\Models\Employe;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffectationListeController extends Controller
{
    public function index()
    {
        $listes = AffectationListe::with('site', 'employes')->get();
        $employes = Employe::where('statut', 'travail')->get();
        $sites = Site::all();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'listes'   => $listes,
                'employes' => $employes,
                'sites'    => $sites,
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id'    => 'required|exists:sites,id',
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after_or_equal:date_debut',
            'employes'   => 'required|array|min:1',
        ]);

        $liste = AffectationListe::create([
            'site_id'    => $validated['site_id'],
            'date_debut' => $validated['date_debut'],
            'date_fin'   => $validated['date_fin'],
            'created_by' => Auth::id(),
        ]);

        $liste->employes()->attach($validated['employes']);

        return response()->json([
            'status'  => 'success',
            'data'    => $liste,
            'message' => 'Liste créée avec succès.'
        ], 201);
    }

    public function destroy(AffectationListe $affectationListe)
    {
        $affectationListe->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Liste supprimée.'
        ], 200);
    }
}
