<div class="w-full px-4 md:px-6 lg:px-8 max-w-7xl mx-auto mt-4 md:mt-10">
    {{-- Bannière de bienvenue --}}
    <div class="mb-4 md:mb-8 p-4 md:p-6 rounded-xl bg-primary text-white shadow-lg flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="text-center md:text-left">
            <h1 class="text-2xl md:text-3xl font-bold mb-1">
                Cabinet Tayba
            </h1>
            <p class="text-primary-light text-base md:text-lg">
                    {{ is_array(Auth::user()->typeuser) ? (Auth::user()->typeuser['Libelle'] ?? '') : (is_object(Auth::user()->typeuser) ? Auth::user()->typeuser->Libelle : Auth::user()->typeuser) }}
                <span class="font-bold">
                    {{ Auth::user()->NomComplet ?? Auth::user()->name ?? '' }}
                </span>
            </p>
        </div>
        <i class="fas fa-tooth text-4xl md:text-5xl opacity-30"></i>
    </div>

    {{-- Encadré recherche patient + nouveau patient --}}
    <div class="bg-white rounded-xl shadow p-4 md:p-6 flex flex-col md:flex-row items-center gap-4 md:gap-6 mb-4 md:mb-8 border border-primary-light">
        <div class="w-full">
            <livewire:patient-search />
        </div>
        <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
            <button wire:click="openGestionPatientsModal" class="w-full sm:w-auto px-4 md:px-6 py-2 md:py-3 bg-primary text-white rounded-lg shadow hover:bg-primary hover:text-white transition text-base md:text-lg flex items-center justify-center gap-2">
                <i class="fas fa-users"></i> Gestion des patients
            </button>
            <button wire:click="showCreateRdv" class="w-full sm:w-auto px-4 md:px-6 py-2 md:py-3 bg-primary text-white rounded-lg shadow hover:bg-primary hover:text-white transition text-base md:text-lg flex items-center justify-center gap-2">
                <i class="fas fa-calendar-plus"></i> Créer RDV
            </button>
        </div>
    </div>

    @if($isDocteurProprietaire || $isDocteur || $isSecretaire)
        <div class="flex flex-wrap gap-2 md:gap-4 mb-4 md:mb-8 bg-gray-50 z-10 py-2 md:py-4 justify-center items-center">
            {{-- Consultation --}}
            <button wire:click="{{ $selectedPatient ? 'setAction(\'consultation\')' : '' }}"
                class="flex items-center gap-2 md:gap-3 px-3 md:px-6 py-2 md:py-3 w-full sm:w-48 md:w-56 border-2 border-primary bg-white text-primary rounded-xl shadow hover:bg-primary hover:text-white transition text-base md:text-lg {{ $selectedPatient ? '' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' }}">
                <span class="inline-flex items-center justify-center rounded-full p-1 md:p-2 transition-all duration-200 bg-white text-primary">
                    <i class="fas fa-stethoscope text-primary text-xl md:text-2xl"></i>
                </span>
                <span class="font-semibold">Consultation</span>
            </button>
            {{-- Caisse Paie --}}
            <button wire:click="showCaisseOperations"
                class="flex items-center gap-2 md:gap-3 px-3 md:px-6 py-2 md:py-3 w-full sm:w-48 md:w-56 border-2 border-primary bg-white text-primary rounded-xl shadow hover:bg-primary hover:text-white transition text-base md:text-lg">
                <span class="inline-flex items-center justify-center rounded-full p-1 md:p-2 transition-all duration-200 bg-white text-primary">
                    <i class="fas fa-cash-register text-primary text-xl md:text-2xl"></i>
                </span>
                <span class="font-semibold">Caisse Paie</span>
            </button>
            {{-- Facture/Devis --}}
            <button wire:click="{{ $selectedPatient ? 'setAction(\'reglement\')' : '' }}"
                class="flex items-center gap-2 md:gap-3 px-3 md:px-6 py-2 md:py-3 w-full sm:w-48 md:w-56 border-2 border-primary bg-white text-primary rounded-xl shadow hover:bg-primary hover:text-white transition text-base md:text-lg {{ $selectedPatient ? '' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' }}">
                <span class="inline-flex items-center justify-center rounded-full p-1 md:p-2 transition-all duration-200 bg-white text-primary">
                    <i class="fas fa-file-invoice-dollar text-primary text-xl md:text-2xl"></i>
                </span>
                <span class="font-semibold">Facture/Devis</span>
            </button>
            {{-- RDV Patient --}}
            <button wire:click="{{ $selectedPatient ? 'setAction(\'rendezvous\')' : '' }}"
                class="flex items-center gap-2 md:gap-3 px-3 md:px-6 py-2 md:py-3 w-full sm:w-48 md:w-56 border-2 border-primary bg-white text-primary rounded-xl shadow hover:bg-primary hover:text-white transition text-base md:text-lg {{ $selectedPatient ? '' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' }}">
                <span class="inline-flex items-center justify-center rounded-full p-1 md:p-2 transition-all duration-200 bg-white text-primary">
                    <i class="fas fa-calendar-plus text-primary text-xl md:text-2xl"></i>
                </span>
                <span class="font-semibold">RDV Patient</span>
            </button>
            {{-- Liste de soins --}}
            <button wire:click="ouvrirListeActesModal"
                class="flex items-center gap-2 md:gap-3 px-3 md:px-6 py-2 md:py-3 w-full sm:w-48 md:w-56 border-2 border-primary bg-white text-primary rounded-xl shadow hover:bg-primary hover:text-white transition text-base md:text-lg">
                <span class="inline-flex items-center justify-center rounded-full p-1 md:p-2 transition-all duration-200 bg-white text-primary">
                    <i class="fas fa-hospital-user text-primary text-xl md:text-2xl"></i>
                </span>
                <span class="font-semibold">Liste de soins</span>
            </button>
            {{-- Assurances --}}
            <button wire:click="ouvrirAssureurModal"
                class="flex items-center gap-2 md:gap-3 px-3 md:px-6 py-2 md:py-3 w-full sm:w-48 md:w-56 border-2 border-primary bg-white text-primary rounded-xl shadow hover:bg-primary hover:text-white transition text-base md:text-lg">
                <span class="inline-flex items-center justify-center rounded-full p-1 md:p-2 transition-all duration-200 bg-white text-primary">
                    <i class="fas fa-house-user text-primary text-xl md:text-2xl"></i>
                </span>
                <span class="font-semibold">Assurances</span>
            </button>
            {{-- Statistiques --}}
            @if($isDocteurProprietaire)
                <button wire:click="showStatistiques" class="flex items-center gap-2 md:gap-3 px-3 md:px-6 py-2 md:py-3 w-full sm:w-48 md:w-56 border-2 border-primary bg-white text-primary rounded-xl shadow hover:bg-primary hover:text-white transition text-base md:text-lg">
                    <span class="inline-flex items-center justify-center rounded-full p-1 md:p-2 transition-all duration-200 bg-white text-primary">
                        <i class="fas fa-chart-bar text-primary text-xl md:text-2xl"></i>
                    </span>
                    <span class="font-semibold">Statistiques</span>
                </button>
            @endif
            {{-- Utilisateurs (modal) --}}
            @if($isDocteurProprietaire)
            <button wire:click="openUsersModal"
                class="flex items-center gap-2 md:gap-3 px-3 md:px-6 py-2 md:py-3 w-full sm:w-48 md:w-56 border-2 border-primary bg-white text-primary rounded-xl shadow hover:bg-primary hover:text-white transition text-base md:text-lg">
                <span class="inline-flex items-center justify-center rounded-full p-1 md:p-2 transition-all duration-200 bg-white text-primary">
                    <i class="fas fa-users-cog text-primary text-xl md:text-2xl"></i>
                </span>
                <span class="font-semibold">Utilisateurs</span>
            </button>
            @endif
        </div>

        @if($showCaisseOperations)
            <div class="w-full">
                <livewire:caisse-operations-manager wire:key="caisse-operations" />
            </div>
        @endif
    @endif

    {{-- Modal création patient --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-0 relative animate-fade-in">
                <!-- Header du modal -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-primary-light rounded-t-2xl">
                    <h2 class="text-xl font-bold text-primary">Gestion des patients</h2>
                    <button wire:click="closeCreateModal" class="text-gray-500 hover:text-primary text-2xl flex items-center gap-2">
                        <i class="fas fa-times"></i> <span class="text-base font-medium">Fermer</span>
                    </button>
                </div>
                <!-- Contenu scrollable -->
                <div class="max-h-[70vh] overflow-y-auto p-6">
                    <livewire:patient-manager />
                </div>
            </div>
        </div>
    @endif

    {{-- Modal création RDV pour tous patients --}}
    @if($showCreateRdvModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <livewire:create-rendez-vous wire:key="create-rdv-global" />
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="closeCreateRdvModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showGestionPatientsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl p-0 relative animate-fade-in">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-primary-light rounded-t-2xl">
                    <h2 class="text-xl font-bold text-primary">Gestion des patients</h2>
                    <button wire:click="closeGestionPatientsModal" class="text-gray-500 hover:text-primary text-2xl flex items-center gap-2">
                        <i class="fas fa-times"></i> <span class="text-base font-medium">Fermer</span>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6">
                    <livewire:patient-manager />
                </div>
            </div>
        </div>
    @endif

    @if($selectedPatient)
        <div class="bg-primary-light border border-primary rounded-xl p-5 mb-8 flex items-center gap-6 shadow">
            <i class="fas fa-user-circle text-primary text-5xl"></i>
            <div class="flex-1">
                <div class="flex items-center">
                    <span class="text-lg font-semibold">{{ $selectedPatient['Prenom'] ?? '' }}</span>
                    <button wire:click="setPatient(null)" class="ml-2 text-primary hover:text-primary-dark">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                @if($selectedPatient['Telephone1'] ?? null)
                    <div class="text-primary text-base">Tél: {{ $selectedPatient['Telephone1'] }}</div>
                @endif
            </div>
        </div>

        <div class="mt-8">
            @if($action === 'consultation')
                <div wire:loading.remove wire:target="setAction">
                    <livewire:consultation-form :patient="$selectedPatient" wire:key="consultation-{{ $selectedPatient['ID'] ?? 'new' }}" lazy />
                </div>
                <div wire:loading wire:target="setAction" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                </div>
            @elseif($action === 'reglement')
                <div wire:loading.remove wire:target="setAction">
                    <livewire:reglement-facture :selectedPatient="$selectedPatient" wire:key="reglement-{{ $selectedPatient['ID'] ?? 'new' }}" lazy />
                </div>
                <div wire:loading wire:target="setAction" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                </div>
            @elseif($action === 'rendezvous')
                <div wire:loading.remove wire:target="setAction">
                    <livewire:create-rendez-vous :patient="$selectedPatient" wire:key="rdv-{{ $selectedPatient['ID'] ?? 'new' }}" lazy />
                </div>
                <div wire:loading wire:target="setAction" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                </div>
            @endif
        </div>

        <!-- Composant HistoriquePaiement toujours présent -->
        <livewire:historique-paiement wire:key="historique-paiement" lazy />
    @endif

    @if($showAssureurModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-0 relative animate-fade-in">
                <button wire:click="fermerAssureurModal" class="absolute top-4 right-4 text-gray-500 hover:text-primary text-2xl font-bold z-10">&times;</button>
                <livewire:assureur-manager wire:key="assureur-manager-modal" />
            </div>
        </div>
    @endif

    @if($showListeActesModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl p-0 relative animate-fade-in" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-primary-light rounded-t-2xl">
                    <h2 class="text-xl font-bold text-primary">Liste des soins</h2>
                    <button wire:click="fermerListeActesModal" class="text-gray-500 hover:text-primary text-2xl flex items-center gap-2">
                        <i class="fas fa-times"></i> <span class="text-base font-medium">Fermer</span>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6">
                    <livewire:acte-manager wire:key="acte-manager-{{ now() }}" />
                </div>
            </div>
        </div>
    @endif

    {{-- Modal utilisateurs --}}
    @if($showUsersModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-0 relative animate-fade-in">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-primary-light rounded-t-2xl">
                    <h2 class="text-xl font-bold text-primary">Gestion des utilisateurs</h2>
                    <button wire:click="closeUsersModal" class="text-gray-500 hover:text-primary text-2xl flex items-center gap-2">
                        <i class="fas fa-times"></i> <span class="text-base font-medium">Fermer</span>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6">
                    <livewire:user-manager />
                </div>
            </div>
        </div>
    @endif

    @if($isDocteurProprietaire && $showStatistiques)
        <div class="w-full mt-8">
            <livewire:statistiques-manager wire:key="statistiques-manager" />
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        Livewire.on('openModal', (modalName) => {
            // console.log('Opening modal:', modalName);
        });
    });

    document.addEventListener('alpine:init', () => {
        Alpine.data('modal', () => ({
            show: false,
            open() {
                this.show = true;
            },
            close() {
                this.show = false;
            }
        }));
    });
</script>
@endpush 