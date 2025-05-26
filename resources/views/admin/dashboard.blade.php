@php $role = Auth::user()->role; @endphp

<div class="max-w-6xl mx-auto py-10 px-6">
    <h1 class="text-2xl font-semibold text-gray-800 mb-8">Bienvenue sur le Tableau de Bord</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        @if ($role === 'administrateur_it')
            <a href="{{ route('admin.users.index') }}" class="block bg-white border border-gray-200 rounded-md p-4 shadow-sm hover:shadow-md transition">
                <div class="flex items-center space-x-3">
                    <div class="text-blue-500 text-xl">👥</div>
                    <div>
                        <h2 class="text-base font-medium text-gray-700">Gestion des Utilisateurs</h2>
                    </div>
                </div>
            </a>
        @endif

        @if ($role === 'administrateur' || $role === 'administrateur_it')
            <a href="{{ route('admin.employes.index') }}" class="block bg-white border border-gray-200 rounded-md p-4 shadow-sm hover:shadow-md transition">
                <div class="flex items-center space-x-3">
                    <div class="text-green-500 text-xl">📋</div>
                    <div>
                        <h2 class="text-base font-medium text-gray-700">Gestion des Employés</h2>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.sites.index') }}" class="block bg-white border border-gray-200 rounded-md p-4 shadow-sm hover:shadow-md transition">
                <div class="flex items-center space-x-3">
                    <div class="text-indigo-500 text-xl">🏗️</div>
                    <div>
                        <h2 class="text-base font-medium text-gray-700">Gestion des Sites</h2>
                    </div>
                </div>
            </a>
        @endif

    </div>
</div>
