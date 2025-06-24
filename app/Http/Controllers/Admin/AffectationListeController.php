<?php

namespace App\Http\Controllers\Admin;

use App\Services\EmployeStatutService;
use App\Http\Controllers\Controller;
use App\Models\AffectationListe;
use App\Models\AffectationListeEmploye;
use App\Models\Employe;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffectationListeController extends Controller
{
    public function index()
{
    EmployeStatutService::verifierEtMettreAJourStatuts(); 

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

//ajout affectation 
public function store(Request $request)
{
    $validated = $request->validate([
        'site_id'    => 'required|exists:sites,id',
        'date_debut' => 'required|date',
        'date_fin'   => 'required|date|after_or_equal:date_debut',
        'employes'   => 'required|array|min:1',
        'employes.*.id' => 'required|exists:employes,id',
        'employes.*.date_debut_reelle' => 'required|date',
        'employes.*.date_fin_reelle'   => 'required|date|after_or_equal:employes.*.date_debut_reelle',
    ]);

    // Vérification des chevauchements d'affectation pour ce site
    $overlapExists = AffectationListe::where('site_id', $validated['site_id'])
        ->where(function ($query) use ($validated) {
            $query->whereBetween('date_debut', [$validated['date_debut'], $validated['date_fin']])
                  ->orWhereBetween('date_fin', [$validated['date_debut'], $validated['date_fin']])
                  ->orWhere(function ($query) use ($validated) {
                      $query->where('date_debut', '<=', $validated['date_debut'])
                            ->where('date_fin', '>=', $validated['date_fin']);
                  });
        })->exists();

    if ($overlapExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Une affectation existe déjà pour ce site pendant cette période.'
        ], 422);
    }

    // Création de l'affectation
    $liste = AffectationListe::create([
        'site_id'    => $validated['site_id'],
        'date_debut' => $validated['date_debut'],
        'date_fin'   => $validated['date_fin'],
        'created_by' => Auth::id(),
    ]);

    // Attacher les employés avec leurs dates personnalisées
    foreach ($validated['employes'] as $emp) {
        $liste->employes()->attach($emp['id'], [
            'date_debut_reelle' => $emp['date_debut_reelle'],
            'date_fin_reelle'   => $emp['date_fin_reelle'],
        ]);
    }

    return response()->json([
        'status'  => 'success',
        'data'    => $liste->load('employes', 'site'),
        'message' => 'Affectation créée avec succès.'
    ], 201);
}


}
