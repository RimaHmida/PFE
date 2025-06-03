<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\JsonResponse;

class PasswordResetLinkController extends Controller
{
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Veuillez fournir votre email pour recevoir un lien de réinitialisation.'
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json([
                'status'  => 'success',
                'message' => __($status)
            ], 200);
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => __($status)
            ], 400);
        }
    }
}
