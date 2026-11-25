<div wire:poll.15s="refreshPendingAppointmentRequestsCount" class="mx-auto mt-4 w-full max-w-7xl px-3 sm:px-4 md:mt-8 md:px-6 lg:px-8">
    @php
        $patientData = is_array($selectedPatient) ? $selectedPatient : ($selectedPatient ? $selectedPatient->toArray() : []);
        $patientName = trim(($patientData['Prenom'] ?? '') . ' ' . ($patientData['NomPatient'] ?? $patientData['Nom'] ?? ''));
        $patientId = $patientData['ID'] ?? null;
    @endphp

    <section class="mb-4 flex items-center justify-between gap-4 rounded-xl bg-primary p-4 text-white shadow-lg md:mb-6 md:p-6">
        <div>
            <h1 class="text-xl font-bold leading-tight sm:text-2xl md:text-3xl">Cabinet Tayba</h1>
            <p class="mt-1 text-xs text-white/80 sm:text-sm md:text-base">
                {{ is_array(Auth::user()->typeuser) ? (Auth::user()->typeuser['Libelle'] ?? '') : (is_object(Auth::user()->typeuser) ? Auth::user()->typeuser->Libelle : Auth::user()->typeuser) }}
                — <strong>{{ Auth::user()->NomComplet ?? Auth::user()->name ?? '' }}</strong>
            </p>
        </div>
        <i class="fas fa-tooth flex-shrink-0 text-4xl opacity-20 md:text-5xl" aria-hidden="true"></i>
    </section>

    <section class="mb-4 flex flex-col items-stretch gap-3 rounded-xl border border-gray-100 bg-white p-3 shadow-sm md:mb-6 md:p-5 lg:flex-row lg:items-center">
        <div class="w-full lg:flex-1"><livewire:patient-search /></div>
        <div class="flex flex-col gap-2 sm:flex-row lg:flex-shrink-0">
            <button wire:click="openGestionPatientsModal" class="btn-secondary justify-center whitespace-nowrap px-4 py-2.5 text-sm">
                <i class="fas fa-users" aria-hidden="true"></i> Liste patients
            </button>
            <button wire:click="showCreateRdv" class="btn-secondary justify-center whitespace-nowrap px-4 py-2.5 text-sm">
                <i class="fas fa-calendar-plus" aria-hidden="true"></i> Gestion RDV
            </button>
        </div>
    </section>

    @if($isDocteurProprietaire || $isDocteur || $isSecretaire)
        <nav class="mb-4 flex flex-wrap justify-center gap-2 rounded-xl border border-gray-100 bg-white px-3 py-3 shadow-sm" aria-label="Actions principales">
            <button wire:click="togglePatientMenu" @if(!$selectedPatient) disabled title="Sélectionnez d’abord un patient" @endif
                class="nav-button btn-secondary min-w-[9rem] justify-center px-4 py-2.5 text-sm {{ $showPatientMenu ? '!border-primary !bg-primary !text-white' : '' }}">
                <i class="fas fa-user-friends" aria-hidden="true"></i>
                <span>Gestion du patient</span>
                <i class="fas fa-chevron-{{ $showPatientMenu ? 'up' : 'down' }} text-xs opacity-60" aria-hidden="true"></i>
            </button>

            <button wire:click="showCaisseOperations" class="nav-button btn-secondary min-w-[9rem] justify-center px-4 py-2.5 text-sm">
                <i class="fas fa-cash-register" aria-hidden="true"></i> Caisse Paie
            </button>

            @if($isDocteurProprietaire || $isSecretaire || $isDocteur)
                <button type="button" wire:click="openAppointmentRequests" class="nav-button btn-secondary relative min-w-[9rem] justify-center px-4 py-2.5 text-sm {{ $showAppointmentRequests ? '!border-primary !bg-primary !text-white' : '' }}">
                    <i class="fas fa-calendar-check" aria-hidden="true"></i> Demandes en ligne
                    @if($pendingAppointmentRequestsCount > 0)
                        <span class="absolute -right-2 -top-2 inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-extrabold text-white shadow ring-2 ring-white" aria-label="{{ $pendingAppointmentRequestsCount }} demandes en attente">
                            {{ $pendingAppointmentRequestsCount > 99 ? '99+' : $pendingAppointmentRequestsCount }}
                        </span>
                    @endif
                </button>
            @endif

            @if($isDocteurProprietaire)
                <button wire:click="showStatistiques" class="nav-button btn-secondary min-w-[9rem] justify-center px-4 py-2.5 text-sm">
                    <i class="fas fa-chart-bar" aria-hidden="true"></i> Statistiques
                </button>
            @endif

            <button wire:click="toggleCabinetMenu" class="nav-button btn-secondary min-w-[9rem] justify-center px-4 py-2.5 text-sm {{ $showCabinetMenu ? '!border-primary !bg-primary !text-white' : '' }}">
                <i class="fas fa-cogs" aria-hidden="true"></i>
                <span>Gestion du cabinet</span>
                <i class="fas fa-chevron-{{ $showCabinetMenu ? 'up' : 'down' }} text-xs opacity-60" aria-hidden="true"></i>
            </button>
        </nav>
    @endif

    @if($selectedPatient && $showPatientMenu)
        <section class="patient-submenu show mb-4 flex flex-wrap justify-center gap-2 rounded-xl border border-primary/20 bg-blue-50/60 px-3 py-3" aria-label="Actions du patient sélectionné">
            <button wire:click="setAction('consultation')" class="patient-nav-button nav-button min-w-[9rem] rounded-xl border-2 px-4 py-2.5 text-sm font-semibold">
                <i class="fas fa-stethoscope" aria-hidden="true"></i> Consultation
            </button>
            <button wire:click="setAction('reglement')" class="patient-nav-button nav-button min-w-[9rem] rounded-xl border-2 px-4 py-2.5 text-sm font-semibold">
                <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> Facture / Devis
            </button>
            <button wire:click="setAction('rendezvous')" class="patient-nav-button nav-button min-w-[9rem] rounded-xl border-2 px-4 py-2.5 text-sm font-semibold">
                <i class="fas fa-calendar-check" aria-hidden="true"></i> Rendez-vous
            </button>
        </section>
    @endif

    @if($showCabinetMenu)
        <section class="mb-4 flex flex-wrap justify-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-3" aria-label="Gestion du cabinet">
            <button wire:click="ouvrirAssureurModal" class="nav-button btn-secondary min-w-[9rem] justify-center px-4 py-2.5 text-sm">
                <i class="fas fa-shield-alt" aria-hidden="true"></i> Assurances
            </button>
            <button wire:click="ouvrirListeActesModal" class="nav-button btn-secondary min-w-[9rem] justify-center px-4 py-2.5 text-sm">
                <i class="fas fa-list-alt" aria-hidden="true"></i> Actes / Soins
            </button>
            @if($isDocteurProprietaire)
                <button wire:click="openUsersModal" class="nav-button btn-secondary min-w-[9rem] justify-center px-4 py-2.5 text-sm">
                    <i class="fas fa-users-cog" aria-hidden="true"></i> Utilisateurs
                </button>
            @endif
        </section>
    @endif

    @if($selectedPatient)
        <section class="mb-4 flex items-center gap-3 rounded-xl border border-primary/20 bg-primary-light p-3 shadow-sm">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-primary" aria-hidden="true"><i class="fas fa-user text-lg"></i></span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-primary">{{ $patientName ?: 'Patient sélectionné' }}</p>
                <p class="text-sm text-gray-600">N° fiche {{ $patientData['IdentifiantPatient'] ?? '—' }} · {{ $patientData['Telephone1'] ?? 'Téléphone non renseigné' }}</p>
            </div>
            <button wire:click="setPatient(null)" class="rounded-lg p-2 text-primary hover:bg-white" aria-label="Désélectionner le patient"><i class="fas fa-times"></i></button>
        </section>
    @endif

    @if($action && $selectedPatient)
        <x-modal
            id="patient-action"
            :title="$action === 'consultation' ? 'Consultation' : ($action === 'reglement' ? 'Facture / Devis' : 'Rendez-vous')"
            :subtitle="$patientName"
            :icon="$action === 'consultation' ? 'fa-stethoscope' : ($action === 'reglement' ? 'fa-file-invoice-dollar' : 'fa-calendar-check')"
            close="clearAction"
            max-width="max-w-5xl"
        >
            @if($action === 'consultation')
                <livewire:consultation-form :patient="$selectedPatient" wire:key="consultation-modal-{{ $patientId }}" />
            @elseif($action === 'reglement')
                <livewire:reglement-facture :selectedPatient="$selectedPatient" wire:key="reglement-modal-{{ $patientId }}" />
            @else
                <livewire:create-rendez-vous :patient="$selectedPatient" wire:key="rdv-modal-{{ $patientId }}" />
            @endif
        </x-modal>
    @endif

    @if($showCaisseOperations)
        <x-modal id="cash" title="Caisse Paie" subtitle="Opérations et situation journalière" icon="fa-cash-register" close="closeCaisseOperations" max-width="max-w-6xl" body-class="modal-body !p-0">
            <livewire:caisse-operations-manager wire:key="caisse-operations" />
        </x-modal>
    @endif

    @if($showAppointmentRequests)
        <x-modal id="appointment-requests" title="Demandes en ligne" subtitle="Validation des rendez-vous demandés depuis l’interface publique" icon="fa-calendar-check" close="closeAppointmentRequests" max-width="max-w-7xl" body-class="modal-body !p-0 relative">
            <div x-data="{ loaded: false }">
                <div x-show="!loaded" class="absolute inset-0 z-10 flex items-center justify-center bg-white">
                    <span class="spinner spinner-dark" aria-hidden="true"></span>
                    <span class="ml-3 text-sm font-medium text-gray-600">Chargement des demandes…</span>
                </div>
                <iframe
                    src="{{ route('appointment-requests.index', ['embedded' => 1]) }}"
                    title="Demandes de rendez-vous en ligne"
                    class="h-[76vh] w-full border-0 bg-gray-50"
                    @load="loaded = true">
                </iframe>
            </div>
        </x-modal>
    @endif

    @if($showCreateRdvModal)
        <x-modal id="rdv" title="Gestion des rendez-vous" icon="fa-calendar-plus" close="closeCreateRdvModal" max-width="max-w-6xl">
            <livewire:create-rendez-vous wire:key="create-rdv-global" />
        </x-modal>
    @endif

    @if($showGestionPatientsModal)
        <x-modal id="patients" title="Liste des patients" icon="fa-users" close="closeGestionPatientsModal" max-width="max-w-7xl">
            <livewire:patient-manager />
        </x-modal>
    @endif

    @if($showAssureurModal)
        <x-modal id="insurance" title="Assurances" icon="fa-shield-alt" close="fermerAssureurModal" max-width="max-w-3xl">
            <livewire:assureur-manager wire:key="assureur-manager-modal" />
        </x-modal>
    @endif

    @if($showListeActesModal)
        <x-modal id="acts" title="Actes / Soins" icon="fa-list-alt" close="fermerListeActesModal" max-width="max-w-7xl">
            <livewire:acte-manager wire:key="acte-manager" />
        </x-modal>
    @endif

    @if($showUsersModal)
        <x-modal id="users" title="Utilisateurs" icon="fa-users-cog" close="closeUsersModal" max-width="max-w-5xl">
            <livewire:user-manager />
        </x-modal>
    @endif

    @if($isDocteurProprietaire && $showStatistiques)
        <x-modal id="stats" title="Statistiques" icon="fa-chart-bar" close="closeStatistiques" max-width="max-w-7xl">
            <livewire:statistiques-manager wire:key="statistiques-manager" />
        </x-modal>
    @endif

    @if($selectedPatient)
        <livewire:historique-paiement wire:key="historique-paiement-{{ $patientId }}" lazy />
    @endif
</div>
