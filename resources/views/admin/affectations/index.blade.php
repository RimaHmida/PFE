@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Listes d'Affectation</h1>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Formulaire de création d'une nouvelle liste --}}
    <form action="{{ route('admin.affectation_listes.store') }}" method="POST" class="mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        @csrf
        <select name="site_id" class="border p-2 rounded col-span-full md:col-span-1" required>
            <option value="">-- Choisir un site --</option>
            @foreach ($sites as $site)
                <option value="{{ $site->id }}">{{ $site->nomsite }}</option>
            @endforeach
        </select>

        <input type="date" name="date_debut" class="border p-2 rounded" required>
        <input type="date" name="date_fin" class="border p-2 rounded" required>

        {{-- Bloc employés dynamique avec recherche et scroll --}}
        <div class="md:col-span-4 col-span-full bg-white border border-gray-300 rounded-lg p-4 mt-2 shadow-sm">
            <label class="block text-sm font-semibold text-gray-700 mb-2">👥 Employés à affecter :</label>

            <input type="text" id="searchInput" placeholder="🔍 Rechercher un employé..." class="w-full mb-4 p-2 border rounded-md focus:ring-blue-500 focus:border-blue-500">

            <div id="employeList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-52 overflow-y-auto pr-1">
                @foreach ($employes as $employe)
                    <label class="flex items-center space-x-2 text-sm text-gray-800 employe-item cursor-pointer hover:bg-gray-100 px-2 py-1 rounded-md">
                        <input type="checkbox" name="employes[]" value="{{ $employe->id }}" class="accent-blue-600">
                        <span>{{ $employe->nom }} {{ $employe->prenom }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="col-span-full">
            <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Créer la liste</button>
        </div>
    </form>

    {{-- Tableau des listes existantes --}}
    <table class="w-full table-auto border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">Site</th>
                <th class="border p-2">Période</th>
                <th class="border p-2">Employés</th>
                <th class="border p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($listes as $liste)
                <tr>
                    <td class="border p-2">{{ $liste->site->nomsite }}</td>
                    <td class="border p-2">{{ $liste->date_debut }} → {{ $liste->date_fin }}</td>
                    <td class="border p-2">
                        <ul class="list-disc list-inside space-y-1 text-sm text-gray-800">
                            @forelse ($liste->employes as $emp)
                                <li>{{ $emp->nom }} {{ $emp->prenom }}</li>
                            @empty
                                <li class="text-gray-500 italic">Aucun employé</li>
                            @endforelse
                        </ul>
                    </td>
                    <td class="border p-2">
                        <form action="{{ route('admin.affectation_listes.destroy', $liste->id) }}" method="POST" onsubmit="return confirm('Supprimer cette liste ?');">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 hover:underline">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Script de recherche --}}
<script>
function filterEmployes() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const items = document.querySelectorAll('.employe-item');

    items.forEach(item => {
        const name = item.textContent.toLowerCase();
        item.style.display = name.includes(filter) ? 'flex' : 'none';
    });
}
document.getElementById('searchInput').addEventListener('keyup', filterEmployes);
</script>
@endsection
