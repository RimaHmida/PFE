<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    public function store(Request $request): JsonResponse
{
    if (auth()->user()->role !== 'administrateur_it') {
        abort(403, 'Seul l\'administrateur IT peut créer des comptes.');
    }

    $validated = $request->validate([
        'nom' => ['required', 'string', 'max:255'],
        'prenom' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'min:6', 'confirmed'],
        'role' => ['required', 'in:manager,secretaire,agent_paie,administrateur'],
    ]);

    $user = User::create([
        'nom' => $validated['nom'],
        'prenom' => $validated['prenom'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role' => $validated['role'],
    ]);

    return response()->json([
        'status' => 'success',
        'user' => $user,
        'message' => '✅ Compte créé avec succès.'
    ], 201);
}

}
