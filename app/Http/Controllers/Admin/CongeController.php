<?php

namespace App\Http\Controllers\Admin;
use App\Services\EmployeStatutService;
use App\Http\Controllers\Controller;
use App\Models\Conge;
use App\Models\Employe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CongeController extends Controller
{
    protected function authorizeAdmin(): void
{
    $user = auth()->user();
    if (!$user || !in_array($user->role, ['administrateur', 'administrateur_it'])) {
        abort(403, 'Accès réservé aux administrateurs.');
    }
}
    public function index()
    
    {
        $this->authorizeAdmin();

        $conges = Conge::with('employe')->latest()->get();
        return response()->json(['data' => $conges]);
    }

    public function store(Request $request)
{
    $this->authorizeAdmin();

    $validated = $request->validate([
        'employe_id'   => 'required|exists:employes,id',
        'type'         => 'required|in:maladie,justifié,non justifié',
        'date_debut'   => 'required|date',
        'date_fin'     => 'required|date|after_or_equal:date_debut',
        'description'  => 'nullable|string',
        'document'     => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048'
    ]);

    if (in_array($validated['type'], ['maladie', 'justifié']) && !$request->hasFile('document')) {
        return response()->json(['error' => 'Le document est requis pour les congés maladie ou justifiés.'], 422);
    }

    if ($request->hasFile('document')) {
        $validated['document'] = $request->file('document')->store('documents/conges', 'public');
    }

    Conge::create($validated);

    // ✅ Rafraîchir le statut immédiatement
    EmployeStatutService::verifierEtMettreAJourStatuts();

    return response()->json(['message' => '✅ Congé enregistré avec succès']);
}

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        $conge = Conge::findOrFail($id);
    
        $validated = $request->validate([
            'employe_id'   => 'required|exists:employes,id',
            'type'         => 'required|in:maladie,justifié,non justifié',
            'date_debut'   => 'required|date',
            'date_fin'     => 'required|date|after_or_equal:date_debut',
            'description'  => 'nullable|string',
            'document'     => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048'
        ]);
    
        // Check document required
        if (in_array($validated['type'], ['maladie', 'justifié']) && !$request->hasFile('document') && !$conge->document) {
            return response()->json(['error' => 'Le document est requis pour ce type de congé.'], 422);
        }
    
        // Upload new document if present
        if ($request->hasFile('document')) {
            if ($conge->document && Storage::disk('public')->exists($conge->document)) {
                Storage::disk('public')->delete($conge->document);
            }
            $validated['document'] = $request->file('document')->store('documents/conges', 'public');
        } else {
            // Keep existing document
            $validated['document'] = $conge->document;
        }
    
        $conge->update($validated);
        EmployeStatutService::verifierEtMettreAJourStatuts();
    
        return response()->json(['message' => '✅ Congé modifié avec succès']);
    }
    
    public function destroy($id)
    {
        $this->authorizeAdmin();

        $conge = Conge::findOrFail($id);
        if ($conge->document) Storage::disk('public')->delete($conge->document);
        $conge->delete();

        return response()->json(['message' => '✅ Congé supprimé avec succès']);
    }

    public function download($id)
{
    $this->authorizeAdmin();

    $conge = Conge::findOrFail($id);

    if (!$conge->document || !Storage::disk('public')->exists($conge->document)) {
        return abort(404);
    }

    $path = storage_path('app/public/' . $conge->document);
    return response()->file($path, [
        'Content-Disposition' => 'attachment; filename="' . basename($conge->document) . '"'
    ]);
}

}
