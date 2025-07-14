<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Services\EmployeStatutService;
use Illuminate\Http\Request;


class EmployeController extends Controller
{    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Employe::class); // ✅ vérifie le droit d'accès

        $employes = EmployeStatutService::getEmployesAvecRecup();

        return response()->json([
            'status' => 'success',
            'data' => $employes,
        ]);
    }





    public function store(Request $request)
    {
        $this->authorize('create', Employe::class); // ✅ vérifie le droit d'ajouter

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:employes,email',
            'numero' => 'required|string|max:20',
            'fonction' => 'required|string|max:100',
            'adresse' => 'required|string|max:255',
            'statut' => 'required|in:travail,congé,récupération,standby',
        ]);

        $employe = Employe::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $employe,
        ], 201);
    }

    public function update(Request $request, Employe $employe)
    {
        $this->authorize('update', $employe); // ✅ vérifie le droit de modifier

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:employes,email,' . $employe->id,
            'numero' => 'required|string|max:20',
            'fonction' => 'required|string|max:100',
            'adresse' => 'required|string|max:255',
            'statut' => 'required|in:travail,congé,récupération,standby',
        ]);

        $employe->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $employe,
        ]);
    }

    public function destroy(Employe $employe)
    {
        $this->authorize('delete', $employe); // ✅ vérifie le droit de supprimer

        $employe->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Employé supprimé.',
        ]);
    }  

    
}
 