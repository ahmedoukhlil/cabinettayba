<div>
    <!-- Notification de succès -->
    @if (session()->has('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             class="fixed bottom-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50" 
             role="alert">
            <div class="flex items-center">
                <div class="py-1">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
                <button type="button" @click="show = false" class="ml-4 text-green-700 hover:text-green-900">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Notification d'erreur -->
    @if (session()->has('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             class="fixed bottom-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg z-50" 
             role="alert">
            <div class="flex items-center">
                <div class="py-1">
                    <svg class="h-5 w-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
                <button type="button" @click="show = false" class="ml-4 text-red-700 hover:text-red-900">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Formulaire de consultation -->
    <div class="space-y-6">
        <div class="p-6 rounded-xl bg-gradient-to-r from-green-600 to-green-500 text-white shadow-lg">
            <h2 class="text-2xl font-bold">Nouvelle Consultation</h2>
            <p class="text-green-100 mt-1">Créez une nouvelle consultation pour le patient sélectionné</p>
        </div>

        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="p-6">
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form wire:submit.prevent="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Sélection du patient -->
                        @if($patient)
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Patient sélectionné</label>
                                <div class="p-2 bg-blue-50 rounded border border-blue-200">
                                    <span class="font-medium text-blue-800">
                                        {{ is_array($patient) ? ($patient['Prenom'] ?? '') : $patient->Prenom }}
                                    </span>
                                    @if(is_array($patient) ? ($patient['Telephone1'] ?? null) : ($patient->Telephone1 ?? null))
                                        <span class="text-sm text-blue-600 ml-2">Tél: {{ is_array($patient) ? $patient['Telephone1'] : $patient->Telephone1 }}</span>
                                    @endif
                                </div>
                            </div>
                        @else
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Rechercher un patient</label>
                            <div class="relative">
                                <livewire:patient-search />
                            </div>
                        </div>
                        @if($selectedPatient)
                            <div class="mt-2 p-2 bg-blue-50 rounded border border-blue-200 col-span-2">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-medium text-blue-800">{{ $selectedPatient['Prenom'] ?? '' }}</span>
                                        @if(($selectedPatient['Telephone1'] ?? null))
                                        <span class="text-sm text-blue-600 ml-2">Tél: {{ $selectedPatient['Telephone1'] }}</span>
                                        @endif
                                    </div>
                                    <button type="button" wire:click.prevent="handlePatientCleared" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            @endif
                        @endif

                        <!-- Sélection du médecin -->
                        <div>
                            <label for="medecin_id" class="block text-sm font-medium text-gray-700">Médecin</label>
                            <select id="medecin_id" wire:model.live="medecin_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Sélectionner un médecin</option>
                                @foreach($medecins as $medecin)
                                    <option value="{{ $medecin->idMedecin }}">Dr. {{ $medecin->Nom }}</option>
                                @endforeach
                            </select>
                            @error('medecin_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Mode de paiement -->
                        <div>
                            <label for="mode_paiement" class="block text-sm font-medium text-gray-700">Mode de paiement</label>
                            <select id="mode_paiement" wire:model.live="mode_paiement" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Sélectionner un mode de paiement</option>
                                @foreach($typesPaiement as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('mode_paiement') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Montant de la consultation (en lecture seule) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Montant</label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-lg font-semibold text-gray-900">{{ number_format($montant, 2) }} MRU</div>
                        </div>
                    </div>

                    <!-- Debug Livewire variables -->
                    {{-- <pre>
                    tauxPEC={{ $tauxPEC ?? 'null' }}
                    nomAssureur={{ $nomAssureur ?? 'null' }}
                    selectedPatient={{ print_r($selectedPatient, true) }}
                    </pre> --}}

                    <!-- Affichage de l'assurance -->
                    @if($selectedPatient && $selectedPatient['Assureur'] > 0)
                        <div class="mt-2 p-3 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center space-x-4">
                                <span class="text-green-800 font-semibold">Patient assuré</span>
                                <span class="text-green-700">Assureur : {{ $selectedPatient['NomAssureur'] ?? '' }}</span>
                                <span class="text-green-700">Taux de prise en charge : {{ number_format(($selectedPatient['TauxPEC'] ?? 0) * 100, 0) }}%</span>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-600">Montant assurance</p>
                                    @php
                                        $montantAssurance = isset($selectedPatient['TauxPEC']) ? $montant * $selectedPatient['TauxPEC'] : 0;
                                    @endphp
                                    <p class="text-sm font-medium text-green-800">{{ number_format($montantAssurance, 2) }} MRU</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">Reste à payer</p>
                                    @php
                                        $montantPatient = isset($selectedPatient['TauxPEC']) ? $montant * (1 - $selectedPatient['TauxPEC']) : 0;
                                    @endphp
                                    <p class="text-sm font-medium text-green-800">{{ number_format($montantPatient, 2) }} MRU</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Bouton de soumission -->
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 text-white rounded hover:from-green-700 hover:to-green-600">
                            Enregistrer la consultation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        [x-cloak] { display: none !important; }
        .form-control {
            @apply block w-full rounded-r-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #receipt-iframe, #receipt-iframe * {
                visibility: visible;
            }
            #receipt-iframe {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.on('receiptGenerated', function(factureId) {
                // Forcer le rafraîchissement de la vue
                Livewire.emit('refresh');
                
                // Imprimer automatiquement
                setTimeout(function() {
                    window.print();
                }, 500);
            });

            // Fermer le modal après l'impression
            window.addEventListener('afterprint', function() {
                Livewire.emit('closeReceiptModal');
            });
        });
    </script>
    @endpush 