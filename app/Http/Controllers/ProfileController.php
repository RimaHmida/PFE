<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    // ✅ Récupérer l'utilisateur connecté
    public function me()
    {
        $user = Auth::user();
        $user->photo_url = $user->photo_url;

        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }

    // ✅ Mettre à jour le profil
    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'photo' => 'nullable|image|max:2048', // max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->nom = $request->nom;
        $user->prenom = $request->prenom;
        $user->email = $request->email;

        // ✅ Si une nouvelle photo est fournie
        if ($request->hasFile('photo')) {
            // Supprimer l’ancienne si elle existe
            if ($user->photo && Storage::exists('public/images/profiles/' . $user->photo)) {
                Storage::delete('public/images/profiles/' . $user->photo);
            }

            $file = $request->file('photo');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/images/profiles', $filename);

            $user->photo = $filename;
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil mis à jour avec succès.',
            'user' => $user
        ]);
    }
}
