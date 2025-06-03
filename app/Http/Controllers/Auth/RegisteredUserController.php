<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Veuillez fournir les informations pour l\'inscription.'
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nom'      => ['required', 'string', 'max:255'],
            'prenom'   => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role'     => ['required', 'in:administrateur_it,administrateur,manager,secretaire,agent paie'],
        ]);

        $user = User::create([
            'nom'      => $request->nom,
            'prenom'   => $request->prenom,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        event(new Registered($user));
        Auth::login($user);

        return response()->json([
            'status'   => 'success',
            'user'     => $user,
            'message'  => 'Inscription réussie.',
            'redirect' => route('dashboard', [], false)
        ], 201);
    }
}
