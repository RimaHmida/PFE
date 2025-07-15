<?php

namespace App\Http\Controllers\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Mail\PasswordChangedNotification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\LoginLog;

class AuthController extends Controller
{
    // ✅ Connexion
    public function login(Request $request)
{
    $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required'
    ]);

    $key = 'login:' . Str::lower($request->email) . '|' . $request->ip();

    // ✅ Check if a lockout exists
    if (Cache::has($key . ':locked')) {
        $retryAfter = Cache::get($key . ':locked') - time();

        return response()->json([
            'status' => 'error',
            'message' => 'Trop de tentatives. Réessayez dans ' . max($retryAfter, 0) . ' secondes.'
        ], 429);
    }

    // ✅ Increment attempts
    $attempts = Cache::increment($key);

    // ✅ First time → set expiration for counter
    if ($attempts === 1) {
        Cache::put($key, 1, 120); // expire after 2 mins
    }

    // ✅ If too many failed attempts → lock for 60s
    if ($attempts >= 5) {
        Cache::put($key . ':locked', time() + 60, 60); // lock key for 60s
        Cache::forget($key); // reset the counter
        return response()->json([
            'status' => 'error',
            'message' => 'Trop de tentatives. Réessayez dans 60 secondes.'
        ], 429);
    }

    $user = User::where('email', $credentials['email'])->first();
//logs
    if (!$user) {
        LoginLog::create([
            'user_id'    => null,
            'email'      => $credentials['email'], // ✅ NEW
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status'     => 'failed',
            'message'    => 'Utilisateur inexistant',
        ]);
    
        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'avez pas de compte ? Contactez votre administrateur IT.'
        ], 401);
    }

    if (!Auth::attempt($credentials)) {
        //logs
        LoginLog::create([
            'user_id' => null, 
        'email'      => $user->email, // ← ✅ Ajoute bien ici aussi
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'status'     => 'failed',
        'message'    => 'Mot de passe incorrect',
        ]);
        Log::warning('❌ Échec de connexion - Mauvais mot de passe', ['email' => $credentials['email'], 'ip' => $request->ip()]);
        return response()->json([
            'status' => 'error',
            'message' => 'Identifiants invalides'
        ], 401);
    }

    // ✅ Login successful → clear lock
    Cache::forget($key);
    Cache::forget($key . ':locked');

    $token = JWTAuth::fromUser(Auth::user());
    Log::info('✅ Connexion réussie', ['user_id' => Auth::id(), 'ip' => $request->ip()]);


    //logs
    LoginLog::create([
        'user_id'    => $user->id,
        'email'      => $user->email, // ← ✅ ici aussi
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'status'     => 'success',
        'message'    => null,
    ]);
    return response()->json([
        'status' => 'success',
        'message' => 'Authentification réussie.',
        'user' => Auth::user(),
        'token' => $token
    ], 200);
 
}


    // ✅ Enregistrement
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:administrateur_it,administrateur,manager,secretaire,agent_paie'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur enregistré avec succès.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    // ✅ Déconnexion
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'status' => 'success',
            'message' => 'Déconnexion réussie.',
        ]);
    }

    // ✅ Récupérer user connecté
    public function me()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user()->only(['id', 'nom', 'prenom', 'email', 'role']),
        ]);
    }

    // ✅ Changer mot de passe
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->password_changed_at = now();
        $user->save();

        Mail::to($user->email)->send(new PasswordChangedNotification($user));
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'status' => 'success',
            'message' => 'Mot de passe changé avec succès. Vous serez déconnecté.',
        ]);
    }
}
