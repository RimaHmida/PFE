<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffectationListe;
use App\Models\Employe;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function adminData()
    {
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
        ]);
    }
}
