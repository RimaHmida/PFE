@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Validation de Présences - {{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}</h1>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('manager.presences.validate') }}" method="POST">
        @csrf

        <table class="w-full table-auto border border-gray-300 mb-6">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2">Employé</th>
                    <th class="border p-2">Site</th>
                    <th class="border p-2">Présent</th>
                    <th class="border p-2">Valider</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($presences as $presence)
                    <tr>
                        <td class="border p-2">{{ $presence->employe->nom }} {{ $presence->employe->prenom }}</td>
                        <td class="border p-2">{{ $presence->affectationListe->site->nomsite }}</td>
                        <td class="border p-2 text-center">
                            {{ $presence->present ? '✔️' : '❌' }}
                        </td>
                        <td class="border p-2 text-center">
                            <input type="checkbox" name="validate_ids[]" value="{{ $presence->id }}" class="accent-green-500">
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4">Aucune présence à valider aujourd'hui.</td></tr>
                @endforelse
            </tbody>
        </table>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Valider les présences sélectionnées
        </button>
    </form>
</div>
@endsection
