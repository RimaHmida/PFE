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
use Carbon\Carbon;

class AffectationListeController extends Controller
{
    public function index(Request $request)
    {
        EmployeStatutService::verifierEtMettreAJourStatuts();
    
        $listes = AffectationListe::with([
            'site',
            'employes' => function ($q) {
                $q->select('employes.id', 'nom', 'prenom')
                  ->withPivot('id', 'date_debut_reelle', 'date_fin_reelle');
            }
        ])->get();
    
        $futureDebut = $request->query('date_debut') ? Carbon::parse($request->query('date_debut')) : Carbon::today();
        $futureFin = $request->query('date_fin') ? Carbon::parse($request->query('date_fin')) : $futureDebut;
    
        $employes = Employe::with(['affectations', 'conges'])->get()->filter(function ($emp) use ($futureDebut, $futureFin) {
            if (in_array($emp->statut, ['standby', 'travail'])) {
                return true;
            }
    
            if ($emp->statut === 'récupération') {
                $derniere = $emp->affectations
                    ->filter(fn($a) => $a->pivot->date_debut_reelle && $a->pivot->date_fin_reelle)
                    ->sortByDesc(fn($a) => $a->pivot->date_fin_reelle)
                    ->first();
    
                if (!$derniere) {
                    Log::info('EMPLOYE DEBUG (aucune affectation)', [
                        'id' => $emp->id,
                        'nom' => $emp->nom,
                        'futureDebut' => $futureDebut->toDateString()
                    ]);
                    return true;
                }
    
                $debut = Carbon::parse($derniere->pivot->date_debut_reelle);
                $fin = Carbon::parse($derniere->pivot->date_fin_reelle);
                $joursAffect = $debut->diffInDays($fin) + 1;
    
                $joursNonJust = $emp->conges
                    ->where('type', 'non justifié')
                    ->reduce(function ($carry, $c) use ($debut, $fin) {
                        $start = Carbon::parse($c->date_debut);
                        $end = Carbon::parse($c->date_fin);
                        return $carry + max(0, $start->diffInDays(min($end, $fin)) + 1);
                    }, 0);
    
                $joursTravail = max(0, $joursAffect - $joursNonJust);
                $recupFin = $fin->copy()->addDays($joursTravail);
    
                Log::info('EMPLOYE DEBUG', [
                    'id' => $emp->id,
                    'nom' => $emp->nom,
                    'recupFin' => $recupFin->toDateString(),
                    'futureDebut' => $futureDebut->toDateString(),
                    'futureFin' => $futureFin->toDateString(),
                    'joursTravail' => $joursTravail,
                    'fin' => $fin->toDateString(),
                ]);
    
                // Include if recupFin before or during requested period
                return $recupFin->lte($futureDebut) || $recupFin->between($futureDebut, $futureFin);
            }
    
            return true;
        })->values();
    
        return response()->json([
            'status' => 'success',
            'data' => [
                'listes' => $listes,
                'employes' => $employes,
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
                if ($this->hasConflict($emp['id'], $emp['date_debut_reelle'], $emp['date_fin_reelle'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Cet employé a déjà une affectation qui chevauche cette période."
                    ], 422);
                }

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

            if ($this->hasConflict($validated['employe_id'], $validated['date_debut_reelle'], $validated['date_fin_reelle'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cet employé est déjà affecté sur cette période.'
                ], 422);
            }

            $affectation = AffectationListe::findOrFail($affectationId);

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

    private function hasConflict($employeId, $start, $end)
    {
        return DB::table('affectation_liste_employe')
            ->where('employe_id', $employeId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('date_debut_reelle', [$start, $end])
                  ->orWhereBetween('date_fin_reelle', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('date_debut_reelle', '<=', $start)
                         ->where('date_fin_reelle', '>=', $end);
                  });
            })
            ->exists();
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
