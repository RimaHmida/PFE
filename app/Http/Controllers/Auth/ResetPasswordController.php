<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class ResetPasswordController extends Controller
{
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Vérifie que le token est bien associé à l'email
        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$reset) {
            return response()->json(['message' => 'Lien de réinitialisation invalide ou expiré.'], 400);
        }

        // Optionnel : vérifie l'expiration (60 minutes)
        if (Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'Le lien de réinitialisation a expiré.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        // Réinitialiser le mot de passe
        $user->password = Hash::make($request->password);
        $user->save();

        // Supprime le token utilisé
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => '✅ Mot de passe réinitialisé avec succès.']);
    }
}
