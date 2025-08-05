<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffectationListe;
use App\Models\Employe;
use App\Models\Site;
use App\Models\Conge;
use App\Models\PresenceJournaliere;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function adminData()
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, ['administrateur', 'administrateur_it'])) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }
        $today = Carbon::today()->toDateString();

        // 1. Statistiques globales
        $total_sites = Site::count();
        $total_employes = Employe::count();
        $total_affectations = AffectationListe::count();

        // 2. Affectations en cours
        $affectations_en_cours = AffectationListe::with('site')
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->get();

        // 3. Affectations à venir
        $affectations_a_venir = AffectationListe::with('site')
            ->where('date_debut', '>', now())
            ->get();

        // 4. Employés sans aucune affectation
        $employes_sans_affectation = Employe::whereDoesntHave('affectations')->get();

        // 5. Employés actuellement en récupération
        $employes_en_recuperation = Employe::where('statut', 'récupération')->get();

        // 6. Employés par statut (pour camembert)
        $employes_par_statut = Employe::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get();

        // 7. Nombre d'affectations par semaine (sur les 5 dernières semaines)
        $affectations_par_semaine = AffectationListe::select(
            DB::raw('YEARWEEK(date_debut, 1) as semaine'),
            DB::raw('COUNT(*) as total')
        )
        ->groupBy('semaine')
        ->orderBy('semaine', 'desc')
        ->take(5)
        ->get();

        // 8. Présents aujourd’hui
        $present_today = PresenceJournaliere::where('date', $today)
            ->where('present', 1)
            ->count();

        // 9. Congés aujourd’hui
        $conges_today = Conge::whereDate('date_debut', '<=', $today)
            ->whereDate('date_fin', '>=', $today)
            ->count();

        // 10. Absents aujourd’hui (affectés mais absents et sans congé)
        $absents_today = DB::table('affectation_liste_employe as ale')
            ->join('presence_journalieres as p', function ($join) use ($today) {
                $join->on('ale.employe_id', '=', 'p.employe_id')
                    ->where('p.date', $today)
                    ->where('p.present', 0);
            })
            ->leftJoin('conges as c', function ($join) use ($today) {
                $join->on('ale.employe_id', '=', 'c.employe_id')
                    ->whereDate('c.date_debut', '<=', $today)
                    ->whereDate('c.date_fin', '>=', $today);
            })
            ->whereNull('c.id')
            ->count();

        // 11. Moyenne jours de récupération restants
        $avg_jours_recup = round(Employe::avg('jours_recuperation_restants'));

        // 12. Top 3 employés fiables
        $reliable_employees = Employe::where('statut', 'travail')
            ->where('jours_recuperation_restants', '>', 0)
            ->get()
            ->map(function ($e) {
                $total = $e->presences()->count();
                $present = $e->presences()->where('present', 1)->count();
                $rate = $total > 0 ? round(($present / $total) * 100) : 0;

                return [
                    'id' => $e->id,
                    'nom' => $e->nom,
                    'prenom' => $e->prenom,
                    'presence_rate' => $rate,
                    'jours_recuperation_restants' => $e->jours_recuperation_restants,
                ];
            })
            ->sortByDesc('presence_rate')
            ->take(3)
            ->values();

        return response()->json([
            'total_sites' => $total_sites,
            'total_employes' => $total_employes,
            'total_affectations' => $total_affectations,
            'affectations_en_cours' => $affectations_en_cours,
            'affectations_a_venir' => $affectations_a_venir,
            'employes_sans_affectation' => $employes_sans_affectation,
            'employes_en_recuperation' => $employes_en_recuperation,
            'employes_par_statut' => $employes_par_statut,
            'affectations_par_semaine' => $affectations_par_semaine,
            'presence_today' => $present_today,
            'absence_today' => $absents_today,
            'conges_today' => $conges_today,
            'avg_jours_recup' => $avg_jours_recup,
            'reliable_employees' => $reliable_employees,
        ]);
    }
}
