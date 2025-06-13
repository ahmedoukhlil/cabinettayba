<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>État de caisse journalier - {{ $date->format('d/m/Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .cabinet-info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .totals {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }
        .totals table {
            width: auto;
            margin-left: auto;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
        }
        .medecin-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .medecin-header {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 10px;
        }
        @media print {
            body {
                margin: 5mm !important;
                padding: 0 !important;
            }
            .header, .cabinet-info, .totaux-table, .details-table {
                margin: 0 !important;
                padding: 0 !important;
            }
            .header {
                page-break-before: avoid !important;
                page-break-after: avoid !important;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    {{-- @include('consultations.entete-facture') --}}
    <div class="header">
        <h1>Cabinet Orient - État de caisse journalier</h1>
        <div class="cabinet-info">
            <h2>{{ $cabinet->Nom ?? 'Cabinet' }}</h2>
            <p>Date : {{ $date->format('d/m/Y') }}</p>
        </div>
    </div>

    @php
        $operationsParMedecin = $operations->groupBy('fkidmedecin');
    @endphp

    @foreach($operationsParMedecin as $medecinId => $operationsMedecin)
        @php
            $medecin = \App\Models\Medecin::find($medecinId);
            $totalRecettesMedecin = $operationsMedecin->sum('entreEspece');
            $totalDepensesMedecin = $operationsMedecin->sum('retraitEspece');
            $soldeMedecin = $totalRecettesMedecin - $totalDepensesMedecin;
        @endphp

        <div class="medecin-section">
            <div class="medecin-header">
                <h3>Dr. {{ $medecin->Nom ?? 'Médecin non spécifié' }}</h3>
            </div>

            <div class="operations">
                <table>
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Opération</th>
                            <th>Mode de paiement</th>
                            <th>Recettes</th>
                            <th>Dépenses</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($operationsMedecin as $operation)
                        <tr>
                            <td>{{ Carbon\Carbon::parse($operation->dateoper)->format('H:i') }}</td>
                            <td>{{ $operation->designation }}</td>
                            <td>{{ $operation->TypePAie }}</td>
                            <td>{{ $operation->entreEspece > 0 ? number_format($operation->entreEspece, 0, ',', ' ') : '' }}</td>
                            <td>{{ $operation->retraitEspece > 0 ? number_format($operation->retraitEspece, 0, ',', ' ') : '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="totals">
                <h4>Récapitulatif du médecin</h4>
                <table>
                    <tr>
                        <th>Total des recettes</th>
                        <td>{{ number_format($totalRecettesMedecin, 0, ',', ' ') }} MRU</td>
                    </tr>
                    <tr>
                        <th>Total des dépenses</th>
                        <td>{{ number_format($totalDepensesMedecin, 0, ',', ' ') }} MRU</td>
                    </tr>
                    <tr>
                        <th>Solde</th>
                        <td>{{ number_format($soldeMedecin, 0, ',', ' ') }} MRU</td>
                    </tr>
                </table>
            </div>

            <div class="modes-paiement">
                <h4>Détail par mode de paiement</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Mode de paiement</th>
                            <th>Recettes</th>
                            <th>Dépenses</th>
                            <th>Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $typesPaiementMedecin = $operationsMedecin->pluck('TypePAie')->unique();
                            $totauxParModePaiementMedecin = collect($typesPaiementMedecin)->mapWithKeys(function($type) use ($operationsMedecin) {
                                $recettes = $operationsMedecin->where('TypePAie', $type)
                                    ->where('entreEspece', '>', 0)
                                    ->sum('MontantOperation');
                                
                                $depenses = $operationsMedecin->where('TypePAie', $type)
                                    ->where('retraitEspece', '>', 0)
                                    ->sum('MontantOperation');

                                return [$type => [
                                    'recettes' => $recettes,
                                    'depenses' => $depenses,
                                    'solde' => $recettes - $depenses
                                ]];
                            })->toArray();
                        @endphp

                        @foreach($totauxParModePaiementMedecin as $mode => $totaux)
                        <tr>
                            <td>{{ $mode }}</td>
                            <td>{{ number_format($totaux['recettes'], 0, ',', ' ') }}</td>
                            <td>{{ number_format($totaux['depenses'], 0, ',', ' ') }}</td>
                            <td>{{ number_format($totaux['solde'], 0, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="totals">
        <h3>Récapitulatif global</h3>
        <table>
            <tr>
                <th>Total des recettes</th>
                <td>{{ number_format($totalRecettes, 0, ',', ' ') }} MRU</td>
            </tr>
            <tr>
                <th>Total des dépenses</th>
                <td>{{ number_format($totalDepenses, 0, ',', ' ') }} MRU</td>
            </tr>
            <tr>
                <th>Solde</th>
                <td>{{ number_format($solde, 0, ',', ' ') }} MRU</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Imprimé le {{ now()->format('d/m/Y H:i') }} par {{ $user->NomComplet }}</p>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()">Imprimer</button>
    </div>
</body>
</html> 