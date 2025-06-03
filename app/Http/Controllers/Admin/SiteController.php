<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::all();
        return response()->json([
            'status' => 'success',
            'data'   => $sites,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomsite'      => 'required|string|max:255',
            'localisation' => 'required|string|max:255',
            'client'       => 'required|string|max:255',
        ]);

        $site = Site::create($validated);

        return response()->json([
            'status'  => 'success',
            'data'    => $site,
            'message' => 'Site ajouté avec succès.'
        ], 201);
    }

    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'nomsite'      => 'required|string|max:255',
            'localisation' => 'required|string|max:255',
            'client'       => 'required|string|max:255',
        ]);

        $site->update($validated);

        return response()->json([
            'status'  => 'success',
            'data'    => $site,
            'message' => 'Site mis à jour avec succès.'
        ], 200);
    }

    public function destroy(Site $site)
    {
        $site->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Site supprimé avec succès.'
        ], 200);
    }
}
