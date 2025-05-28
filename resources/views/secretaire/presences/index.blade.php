@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Présence du {{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}</h1>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('secretaire.presences.store') }}" method="POST">
        @csrf

        <table class="w-full table-auto border border-gray-300 mb-6">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2">Employé</th>
                    <th class="border p-2">Site</th>
                    <th class="border p-2">Présent</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($affectations as $affectation)
                    @foreach ($affectation->employes as $employe)
                        <tr>
                            <td class="border p-2">{{ $employe->nom }} {{ $employe->prenom }}</td>
                            <td class="border p-2">{{ $affectation->site->nomsite }}</td>
                            <td class="border p-2 text-center">
                                <input type="checkbox" name="presences[{{ $affectation->id }}][{{ $employe->id }}]" value="1" class="accent-green-500">
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Enregistrer la présence
        </button>
    </form>
</div>

@endsection
