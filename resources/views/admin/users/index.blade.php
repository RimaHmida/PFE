@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
    <h1 class="text-2xl font-bold mb-4">Gestion des Utilisateurs</h1>

    {{-- Create User Form --}}
    <div class="mb-6">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-6 gap-4">
                <input name="nom" type="text" placeholder="Nom" required class="border p-2 rounded">
                <input name="prenom" type="text" placeholder="Prénom" required class="border p-2 rounded">
                <input name="email" type="email" placeholder="Email" required class="border p-2 rounded">
                <input name="password" type="password" placeholder="Mot de passe" required class="border p-2 rounded">
                <input name="password_confirmation" type="password" placeholder="Confirmer mot de passe" required class="border p-2 rounded">
                <select name="role" class="border p-2 rounded" required>
                    <option value="">-- Rôle --</option>
                    <option value="administrateur">Administrateur</option>
                    <option value="administrateur_it">Administrateur IT</option>
                    <option value="manager">Manager</option>
                    <option value="secretaire">Secrétaire</option>
                    <option value="agent paie">Agent Paie</option>
                </select>
            </div>
            <button type="submit" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded">Créer Utilisateur</button>

            @if ($errors->any())
                <div class="text-red-600 mt-2">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </form>
    </div>

    {{-- User Table --}}
    <table class="w-full border-collapse border border-gray-300">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2">Nom</th>
                <th class="border p-2">Prénom</th>
                <th class="border p-2">Email</th>
                <th class="border p-2">Rôle</th>
                <th class="border p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <td class="border p-2">
                            <input name="nom" value="{{ $user->nom }}" class="border w-full p-1 rounded">
                        </td>
                        <td class="border p-2">
                            <input name="prenom" value="{{ $user->prenom }}" class="border w-full p-1 rounded">
                        </td>
                        <td class="border p-2">
                            <input name="email" value="{{ $user->email }}" class="border w-full p-1 rounded">
                        </td>
                        <td class="border p-2">
                            <select name="role" class="border p-1 rounded">
                                <option value="administrateur" @selected($user->role === 'administrateur')>Administrateur</option>
                                <option value="administrateur_it" @selected($user->role === 'administrateur_it')>Administrateur IT</option>
                                <option value="manager" @selected($user->role === 'manager')>Manager</option>
                                <option value="secretaire" @selected($user->role === 'secretaire')>Secrétaire</option>
                                <option value="agent paie" @selected($user->role === 'agent paie')>Agent Paie</option>
                            </select>
                        </td>
                        <td class="border p-2 flex space-x-2">
                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded">Modifier</button>
                    </form>
                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Supprimer cet utilisateur ?');">
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