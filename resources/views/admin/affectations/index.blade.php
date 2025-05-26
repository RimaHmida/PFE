@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Affectations des Employés</h1>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Formulaire de création d'affectation --}}
    <div class="mb-6">
        <form action="{{ route('admin.affectations.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @csrf
            <select name="site_id" class="border p-2 rounded" required>
                <option value="">-- Sélectionner un site --</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->nomsite }}</option>
                @endforeach
            </select>

            <select name="employe_id" class="border p-2 rounded" required>
                <option value="">-- Sélectionner un employé --</option>
                @foreach ($employes as $employe)
                    <option value="{{ $employe->id }}">{{ $employe->nom }} {{ $employe->prenom }}</option>
                @endforeach
            </select>

            <input name="date_debut" type="date" class="border p-2 rounded" required>
            <input name="date_fin" type="date" class="border p-2 rounded" required>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Affecter</button>
        </form>
    </div>

    {{-- Tableau des affectations --}}
    <table class="w-full table-auto border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">Employé</th>
                <th class="border p-2">Site</th>
                <th class="border p-2">Date début</th>
                <th class="border p-2">Date fin</th>
                <th class="border p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($affectations as $affectation)
                <tr>
                    <td class="border p-2">{{ $affectation->employe->nom }} {{ $affectation->employe->prenom }}</td>
                    <td class="border p-2">{{ $affectation->site->nomsite }}</td>
                    <td class="border p-2">{{ $affectation->date_debut }}</td>
                    <td class="border p-2">{{ $affectation->date_fin }}</td>
                    <td class="border p-2 text-center">
                        <form action="{{ route('admin.affectations.destroy', $affectation->id) }}" method="POST" onsubmit="return confirm('Supprimer cette affectation ?');">
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
