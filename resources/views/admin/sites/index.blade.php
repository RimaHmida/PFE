@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Gestion des Sites</h1>

    {{-- Formulaire d'ajout de site --}}
    <div class="mb-6">
        <form action="{{ route('admin.sites.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <input name="nomsite" type="text" placeholder="Nom du site" class="border p-2 rounded" required>
            <input name="localisation" type="text" placeholder="Localisation" class="border p-2 rounded" required>
            <input name="client" type="text" placeholder="Client" class="border p-2 rounded" required>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Ajouter</button>
        </form>
    </div>

    {{-- Tableau des sites --}}
    <table class="w-full table-auto border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">Nom du site</th>
                <th class="border p-2">Localisation</th>
                <th class="border p-2">Client</th>
                <th class="border p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sites as $site)
                <tr>
                    <form action="{{ route('admin.sites.update', $site->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <td class="border p-1"><input name="nomsite" value="{{ $site->nomsite }}" class="w-full border rounded p-1"></td>
                        <td class="border p-1"><input name="localisation" value="{{ $site->localisation }}" class="w-full border rounded p-1"></td>
                        <td class="border p-1"><input name="client" value="{{ $site->client }}" class="w-full border rounded p-1"></td>
                        <td class="border p-1 text-center space-x-1">
                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded">Modifier</button>
                    </form>
                    <form action="{{ route('admin.sites.destroy', $site->id) }}" method="POST" onsubmit="return confirm('Supprimer ce site ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded">Supprimer</button>
                    </form>
                        </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection