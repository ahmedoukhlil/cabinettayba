<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $operation->MontantOperation < 0 ? 'REÇU DE REMBOURSEMENT' : 'REÇU DE PAIEMENT' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #222;
            font-size: 11px;
        }
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
        @media print {
            .print-buttons { display: none !important; }
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
<div class="recu-header">
    @include('partials.recu-header')
</div>
<div class="container">
    <div class="print-buttons">
        <button onclick="printFormat('A4')">Imprimer A4</button>
        <button onclick="printFormat('A5')">Imprimer A5</button>
    </div>
    <table class="info-table">
        <tr>
            <td class="label">N° Fiche :</td>
            <td>{{ $patient->IdentifiantPatient ?? '' }}</td>
            <td class="label">Patient:</td>
            <td>{{ $patient->Prenom ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">N° Facture:</td>
            <td>{{ $facture->Nfacture ?? '' }}</td>
            <td class="label">Date:</td>
            <td>{{ $operation->dateoper ? \Carbon\Carbon::parse($operation->dateoper)->format('d/m/Y H:i') : '' }}</td>
        </tr>
        <tr>
            <td class="label">Médecin:</td>
            <td>Dr. {{ $medecin->Nom ?? '' }}</td>
            <td class="label">Téléphone:</td>
            <td>{{ $patient->Telephone1 ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Mode de règlement:</td>
            <td>{{ $mode->LibPaie ?? $operation->TypePAie }}</td>
        </tr>
    </table>
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
    <table class="details-table" style="width:100%;margin-top:18px;">
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