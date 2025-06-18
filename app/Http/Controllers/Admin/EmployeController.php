<?php

namespace App\Http\Controllers\Admin;
use App\Services\EmployeStatutService;
use App\Http\Controllers\Controller;
use App\Models\Employe;
use Illuminate\Http\Request;

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
        return response()->json([
            'status' => 'success',
            'data'   => $employes,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'      => 'required|string|max:255',
            'prenom'   => 'required|string|max:255',
            'email'    => 'required|email|unique:employes,email',
            'numero'   => 'required|string|max:20',
            'fonction' => 'required|string|max:100',
            'adresse'  => 'required|string|max:255',
            'statut'   => 'required|in:travail,congé,récupération',
        ]);

        $employe = Employe::create($validated);

        return response()->json([
            'status'  => 'success',
            'data'    => $employe,
            'message' => 'Employé ajouté avec succès.'
        ], 201);
    }

    public function update(Request $request, Employe $employe)
    {
        $validated = $request->validate([
            'nom'      => 'required|string|max:255',
            'prenom'   => 'required|string|max:255',
            'email'    => 'required|email|unique:employes,email,' . $employe->id,
            'numero'   => 'required|string|max:20',
            'fonction' => 'required|string|max:100',
            'adresse'  => 'required|string|max:255',
            'statut'   => 'required|in:travail,congé,récupération',
        ]);

        $employe->update($validated);

        return response()->json([
            'status'  => 'success',
            'data'    => $employe,
            'message' => 'Employé mis à jour avec succès.'
        ], 200);
    }

    public function destroy(Employe $employe)
    {
        $employe->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Employé supprimé avec succès.'
        ], 200);
    }
}
