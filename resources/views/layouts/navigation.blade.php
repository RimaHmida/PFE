<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @auth
                        @php $role = Auth::user()->role; @endphp

                        @if($role === 'administrateur_it' || $role === 'administrateur')
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                                {{ __('Tableau de bord Admin') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.affectation_listes.index')" :active="request()->routeIs('admin.affectation_listes.*')">
                                {{ __('Affectations') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.employes.index')" :active="request()->routeIs('admin.employes.*')">
                                {{ __('Employés') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.sites.index')" :active="request()->routeIs('admin.sites.*')">
                                {{ __('Sites') }}
                            </x-nav-link>
                        @endif

                        @if($role === 'administrateur_it')
                            <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                                {{ __('Utilisateurs') }}
                            </x-nav-link>
                        @endif

                        @if($role === 'secretaire' || $role === 'administrateur_it')
                            <x-nav-link :href="route('secretaire.presences.index')" :active="request()->routeIs('secretaire.presences.*')">
                                {{ __('Présences Journalières') }}
                            </x-nav-link>
                        @endif

                        @if($role === 'manager' || $role === 'administrateur_it')
                            <x-nav-link :href="route('manager.presences.index')" :active="request()->routeIs('manager.presences.*')">
                                {{ __('Validation des présences') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Dropdown (logout) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                            <div>Options</div>
                            <div class="ms-1">
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20">
                                    <path d="M5.5 7.5l4.5 4.5 4.5-4.5-1-1L10 10 6.5 6.5z"/>
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 w-full text-left text-red-600 hover:text-red-800">
                            Se déconnecter
                        </button>
                    </form>
                </x-dropdown>
            </div>

            <!-- Hamburger for mobile -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation (Mobile) -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                @php $role = Auth::user()->role; @endphp

                @if($role === 'administrateur_it' || $role === 'administrateur')
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        Tableau de bord Admin
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.affectation_listes.index')" :active="request()->routeIs('admin.affectation_listes.*')">
                        Affectations
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.employes.index')" :active="request()->routeIs('admin.employes.*')">
                        Employés
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.sites.index')" :active="request()->routeIs('admin.sites.*')">
                        Sites
                    </x-responsive-nav-link>
                @endif

                @if($role === 'administrateur_it')
                    <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                        Utilisateurs
                    </x-responsive-nav-link>
                @endif

                @if($role === 'secretaire' || $role === 'administrateur_it')
                    <x-responsive-nav-link :href="route('secretaire.presences.index')" :active="request()->routeIs('secretaire.presences.*')">
                        Présences Journalières
                    </x-responsive-nav-link>
                @endif

                @if($role === 'manager' || $role === 'administrateur_it')
                    <x-responsive-nav-link :href="route('manager.presences.index')" :active="request()->routeIs('manager.presences.*')">
                        Validation des présences
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- User Info -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4 space-y-1">
                <div class="text-sm text-gray-700">{{ Auth::user()->nom ?? 'Utilisateur' }}</div>
                <div class="text-sm text-gray-700">{{ Auth::user()->email ?? '' }}</div>
                <div class="text-sm text-gray-700">Rôle : {{ Auth::user()->role ?? '' }}</div>
            </div>
        </div>
    </div>
</nav>
