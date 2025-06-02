@php $role = Auth::user()->role; @endphp

<div class="max-w-7xl mx-auto py-12 px-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-10">Bienvenue sur le Tableau de Bord</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @if ($role === 'administrateur_it')
            <a href="{{ route('admin.users.index') }}"
                class="bg-gradient-to-r from-blue-100 to-blue-50 hover:from-blue-200 hover:to-blue-100 border border-blue-200 rounded-xl p-5 shadow-md hover:shadow-lg transition-all">
                <div class="flex items-center space-x-4">
                    <div class="text-blue-600 text-3xl">👥</div>
                    <div>
                        <h2 class="text-lg font-semibold text-blue-900">Utilisateurs</h2>
                        <p class="text-sm text-gray-600">Gérer les comptes des utilisateurs</p>
                    </div>
                </div>
            </a>
        @endif

        @if ($role === 'administrateur' || $role === 'administrateur_it')
            <a href="{{ route('admin.employes.index') }}"
                class="bg-gradient-to-r from-green-100 to-green-50 hover:from-green-200 hover:to-green-100 border border-green-200 rounded-xl p-5 shadow-md hover:shadow-lg transition-all">
                <div class="flex items-center space-x-4">
                    <div class="text-green-600 text-3xl">📋</div>
                    <div>
                        <h2 class="text-lg font-semibold text-green-900">Employés</h2>
                        <p class="text-sm text-gray-600">Ajouter, modifier ou supprimer un employé</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.sites.index') }}"
                class="bg-gradient-to-r from-indigo-100 to-indigo-50 hover:from-indigo-200 hover:to-indigo-100 border border-indigo-200 rounded-xl p-5 shadow-md hover:shadow-lg transition-all">
                <div class="flex items-center space-x-4">
                    <div class="text-indigo-600 text-3xl">🏗️</div>
                    <div>
                        <h2 class="text-lg font-semibold text-indigo-900">Sites</h2>
                        <p class="text-sm text-gray-600">Lister et gérer les sites de travail</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.affectation_listes.index') }}"
                class="bg-gradient-to-r from-yellow-100 to-yellow-50 hover:from-yellow-200 hover:to-yellow-100 border border-yellow-200 rounded-xl p-5 shadow-md hover:shadow-lg transition-all">
                <div class="flex items-center space-x-4">
                    <div class="text-yellow-600 text-3xl">📌</div>
                    <div>
                        <h2 class="text-lg font-semibold text-yellow-900">Affectations</h2>
                        <p class="text-sm text-gray-600">Créer et suivre les affectations</p>
                    </div>
                </div>
            </a>
        @endif
    </div>
</div>
