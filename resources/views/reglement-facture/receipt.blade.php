<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $operation->MontantOperation < 0 ? 'REÇU DE REMBOURSEMENT' : 'REÇU DE PAIEMENT' }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #fff; font-size: 12px; }
        .a4 { width: 210mm; min-height: 297mm; margin: auto; background: #fff; padding: 0 18mm 0 10mm; position: relative; box-sizing: border-box; display: flex; flex-direction: column; min-height: 297mm; }
        .consult-title { text-align: center; font-size: 22px; font-weight: bold; margin-top: 10px; margin-bottom: 28px; letter-spacing: 2px; }
        .bloc-patient { margin: 0 0 10px 0; }
        .bloc-patient-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .bloc-patient-table td { padding: 2px 8px; font-size: 12px; }
        .bloc-patient-table .label { font-weight: bold; color: #222; width: 80px; }
        .bloc-patient-table .value { color: #222; }
        .bloc-patient-table .ref-cell { text-align: right; padding: 2px 4px; }
        .bloc-patient-table .ref-label { font-weight: bold; padding-right: 3px; display: inline; }
        .bloc-patient-table .ref-value { display: inline; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .details-table th, .details-table td { border: 1px solid #222; font-size: 12px; padding: 6px 8px; }
        .details-table th { background: #f4f6fa; text-align: center; }
        .details-table td { text-align: center; }
        .details-table th:first-child, .details-table td:first-child { text-align: left; }
        .details-table th:last-child, .details-table td:last-child { width: 40%; text-align: right; }
        .totaux-table { width: 40%; border-collapse: collapse; margin-top: 0; margin-bottom: 0; margin-left: auto; }
        .totaux-table td { border: 1px solid #222; font-size: 12px; padding: 6px 8px; text-align: right; }
        .montant-lettres { margin-top: 18px; font-size: 12px; clear: both; text-align: left; }
        .recu-header, .recu-footer { width: 100%; text-align: center; }
        .recu-header img, .recu-footer img { max-width: 100%; height: auto; }
        .recu-footer { position: absolute; bottom: 0; left: 0; width: 100%; }
        .print-controls { display: flex; gap: 10px; justify-content: flex-end; margin: 18px 0; }
        .print-controls button { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; background: #2c5282; color: #fff; border: none; cursor: pointer; }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 30px 30px 30px;
            background: #fff;
            min-height: 100vh;
            position: relative;
        }
        .recu-header {
            margin-bottom: 10px;
        }
        .recu-footer {
            margin-top: 10px;
        }
        .footer {
            display: none;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table td {
            padding: 4px 8px;
            font-size: 12px;
            border: none;
        }
        .info-table .label {
            font-weight: bold;
            color: #222;
            width: 120px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1;
            z-index: -1;
        }
        @media print {
            .print-controls { display: none !important; }
            body, .container {
                background: #fff !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
                page-break-inside: avoid !important;
            }
            html, body {
                height: auto !important;
                max-height: 100vh !important;
                overflow: hidden !important;
            }
            .container { page-break-inside: avoid; }
            .footer {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="a4">
    <div class="recu-header">
        @include('partials.recu-header')
    </div>
    <div class="container">
        <div class="print-controls">
            <button onclick="printFormat('A4')" class="print-btn">Imprimer A4</button>
            <button onclick="printFormat('A5')" class="print-btn">Imprimer A5</button>
        </div>
        <div class="consult-title">
            {{ $operation->MontantOperation < 0 ? 'REÇU DE REMBOURSEMENT' : 'REÇU DE PAIEMENT' }}
        </div>
        <div class="bloc-patient">
            <table class="bloc-patient-table">
                <tr>
                    <td class="label">N° Fiche :</td>
                    <td class="value">{{ $patient->IdentifiantPatient ?? 'N/A' }}</td>
                    <td class="ref-cell" colspan="2">
                        <span class="ref-label">Réf :</span>
                        <span class="ref-value">{{ $facture->Nfacture ?? 'N/A' }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Nom Patient :</td>
                    <td class="value">{{ $patient->NomContact ?? $patient->Prenom ?? 'N/A' }}</td>
                    <td class="ref-cell" colspan="2">
                        <span class="ref-label">Date :</span>
                        <span class="ref-value">{{ $operation->dateoper ? \Carbon\Carbon::parse($operation->dateoper)->format('d/m/Y H:i') : 'N/A' }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Téléphone :</td>
                    <td class="value">{{ $patient->Telephone1 ?? 'N/A' }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td class="label">Praticien :</td>
                    <td class="value">{{ $medecin->Nom ?? '' }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td class="label">Mode de règlement :</td>
                    <td class="value" colspan="3">{{ $mode->LibPaie ?? $operation->TypePAie }}</td>
                </tr>
                @if($patient && $patient->assureur)
                <tr>
                    <td class="label">Assureur :</td>
                    <td class="value">
                        {{ $patient->assureur->LibAssurance ?? 'N/A' }}
                        @if($patient->IdentifiantAssurance)
                            ({{ $patient->IdentifiantAssurance }})
                        @endif
                    </td>
                    <td colspan="2"></td>
                </tr>
                @endif
            </table>
        </div>
        @php
            $reste = null;
            $totalDejaPaye = null;
            if (($facture->ISTP ?? 0) == 1 && isset($pourQui) && $pourQui === 'pec') {
                $part = $facture->TotalPEC ?? ($facture->TotFacture * $facture->TXPEC);
                $totalDejaPaye = $facture->ReglementPEC ?? 0;
            } else {
                $part = $facture->TotalfactPatient ?? ($facture->TotFacture * (1-($facture->TXPEC ?? 0)));
                $totalDejaPaye = $facture->TotReglPatient ?? 0;
            }
            $reste = $part - $totalDejaPaye;
        @endphp
        <table class="details-table">
            <tbody>
                <tr>
                    <td class="label">Montant réglé</td>
                    <td>{{ number_format(abs($operation->MontantOperation), 2) }} MRU</td>
                </tr>
                <tr>
                    <td class="label">Reste à payer</td>
                    <td style="font-weight:bold;{{ $reste > 0 ? 'color:#b91c1c;' : 'color:#15803d;' }}">{{ number_format($reste, 2) }} MRU</td>
                </tr>
            </tbody>
        </table>
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-label">Le Caissier</div>
            <div class="signature-name"><strong>{{ Auth::user()->name ?? '' }}</strong></div>
        </div>
        <div class="watermark">
            <x-logo size="h-[400px]" />
        </div>
    </div>
    <div class="recu-footer">
        @include('partials.recu-footer')
    </div>
</div>
<script>
    function printFormat(format) {
        let style = document.createElement('style');
        style.id = 'print-format-style';
        if (format === 'A4') {
            style.innerHTML = '@page { size: A4; margin: 10mm; }';
        } else {
            style.innerHTML = '@page { size: A5; margin: 8mm; }';
        }
        let old = document.getElementById('print-format-style');
        if (old) old.remove();
        document.head.appendChild(style);
        window.print();
    }
</script>
</body>
</html> 