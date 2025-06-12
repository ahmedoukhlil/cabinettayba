<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if($facture->TypeReglement == 'P')
            REÇU DE PAIEMENT
        @else
            REÇU DE REMBOURSEMENT
        @endif
    </title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #fff; font-size: 12px; }
        .a4 { width: 210mm; min-height: 297mm; margin: auto; background: #fff; padding: 0 18mm 0 10mm; position: relative; box-sizing: border-box; display: flex; flex-direction: column; min-height: 297mm; }
        .a5 { width: 148mm; min-height: 210mm; margin: auto; background: #fff; padding: 0 10mm 0 8mm; position: relative; box-sizing: border-box; display: flex; flex-direction: column; min-height: 210mm; }
        .consult-title { text-align: center; font-size: 22px; font-weight: bold; margin-top: 10px; margin-bottom: 28px; letter-spacing: 2px; }
        .bloc-patient { margin: 0 0 10px 0; }
        .bloc-patient-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .bloc-patient-table td { padding: 2px 8px; font-size: 12px; }
        .bloc-patient-table .label { font-weight: bold; color: #222; width: 120px; }
        .bloc-patient-table .value { color: #222; }
        .bloc-patient-table .praticien-value { padding-left: 2px !important; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .details-table th, .details-table td { border: 1px solid #222; font-size: 12px; padding: 6px 8px; text-align: left; }
        .details-table th { background: #f4f6fa; text-align: center; }
        .details-table td { text-align: center; }
        .totaux-table { width: 40%; border-collapse: collapse; margin-top: 0; margin-bottom: 0; margin-left: auto; }
        .totaux-table td { border: 1px solid #222; font-size: 12px; padding: 6px 8px; text-align: right; }
        .montant-lettres { margin-top: 18px; font-size: 12px; clear: both; text-align: left; }
        .recu-header, .recu-footer { width: 100%; text-align: center; }
        .recu-header img, .recu-footer img { max-width: 100%; height: auto; }
        .recu-footer { position: absolute; left: 0; width: 100%; }
        .a4 .recu-footer { bottom: 0; }
        .a5 .recu-footer { bottom: 0; }
        .print-btn, .format-select { display: inline-block; vertical-align: middle; }
        .format-select { margin-right: 12px; font-size: 1rem; padding: 6px 10px; border-radius: 5px; border: 1px solid #bbb; background: #f4f6fa; }
        @media print { 
            .a4, .a5 { box-shadow: none; }
            .a4 .recu-footer, .a5 .recu-footer { position: fixed; bottom: 0; left: 0; width: 100%; }
            .print-btn, .format-select { display: none !important; }
        }
    </style>
</head>
<body>
<div id="recu-container" class="a4">
    <div style="text-align:right; margin: 18px 0 0 0;">
        <select id="formatSelect" class="format-select">
            <option value="a4">A4</option>
            <option value="a5">A5</option>
        </select>
        <button onclick="window.print()" class="print-btn" style="background: #2c5282; color: #fff; padding: 10px 22px; border-radius: 6px; font-size: 1.1rem; border: none; cursor: pointer;">
            Imprimer
        </button>
    </div>
    <div class="recu-header">@include('partials.recu-header')</div>
    <div class="consult-title">
        @if($facture->TypeReglement == 'P')
            REÇU DE PAIEMENT
        @else
            REÇU DE REMBOURSEMENT
        @endif
    </div>
    <div class="bloc-patient">
        <table class="bloc-patient-table">
            <tr>
                <td class="label">N° Fiche :</td>
                <td class="value">{{ $facture->patient->IdentifiantPatient ?? '' }}</td>
                <td class="label">Nom Patient :</td>
                <td class="value">{{ $facture->patient->Prenom }}</td>
                <td class="label">Réf :</td>
                <td class="value">{{ $facture->Nfacture }}</td>
            </tr>
            <tr>
                <td class="label">Praticien :</td>
                <td class="value praticien-value">Dr {{ $facture->medecin->Nom }}</td>
                <td class="label">Date :</td>
                <td class="value">{{ $operation->dateoper->format('d/m/Y H:i') }}</td>
                <td class="label">Tél :</td>
                <td class="value">{{ $facture->patient->Telephone1 ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Mode de règlement :</td>
                <td class="value" colspan="5">{{ $mode->LibPaie ?? ($operation->TypePAie ?? '') }}</td>
            </tr>
        </table>
    </div>
    <table class="details-table">
        <thead>
        <tr>
            <th>Description</th>
            <th>Montant</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                @if($facture->TypeReglement == 'P')
                    Paiement facture N°{{ $facture->Nfacture }}
                @else
                    Remboursement facture N°{{ $facture->Nfacture }}
                @endif
            </td>
            <td>{{ number_format(abs($operation->MontantOperation), 2) }} MRU</td>
        </tr>
        </tbody>
    </table>
    <table class="totaux-table">
        <tr>
            <td>Total</td>
            <td>{{ number_format(abs($operation->MontantOperation), 2) }} MRU</td>
        </tr>
    </table>
    <div class="montant-lettres">
        @if($facture->TypeReglement == 'P')
            Arrêté le présent paiement à la somme de : <strong>{{ $operation->MontantEnLettre ?? $facture->en_lettres ?? '---' }}</strong>
        @else
            Arrêté le présent remboursement à la somme de : <strong>{{ $operation->MontantEnLettre ?? $facture->en_lettres ?? '---' }}</strong>
        @endif
    </div>
    <div style="margin-top: 10px; font-size: 13px; text-align: left;">
        <strong>Mode de règlement :</strong> {{ $mode->LibPaie ?? ($operation->TypePAie ?? '') }}
    </div>
    <div class="recu-footer">@include('partials.recu-footer')</div>
</div>
<script>
    const formatSelect = document.getElementById('formatSelect');
    const recuContainer = document.getElementById('recu-container');
    formatSelect.addEventListener('change', function() {
        recuContainer.className = this.value;
    });
</script>
</body>
</html> 