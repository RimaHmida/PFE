<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'administrateur_it') {
            abort(403, 'Accès réservé à l’administrateur IT.');
        }

        $query = LoginLog::with('user');

        if ($request->has('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                // 🔍 Recherche sur l'utilisateur associé (nom, prénom, email)
                $q->whereHas('user', function ($sub) use ($search) {
                    $sub->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                // 🔍 Recherche sur les logs même si user_id est null
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->orderByDesc('created_at')->get();

        return response()->json(['data' => $logs]);
    }
}
