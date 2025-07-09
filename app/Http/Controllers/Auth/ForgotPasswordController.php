<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(60);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        $resetUrl = 'http://localhost:3000/#/reset-password?token=' . $token;

        Mail::raw("Voici votre lien de réinitialisation : $resetUrl", function ($message) use ($request) {
            $message->to($request->email)->subject('Réinitialisation du mot de passe');
        });

        return response()->json(['message' => 'Le lien de réinitialisation a été envoyé par email.'], 200);
    }
}
