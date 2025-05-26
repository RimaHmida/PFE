<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EmployeController extends Controller
{
    protected function authorizeAdmin()
{
    if (!in_array(auth()->user()->role, ['administrateur', 'administrateur_it'])) {
        abort(403, 'Accès non autorisé.');
    }
}

    public function index()
    {
        $employes = Employe::all();
        return view('admin.employes.index', compact('employes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:employes,email',
            'numero' => 'required|string|max:20',
            'fonction' => 'required|string|max:100',
            'adresse' => 'required|string|max:255',
            'statut' => 'required|in:travail,congé,récupération',
        ]);

        Employe::create($validated);

        return Redirect::route('admin.employes.index')->with('success', 'Employé ajouté avec succès.');
    }

    public function update(Request $request, Employe $employe)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:employes,email,' . $employe->id,
            'numero' => 'required|string|max:20',
            'fonction' => 'required|string|max:100',
            'adresse' => 'required|string|max:255',
            'statut' => 'required|in:travail,congé,récupération',
        ]);

        $employe->update($validated);

        return Redirect::route('admin.employes.index')->with('success', 'Employé mis à jour avec succès.');
    }

    public function destroy(Employe $employe)
    {
        $employe->delete();

        return Redirect::route('admin.employes.index')->with('success', 'Employé supprimé avec succès.');
    }
}