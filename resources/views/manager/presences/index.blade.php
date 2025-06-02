@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Validation de Présences pour les périodes terminées</h1>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if ($presences->count() > 0)
        <form action="{{ route('manager.presences.validate') }}" method="POST">
            @csrf

            <table class="w-full table-auto border border-gray-300 mb-6">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2">Employé</th>
                        <th class="border p-2">Site</th>
                        <th class="border p-2">Date</th>
                        <th class="border p-2">Présent</th>
                        <th class="border p-2">Sélectionner</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($presences as $presence)
                        <tr>
                            <td class="border p-2">{{ $presence->employe->nom }} {{ $presence->employe->prenom }}</td>
                            <td class="border p-2">{{ $presence->affectationListe->site->nomsite }}</td>
                            <td class="border p-2">{{ \Carbon\Carbon::parse($presence->date)->format('d/m/Y') }}</td>
                            <td class="border p-2 text-center">
                                {{ $presence->present ? '✔️' : '❌' }}
                            </td>
                            <td class="border p-2 text-center">
                                <input type="checkbox" name="validate_ids[]" value="{{ $presence->id }}" class="accent-green-600">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Valider les présences sélectionnées
            </button>
        </form>
    @else
        <div class="text-center text-gray-600">Aucune présence à valider pour les périodes terminées.</div>
    @endif
</div>
@endsection
