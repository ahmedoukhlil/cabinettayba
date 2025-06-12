<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FACTURE</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #fff; font-size: 12px; }
        .a4 { width: 210mm; min-height: 297mm; margin: auto; background: #fff; padding: 0 18mm 0 10mm; position: relative; box-sizing: border-box; display: flex; flex-direction: column; min-height: 297mm; }
        .facture-title { text-align: center; font-size: 22px; font-weight: bold; margin-top: 10px; margin-bottom: 28px; letter-spacing: 2px; }
        .bloc-patient { margin: 0 0 10px 0; }
        .bloc-patient-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .bloc-patient-table td { padding: 2px 8px; font-size: 12px; }
        .bloc-patient-table .label { font-weight: bold; color: #222; width: 120px; }
        .bloc-patient-table .value { color: #222; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .details-table th, .details-table td { border: 1px solid #222; font-size: 12px; padding: 6px 8px; }
        .details-table th { background: #f4f6fa; text-align: center; }
        .details-table td { text-align: center; }
        .totaux-table { width: 40%; border-collapse: collapse; margin-top: 0; margin-bottom: 0; margin-left: auto; }
        .totaux-table td { border: 1px solid #222; font-size: 12px; padding: 6px 8px; text-align: right; }
        .montant-lettres { margin-top: 18px; font-size: 12px; clear: both; text-align: left; }
        .recu-header, .recu-footer { width: 100%; text-align: center; }
        .recu-header img, .recu-footer img { max-width: 100%; height: auto; }
        .recu-footer { position: absolute; bottom: 0; left: 0; width: 100%; }
        @media print { .a4 { box-shadow: none; } .recu-footer { position: fixed; bottom: 0; left: 0; width: 100%; } .print-btn { display: none !important; } }
        .print-btn { display: inline-block; }
        .bloc-patient-table .praticien-value { padding-left: 2px !important; }
    </style>
</head>
<body>
<div class="a4">
    <div style="text-align:right; margin: 18px 0 0 0;">
        <button onclick="window.print()" class="print-btn" style="background: #2c5282; color: #fff; padding: 10px 22px; border-radius: 6px; font-size: 1.1rem; border: none; cursor: pointer;">
            Imprimer
        </button>
    </div>
    <div class="recu-header">@include('partials.recu-header')</div>
    <div class="facture-title">FACTURE</div>
    <div class="bloc-patient">
        <table class="bloc-patient-table">
            <tr>
                <td class="label">N° Fiche :</td>
                <td class="value">{{ $facture->patient->IdentifiantPatient ?? '' }}</td>
                <td class="label">Nom Patient :</td>
                <td class="value">{{ $facture->patient->Prenom ?? '' }}</td>
                <td class="label">Réf :</td>
                <td class="value">{{ $facture->Nfacture ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Praticien :</td>
                <td class="value praticien-value">{{ $facture->medecin->Nom ?? '' }}</td>
                <td class="label">Assureur :</td>
                <td class="value">
                    @if($facture->assureur)
                        {{ $facture->assureur->LibAssurance }}
                        @if($facture->patient && $facture->patient->IdentifiantAssure)
                            ({{ $facture->patient->IdentifiantAssure }})
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td class="label">Date :</td>
                <td class="value">{{ $facture->DtFacture ? $facture->DtFacture->format('d/m/Y H:i:s') : '' }}</td>
            </tr>
        </table>
    </div>
    <table class="details-table">
        <thead>
        <tr>
            <th>Traitement</th>
            <th>Qté</th>
            <th>P.U</th>
            <th>Sous Total (MRU)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($facture->details as $detail)
            <tr>
                <td>{{ $detail->Actes }}</td>
                <td>{{ $detail->Quantite }}</td>
                <td>{{ number_format($detail->PrixFacture, 2) }}</td>
                <td>{{ number_format($detail->PrixFacture * $detail->Quantite, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <table class="totaux-table">
        <tr>
            <td>Total facture</td>
            <td>{{ number_format($facture->TotFacture, 2) }} MRU</td>
        </tr>
        <tr>
            <td>Assurance</td>
            <td>{{ number_format($facture->TotalPEC, 2) }} MRU</td>
        </tr>
        <tr>
            <td>Total Patient</td>
            <td>{{ number_format($facture->TotalfactPatient, 2) }} MRU</td>
        </tr>
        <tr>
            <td>Reste à payer</td>
            <td>{{ number_format(($facture->TotFacture - $facture->TotalPEC), 2) }} MRU</td>
        </tr>
    </table>
    <div class="montant-lettres">
        Arrêtez la présente facture à la somme de : <strong>{{ $facture->en_lettres ?? '' }}</strong>
    </div>
    <div class="recu-footer">@include('partials.recu-footer')</div>
</div>
</body>
</html> 