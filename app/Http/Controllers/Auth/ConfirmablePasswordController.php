<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class ConfirmablePasswordController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'message' => 'Veuillez confirmer votre mot de passe.'
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Auth::guard('web')->validate([
            'email'    => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return response()->json([
            'status'   => 'success',
            'message'  => 'Mot de passe confirmé.',
            'redirect' => route('dashboard', [], false)
        ], 200);
    }
}
