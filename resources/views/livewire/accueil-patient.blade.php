<div class="mx-auto mt-4 w-full max-w-7xl px-3 sm:px-4 md:mt-8 md:px-6 lg:px-8">
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

            @if($isDocteurProprietaire || $isSecretaire)
                <a href="{{ route('appointment-requests.index') }}" class="nav-button btn-secondary min-w-[9rem] justify-center px-4 py-2.5 text-sm">
                    <i class="fas fa-calendar-check" aria-hidden="true"></i> Demandes en ligne
                </a>
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
        <div class="modal-overlay" wire:click.self="clearAction">
            <section class="modal-box max-w-5xl" role="dialog" aria-modal="true" aria-labelledby="patient-action-title">
                <header class="modal-header">
                    <div>
                        <h2 id="patient-action-title">
                            <i class="fas {{ $action === 'consultation' ? 'fa-stethoscope' : ($action === 'reglement' ? 'fa-file-invoice-dollar' : 'fa-calendar-check') }} mr-2"></i>
                            {{ $action === 'consultation' ? 'Consultation' : ($action === 'reglement' ? 'Facture / Devis' : 'Rendez-vous') }}
                        </h2>
                        <p>{{ $patientName }}</p>
                    </div>
                    <button wire:click="clearAction" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </header>
                <div class="modal-body">
                    @if($action === 'consultation')
                        <livewire:consultation-form :patient="$selectedPatient" wire:key="consultation-modal-{{ $patientId }}" />
                    @elseif($action === 'reglement')
                        <livewire:reglement-facture :selectedPatient="$selectedPatient" wire:key="reglement-modal-{{ $patientId }}" />
                    @else
                        <livewire:create-rendez-vous :patient="$selectedPatient" wire:key="rdv-modal-{{ $patientId }}" />
                    @endif
                </div>
            </section>
        </div>
    @endif

    @if($showCaisseOperations)
        <div class="modal-overlay" wire:click.self="closeCaisseOperations">
            <section class="modal-box max-w-6xl max-h-[90vh]" role="dialog" aria-modal="true" aria-labelledby="cash-title">
                <header class="modal-header">
                    <div><h2 id="cash-title"><i class="fas fa-cash-register mr-2"></i>Caisse Paie</h2><p>Opérations et situation journalière</p></div>
                    <button wire:click="closeCaisseOperations" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </header>
                <div class="modal-body !p-0"><livewire:caisse-operations-manager wire:key="caisse-operations" /></div>
            </section>
        </div>
    @endif

    @if($showCreateRdvModal)
        <div class="modal-overlay" wire:click.self="closeCreateRdvModal">
            <section class="modal-box max-w-6xl" role="dialog" aria-modal="true" aria-labelledby="rdv-title">
                <header class="modal-header">
                    <div><h2 id="rdv-title"><i class="fas fa-calendar-plus mr-2"></i>Gestion des rendez-vous</h2></div>
                    <button wire:click="closeCreateRdvModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </header>
                <div class="modal-body"><livewire:create-rendez-vous wire:key="create-rdv-global" /></div>
            </section>
        </div>
    @endif

    @if($showGestionPatientsModal)
        <div class="modal-overlay" wire:click.self="closeGestionPatientsModal">
            <section class="modal-box max-w-7xl" role="dialog" aria-modal="true" aria-labelledby="patients-title">
                <header class="modal-header">
                    <div><h2 id="patients-title"><i class="fas fa-users mr-2"></i>Liste des patients</h2></div>
                    <button wire:click="closeGestionPatientsModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </header>
                <div class="modal-body"><livewire:patient-manager /></div>
            </section>
        </div>
    @endif

    @if($showAssureurModal)
        <div class="modal-overlay" wire:click.self="fermerAssureurModal">
            <section class="modal-box max-w-3xl" role="dialog" aria-modal="true" aria-labelledby="insurance-title">
                <header class="modal-header">
                    <div><h2 id="insurance-title"><i class="fas fa-shield-alt mr-2"></i>Assurances</h2></div>
                    <button wire:click="fermerAssureurModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </header>
                <div class="modal-body"><livewire:assureur-manager wire:key="assureur-manager-modal" /></div>
            </section>
        </div>
    @endif

    @if($showListeActesModal)
        <div class="modal-overlay" wire:click.self="fermerListeActesModal">
            <section class="modal-box max-w-7xl" role="dialog" aria-modal="true" aria-labelledby="acts-title">
                <header class="modal-header">
                    <div><h2 id="acts-title"><i class="fas fa-list-alt mr-2"></i>Actes / Soins</h2></div>
                    <button wire:click="fermerListeActesModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </header>
                <div class="modal-body"><livewire:acte-manager wire:key="acte-manager" /></div>
            </section>
        </div>
    @endif

    @if($showUsersModal)
        <div class="modal-overlay" wire:click.self="closeUsersModal">
            <section class="modal-box max-w-5xl" role="dialog" aria-modal="true" aria-labelledby="users-title">
                <header class="modal-header">
                    <div><h2 id="users-title"><i class="fas fa-users-cog mr-2"></i>Utilisateurs</h2></div>
                    <button wire:click="closeUsersModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </header>
                <div class="modal-body"><livewire:user-manager /></div>
            </section>
        </div>
    @endif

    @if($isDocteurProprietaire && $showStatistiques)
        <div class="modal-overlay" wire:click.self="closeStatistiques">
            <section class="modal-box max-w-7xl" role="dialog" aria-modal="true" aria-labelledby="stats-title">
                <header class="modal-header">
                    <div><h2 id="stats-title"><i class="fas fa-chart-bar mr-2"></i>Statistiques</h2></div>
                    <button wire:click="closeStatistiques" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
                </header>
                <div class="modal-body"><livewire:statistiques-manager wire:key="statistiques-manager" /></div>
            </section>
        </div>
    @endif

    @if($selectedPatient)
        <livewire:historique-paiement wire:key="historique-paiement-{{ $patientId }}" lazy />
    @endif
</div>
