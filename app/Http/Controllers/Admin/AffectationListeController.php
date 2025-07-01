<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffectationListe;
use App\Models\Employe;
use App\Models\Site;
use App\Services\EmployeStatutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
class AffectationListeController extends Controller
{
    public function index()
    {
        EmployeStatutService::verifierEtMettreAJourStatuts();

        $listes = AffectationListe::with([
            'site',
            'employes' => function ($q) {
                $q->select('employes.id', 'nom', 'prenom')
                  ->withPivot('id', 'date_debut_reelle', 'date_fin_reelle');
            }
        ])->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'listes' => $listes,
                'employes' => Employe::where('statut', 'travail')->get(),
                'sites' => Site::all(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'employes' => 'nullable|array',
            'employes.*.id' => 'required_with:employes|exists:employes,id',
            'employes.*.date_debut_reelle' => 'required_with:employes|date',
            'employes.*.date_fin_reelle' => 'required_with:employes|date|after_or_equal:employes.*.date_debut_reelle',
        ]);

        $affectation = AffectationListe::create([
            'site_id' => $validated['site_id'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'created_by' => Auth::id(),
        ]);

        if (!empty($validated['employes'])) {
            foreach ($validated['employes'] as $emp) {
                $affectation->employes()->attach($emp['id'], [
                    'date_debut_reelle' => $emp['date_debut_reelle'],
                    'date_fin_reelle' => $emp['date_fin_reelle'],
                ]);
            }
        }

        $affectation->load(['site', 'employes' => function ($q) {
            $q->select('employes.id', 'nom', 'prenom')
              ->withPivot('id', 'date_debut_reelle', 'date_fin_reelle');
        }]);

        return response()->json([
            'status' => 'success',
            'message' => 'Affectation et employés ajoutés',
            'data' => $affectation
        ], 201);
    }

    public function ajouterEmployeAffectation(Request $request, $affectationId)
    {
        try {
            $validated = $request->validate([
                'employe_id' => 'required|exists:employes,id',
                'date_debut_reelle' => 'required|date',
                'date_fin_reelle' => 'required|date|after_or_equal:date_debut_reelle',
            ]);

            $affectation = AffectationListe::findOrFail($affectationId);

            if ($affectation->employes()->where('employe_id', $validated['employe_id'])->exists()) {
                return response()->json(['status' => 'error', 'message' => 'Cet employé est déjà affecté'], 422);
            }

            $affectation->employes()->attach($validated['employe_id'], [
                'date_debut_reelle' => $validated['date_debut_reelle'],
                'date_fin_reelle' => $validated['date_fin_reelle'],
            ]);

            $affectation->load(['employes' => function ($q) {
                $q->select('employes.id', 'nom', 'prenom')
                  ->withPivot('id', 'date_debut_reelle', 'date_fin_reelle');
            }]);

            return response()->json([
                'status' => 'success',
                'message' => 'Employé ajouté',
                'data' => $affectation
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur ajouterEmployeAffectation : " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Erreur lors de l\'ajout'], 500);
        }
    }

    public function updatePivot(Request $request, $pivotId)
    {
        try {
            $validated = $request->validate([
                'date_debut_reelle' => 'required|date',
                'date_fin_reelle' => 'required|date|after_or_equal:date_debut_reelle',
            ]);

            DB::table('affectation_liste_employe')->where('id', $pivotId)->update($validated);

            return response()->json(['status' => 'success', 'message' => 'Mise à jour OK']);
        } catch (\Exception $e) {
            Log::error("Erreur updatePivot : " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Erreur mise à jour'], 500);
        }
    }

    public function supprimerEmploye($pivotId)
    {
        try {
            DB::table('affectation_liste_employe')->where('id', $pivotId)->delete();
            return response()->json(['status' => 'success', 'message' => 'Employé supprimé']);
        } catch (\Exception $e) {
            Log::error("Erreur supprimerEmploye : " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Erreur suppression'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $affectation = AffectationListe::findOrFail($id);
            $affectation->employes()->detach();
            $affectation->delete();

            return response()->json(['status' => 'success', 'message' => 'Affectation supprimée']);
        } catch (\Exception $e) {
            Log::error("Erreur destroy : " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Erreur suppression'], 500);
        }
    }
}
