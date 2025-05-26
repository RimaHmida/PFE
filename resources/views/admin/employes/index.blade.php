@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Gestion des Employés</h1>

    {{-- Formulaire d'ajout d'employé --}}
    <div class="mb-6">
        <form action="{{ route('admin.employes.store') }}" method="POST" class="grid grid-cols-4 gap-4">
            @csrf
            <input name="nom" type="text" placeholder="Nom" class="border p-2 rounded" required>
            <input name="prenom" type="text" placeholder="Prénom" class="border p-2 rounded" required>
            <input name="email" type="email" placeholder="Email" class="border p-2 rounded" required>
            <input name="numero" type="text" placeholder="Numéro" class="border p-2 rounded" required>
            <input name="fonction" type="text" placeholder="Fonction" class="border p-2 rounded" required>
            <input name="adresse" type="text" placeholder="Adresse" class="border p-2 rounded" required>
            <select name="statut" class="border p-2 rounded" required>
                <option value="">-- Statut --</option>
                <option value="travail">Travail</option>
                <option value="congé">congé</option>
                <option value="récupération">Récupération</option>
            </select>
            <button type="submit" class="col-span-4 sm:col-span-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Ajouter</button>
        </form>
    </div>

    {{-- Table des employés --}}
    <table class="w-full table-auto border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">Nom</th>
                <th class="border p-2">Prénom</th>
                <th class="border p-2">Email</th>
                <th class="border p-2">Numéro</th>
                <th class="border p-2">Fonction</th>
                <th class="border p-2">Adresse</th>
                <th class="border p-2">Statut</th>
                <th class="border p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employes as $employe)
                <tr>
                    <form action="{{ route('admin.employes.update', $employe->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <td class="border p-1"><input name="nom" value="{{ $employe->nom }}" class="w-full border rounded p-1"></td>
                        <td class="border p-1"><input name="prenom" value="{{ $employe->prenom }}" class="w-full border rounded p-1"></td>
                        <td class="border p-1"><input name="email" value="{{ $employe->email }}" class="w-full border rounded p-1"></td>
                        <td class="border p-1"><input name="numero" value="{{ $employe->numero }}" class="w-full border rounded p-1"></td>
                        <td class="border p-1"><input name="fonction" value="{{ $employe->fonction }}" class="w-full border rounded p-1"></td>
                        <td class="border p-1"><input name="adresse" value="{{ $employe->adresse }}" class="w-full border rounded p-1"></td>
                        <td class="border p-1">
                            <select name="statut" class="w-full border rounded p-1">
                                <option value="travail" @selected($employe->statut === 'travail')>Travail</option>
                                <option value="congé" @selected($employe->statut === 'congé')>congé</option>
                                <option value="récupération" @selected($employe->statut === 'récupération')>Récupération</option>
                            </select>
                        </td>
                        <td class="border p-1 text-center space-x-1">
                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded">Modifier</button>
                    </form>
                    <form action="{{ route('admin.employes.destroy', $employe->id) }}" method="POST" onsubmit="return confirm('Supprimer cet employé ?');">
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
