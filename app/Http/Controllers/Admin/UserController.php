<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected function authorizeAdmin(): void
    {
        if (Auth::user()?->role !== 'administrateur_it') {
            abort(403, 'Accès réservé aux administrateurs IT.');
        }
    }

    public function index()
    {
        $this->authorizeAdmin();
        $users = User::all();

        return response()->json([
            'status' => 'success',
            'data'   => $users,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'nom'      => 'required|string|max:255',
            'prenom'   => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/' ],            
            'role'     => 'required|in:administrateur_it,administrateur,manager,secretaire,agent_paie',
        ]);

        $user = User::create([
            'nom'      => $validated['nom'],
            'prenom'   => $validated['prenom'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        return response()->json([
            'status'  => 'success',
            'data'    => $user,
            'message' => 'Utilisateur créé avec succès.'
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'nom'      => 'required|string|max:255',
            'prenom'   => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'role'     => 'required|in:administrateur_it,administrateur,manager,secretaire,agent_paie',
        ]);

        $user->nom    = $validated['nom'];
        $user->prenom = $validated['prenom'];
        $user->email  = $validated['email'];
        $user->role   = $validated['role'];

        if ($request->filled('password')) {
            $request->validate(['password' => 'nullable|string|min:8|confirmed']);
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return response()->json([
            'status'  => 'success',
            'data'    => $user,
            'message' => 'Utilisateur mis à jour avec succès.'
        ], 200);
    }

    public function destroy(User $user)
    {
        $this->authorizeAdmin();
        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Utilisateur supprimé avec succès.'
        ], 200);
    }
}
