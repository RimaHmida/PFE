<?php
namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AffectationListe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PresenceValidationController extends Controller
{
    protected function authorizeValidationAccess(): void
{
    $user = auth()->user();
    if (!$user || !in_array($user->role, ['manager', 'administrateur_it'])) {
        abort(403, 'Accès réservé aux managers et administrateurs IT.');
    }
}

    public function index()
    {
        $this->authorizeValidationAccess(); // 🔐

        if (!in_array(Auth::user()->role, ['manager', 'administrateur_it'])) {
            abort(403, 'Accès refusé');
        }

        $today = Carbon::now()->toDateString();

        $affectations = AffectationListe::with(['site', 'employes.presences'])
            ->whereDate('date_fin', '<=', $today)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $affectations,
        ], 200);
    }

    public function validateAffectation($id)
    {
        $this->authorizeValidationAccess(); // 🔐

        if (!in_array(Auth::user()->role, ['manager', 'administrateur_it'])) {
            abort(403, 'Accès refusé');
        }

        $affectation = AffectationListe::findOrFail($id);

        if ($affectation->validated_by_manager) {
            return response()->json([
                'status'  => 'info',
                'message' => 'Cette affectation est déjà validée.'
            ], 200);
        }

        $affectation->validated_by_manager = true;
        $affectation->validated_at = now();
        $affectation->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Affectation validée avec succès.'
        ], 200);
    }
}
