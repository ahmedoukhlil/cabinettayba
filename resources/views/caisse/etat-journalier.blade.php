<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>État journalier de caisse</title>
    <link rel="stylesheet" href="{{ asset('css/print-tables.css') }}">
    <style>
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .header h1 {
            color: #2c5282;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .cabinet-info {
            margin-top: 15px;
        }
        .cabinet-info h2 {
            color: #1e3a8a;
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .cabinet-info p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }
        .medecin-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .medecin-header {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .medecin-header h3 {
            margin: 0;
            color: #1e3a8a;
            font-size: 16px;
        }
        .totals {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        .totals h3 {
            color: #1e3a8a;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        .no-print button {
            background: #2c5282;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="a4">
        <div class="header">
            <h1>Dental House - État de caisse journalier</h1>
            <div class="cabinet-info">
                <h2>{{ $cabinet->Nom ?? 'Dental House' }}</h2>
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

                <!-- Détails des opérations -->
                <table class="etat-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Désignation</th>
                            <th>Recettes</th>
                            <th>Dépenses</th>
                            <th>Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $soldeCumule = 0;
                        @endphp
                        @foreach($operationsMedecin as $operation)
                            @php
                                $recette = $operation->entreEspece;
                                $depense = $operation->retraitEspece;
                                $soldeCumule += ($recette - $depense);
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($operation->dateoper)->format('d/m/Y H:i') }}</td>
                                <td>{{ $operation->designation }}</td>
                                <td>{{ $recette > 0 ? number_format($recette, 0, ',', ' ') : '-' }}</td>
                                <td>{{ $depense > 0 ? number_format($depense, 0, ',', ' ') : '-' }}</td>
                                <td>{{ number_format($soldeCumule, 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Totaux par médecin -->
                <div style="margin-top: 15px;">
                    <table class="totaux-table">
                        <tr>
                            <td>Total recettes</td>
                            <td>{{ number_format($totalRecettesMedecin, 0, ',', ' ') }} MRU</td>
                        </tr>
                        <tr>
                            <td>Total dépenses</td>
                            <td>{{ number_format($totalDepensesMedecin, 0, ',', ' ') }} MRU</td>
                        </tr>
                        <tr>
                            <td>Solde</td>
                            <td>{{ number_format($soldeMedecin, 0, ',', ' ') }} MRU</td>
                        </tr>
                    </table>
                </div>

                <!-- Ventilation par mode de paiement -->
                @php
                    $totauxParModePaiementMedecin = [];
                    foreach($operationsMedecin as $operation) {
                        $mode = $operation->TypePAie ?? 'CASH';
                        if (!isset($totauxParModePaiementMedecin[$mode])) {
                            $totauxParModePaiementMedecin[$mode] = ['recettes' => 0, 'depenses' => 0, 'solde' => 0];
                        }
                        $totauxParModePaiementMedecin[$mode]['recettes'] += $operation->entreEspece;
                        $totauxParModePaiementMedecin[$mode]['depenses'] += $operation->retraitEspece;
                        $totauxParModePaiementMedecin[$mode]['solde'] += ($operation->entreEspece - $operation->retraitEspece);
                    }
                @endphp

                @if(count($totauxParModePaiementMedecin) > 0)
                    <div style="margin-top: 15px;">
                        <h4 style="color: #1e3a8a; margin-bottom: 10px;">Ventilation par mode de paiement</h4>
                        <table class="etat-table">
                            <thead>
                                <tr>
                                    <th>Mode de paiement</th>
                                    <th>Recettes</th>
                                    <th>Dépenses</th>
                                    <th>Solde</th>
                                </tr>
                            </thead>
                            <tbody>
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
                @endif
            </div>
        @endforeach

        <div class="totals">
            <h3>Récapitulatif global</h3>
            <table class="totaux-table">
                <tr>
                    <td>Total des recettes</td>
                    <td>{{ number_format($totalRecettes, 0, ',', ' ') }} MRU</td>
                </tr>
                <tr>
                    <td>Total des dépenses</td>
                    <td>{{ number_format($totalDepenses, 0, ',', ' ') }} MRU</td>
                </tr>
                <tr>
                    <td>Solde</td>
                    <td>{{ number_format($solde, 0, ',', ' ') }} MRU</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Imprimé le {{ now()->format('d/m/Y H:i') }} par {{ $user->NomComplet }}</p>
        </div>

        <div class="no-print">
            <button onclick="window.print()">Imprimer</button>
        </div>
    </div>
</body>
</html> 