<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Site::class);

        $sites = Site::all();
        return response()->json([
            'status' => 'success',
            'data'   => $sites,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Site::class);

        $validated = $request->validate([
            'nomsite'      => 'required|string|max:255',
            'localisation' => 'required|string|max:255',
            'client'       => 'required|string|max:255',
            'image_url'    => 'nullable|string|max:255',
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
        $this->authorize('update', $site);

        $validated = $request->validate([
            'nomsite'      => 'required|string|max:255',
            'localisation' => 'required|string|max:255',
            'client'       => 'required|string|max:255',
            'image_url'    => 'nullable|string|max:255',
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
        $this->authorize('delete', $site);

        $site->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Site supprimé avec succès.'
        ], 200);
    }

    // New method to handle image upload
    public function uploadImage(Request $request)
    {
        $this->authorize('create', Site::class);

        $request->validate([
            'image' => 'required|image|max:2048', // max 2MB
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('sites', 'public');
            $url = Storage::url($path); // e.g. /storage/sites/filename.jpg

            return response()->json([
                'status' => 'success',
                'image_url' => $url,
            ], 200);
        }

        return response()->json(['status' => 'error', 'message' => 'No image uploaded'], 400);
    }
}
