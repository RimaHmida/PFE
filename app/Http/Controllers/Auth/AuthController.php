<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User; // Assurez-vous que le chemin est correct selon votre structure
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Connecte l'utilisateur et retourne un token JWT.
     *
     * Exigences de la requête :
     * - email (valide)
     * - password
     *
     * Si les identifiants sont valides, un token JWT est généré et renvoyé.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validation des identifiants
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        // Tente de créer un token avec les credentials fournis
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Identifiants invalides'
            ], 401);
        }

        // Récupère l'utilisateur connecté
        $user = Auth::user();

        return response()->json([
            'status'  => 'success',
            'message' => 'Authentification réussie.',
            'user'    => $user,
            'token'   => $token
        ], 200);
    }

    /**
     * Enregistre un nouvel utilisateur, génère un token et le retourne.
     *
     * Remarque : Ici, l'utilisateur peut choisir lui-même le rôle.
     * Le champ 'role' doit être fourni dans la requête et
     * sa valeur doit être l'une des suivantes :
     * "administrateur_it", "administrateur", "manager", "secretaire", "agent paie".
     *
     * Exigences de la requête (en JSON) :
     * {
     *    "nom": "Jean Dupont",
     *    "prenom": "Jean",
     *    "email": "jean.dupont@example.com",
     *    "password": "secret123",
     *    "password_confirmation": "secret123",
     *    "role": "manager"
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Validation des données reçues, incluant le champ "role"
        $validator = Validator::make($request->all(), [
            'nom'       => 'required|string|max:255',
            'prenom'    => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email',
            'password'  => 'required|string|min:6|confirmed', // "confirmed" nécessite "password_confirmation"
            'role'      => 'required|string|in:administrateur_it,administrateur,manager,secretaire,agent paie'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Création du nouvel utilisateur dans la base de données
        $user = User::create([
            'nom'      => $request->nom,
            'prenom'   => $request->prenom,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        // Génération d'un token JWT pour l'utilisateur nouvellement créé
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status'      => 'success',
            'message'     => 'Utilisateur enregistré avec succès.',
            'user'        => $user,
            'token'       => $token,
            'token_type'  => 'Bearer',
        ], 201);
    }

    /**
     * Retourne l'utilisateur authentifié (route protégée).
     *
     * Pour accéder à cette route, le client doit fournir un token JWT valide dans le header
     * Authorization: Bearer <votre_token>.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json([
            'status' => 'success',
            'user'   => Auth::user()
        ], 200);
    }

    /**
     * Déconnecte l'utilisateur en invalidant le token JWT.
     *
     * En cas de succès, le token est invalidé et l'utilisateur est déconnecté.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'status'  => 'success',
                'message' => 'Déconnexion réussie.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'La déconnexion a échoué.'
            ], 500);
        }
    }
}
