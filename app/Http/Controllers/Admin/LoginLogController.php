<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Auth;

class LoginLogController extends Controller
{
    
    public function index()
    {
        
        if (Auth::user()->role !== 'administrateur_it') {
            abort(403, 'Accès réservé à l’administrateur IT.');
        }
       
            $logs = LoginLog::with('user')->latest()->get();
        
            return response()->json(['data' => $logs]);
       
    }
}


