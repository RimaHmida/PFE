<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::all();
        return view('admin.sites.index', compact('sites'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomsite' => 'required|string|max:255',
            'localisation' => 'required|string|max:255',
            'client' => 'required|string|max:255',
        ]);

        Site::create($validated);

        return Redirect::route('admin.sites.index')->with('success', 'Site ajouté avec succès.');
    }

    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'nomsite' => 'required|string|max:255',
            'localisation' => 'required|string|max:255',
            'client' => 'required|string|max:255',
        ]);

        $site->update($validated);

        return Redirect::route('admin.sites.index')->with('success', 'Site mis à jour avec succès.');
    }

    public function destroy(Site $site)
    {
        $site->delete();

        return Redirect::route('admin.sites.index')->with('success', 'Site supprimé avec succès.');
    }
}
