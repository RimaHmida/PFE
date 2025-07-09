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

        $user->profile_image_url = $user->profile_image
            ? asset('storage/app/private/public/images/profiles/' . $user->profile_image)
            : 'https://ui-avatars.com/api/?name=' . urlencode($user->prenom . ' ' . $user->nom);

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
            if ($user->profile_image && Storage::disk('public')->exists('images/profiles/' . $user->profile_image)) {
                Storage::disk('public')->delete('images/profiles/' . $user->profile_image);
            }

            $file = $request->file('photo');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();

            // ✅ Sauvegarde dans storage/app/public/images/profiles/
            Storage::disk('public')->putFileAs('images/profiles', $file, $filename);

            $user->profile_image = $filename;
        }

        $user->save();

        $user->profile_image_url = $user->profile_image
            ? asset('storage/images/profiles/' . $user->profile_image)
            : 'https://ui-avatars.com/api/?name=' . urlencode($user->prenom . ' ' . $user->nom);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil mis à jour avec succès.',
            'user' => $user
        ]);
    }
}
