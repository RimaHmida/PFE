<?php

namespace App\Http\Controllers\Paie;

use App\Http\Controllers\Controller;
use App\Models\AffectationListe;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ValidationPaieController extends Controller
{
    protected function authorizePaieAccess(): void
{
    $user = auth()->user();
    if (!$user || !in_array($user->role, ['administrateur_it', 'agent_paie'])) {
        abort(403, 'Accès réservé aux administrateurs IT et aux agents paie.');
    }
}

    public function index()
    {
        $this->authorizePaieAccess(); // 🔐

        $validatedAffectations = AffectationListe::with(['site', 'employes'])
            ->where('validated_by_manager', true)
            ->get()
            ->map(function ($affectation) {
                $period = $this->generateDateRange($affectation->date_debut, $affectation->date_fin);

                return [
                    'id' => $affectation->id,
                    'site' => $affectation->site->nomsite ?? '',
                    'validated_at' => $affectation->validated_at,
                    'periode' => $affectation->date_debut . ' → ' . $affectation->date_fin,
                    'employes' => $affectation->employes->map(function ($emp) use ($period, $affectation) {
                        return [
                            'id' => $emp->id,
                            'nom' => $emp->nom,
                            'prenom' => $emp->prenom,
                            'presences' => collect($period)->map(function ($date) use ($emp, $affectation) {
                                $presence = $emp->presences()
                                    ->where('affectation_liste_id', $affectation->id)
                                    ->where('date', $date)
                                    ->first();

                                return [
                                    'date' => $date,
                                    'etat' => $presence ? ($presence->present ? 'Présent' : 'Absent') : 'Non marqué'
                                ];
                            })->values()
                        ];
                    })->values()
                ];
            });

        return response()->json(['data' => $validatedAffectations]);
    }
    public function download($id)
    {
        $this->authorizePaieAccess(); // 🔐

        $user = Auth::user();
        if (!in_array($user->role, ['administrateur_it', 'agent_paie'])) {
            abort(403, 'Accès refusé');
        }
    
        $affectation = AffectationListe::with(['site', 'employes'])->findOrFail($id);
        $period = $this->generateDateRange($affectation->date_debut, $affectation->date_fin);
    
        $html = '<h2>Feuille de présence</h2>';
        $html .= '<p><strong>Site :</strong> ' . ($affectation->site->nomsite ?? 'N/A') . '</p>';
        $html .= '<p><strong>Période :</strong> ' . $affectation->date_debut . ' → ' . $affectation->date_fin . '</p>';
        $html .= '<p><strong>Validée le :</strong> ' . ($affectation->validated_at ?? 'Non validée') . '</p>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%"><thead><tr><th>Employé</th><th>Date</th><th>État</th></tr></thead><tbody>';
    
        foreach ($affectation->employes as $emp) {
            foreach ($period as $date) {
                $presence = $emp->presences()
                    ->where('affectation_liste_id', $affectation->id)
                    ->where('date', $date)
                    ->first();
    
                $etat = $presence ? ($presence->present ? 'Présent' : 'Absent') : 'Non marqué';
                $html .= '<tr><td>' . $emp->nom . ' ' . $emp->prenom . '</td><td>' . $date . '</td><td>' . $etat . '</td></tr>';
            }
        }
    
        $html .= '</tbody></table>';
    
        $pdf = Pdf::loadHtml($html);
    
        // Construire un nom de fichier clair avec les dates
        $filename = "presence_{$affectation->date_debut}_au_{$affectation->date_fin}.pdf";
    
        // Retourner le PDF directement pour téléchargement
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $filename);
    }
    
    private function generateDateRange($start, $end)
    {
        $dates = [];
        $current = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        while ($current->lte($endDate)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }
}
