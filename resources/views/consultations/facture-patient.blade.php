<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $facture->Type ?: 'FACTURE' }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #fff; font-size: 12px; }
        .a4 { width: 210mm; min-height: 297mm; margin: auto; background: #fff; padding: 0 18mm 0 10mm; position: relative; box-sizing: border-box; display: flex; flex-direction: column; min-height: 297mm; }
        .a5 { width: 148mm; min-height: 210mm; margin: auto; background: #fff; padding: 0 10mm 0 5mm; position: relative; box-sizing: border-box; display: flex; flex-direction: column; min-height: 210mm; }
        .facture-title { text-align: center; font-size: 22px; font-weight: bold; margin-top: 10px; margin-bottom: 28px; letter-spacing: 2px; }
        .a5 .facture-title { font-size: 18px; margin-bottom: 20px; }
        .bloc-patient { margin: 0 0 10px 0; }
        .bloc-patient-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .bloc-patient-table td { padding: 2px 8px; font-size: 12px; }
        .a5 .bloc-patient-table td { font-size: 10px; padding: 1px 4px; }
        .bloc-patient-table .label { font-weight: bold; color: #222; width: 80px; }
        .bloc-patient-table .value { color: #222; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .details-table th, .details-table td { border: 1px solid #222; font-size: 12px; padding: 6px 8px; }
        .a5 .details-table th, .a5 .details-table td { font-size: 10px; padding: 4px 6px; }
        .details-table th { background: #f4f6fa; text-align: center; }
        .details-table td { text-align: center; }
        .details-table th:first-child, .details-table td:first-child { text-align: left; }
        .details-table th:last-child, .details-table td:last-child { width: 40%; text-align: right; }
        .totaux-table { width: 40%; border-collapse: collapse; margin-top: 0; margin-bottom: 0; margin-left: auto; }
        .totaux-table td { border: 1px solid #222; font-size: 12px; padding: 6px 8px; text-align: right; }
        .a5 .totaux-table td { font-size: 10px; padding: 4px 6px; }
        .montant-lettres { margin-top: 18px; font-size: 12px; clear: both; text-align: left; }
        .a5 .montant-lettres { font-size: 10px; margin-top: 12px; }
        .recu-header, .recu-footer { width: 100%; text-align: center; }
        .recu-header img, .recu-footer img { max-width: 100%; height: auto; }
        .recu-footer { position: absolute; bottom: 0; left: 0; width: 100%; }
        @media print { .a4, .a5 { box-shadow: none; } .recu-footer { position: fixed; bottom: 0; left: 0; width: 100%; } .print-controls { display: none !important; } }
        .print-controls { display: flex; gap: 10px; justify-content: flex-end; margin: 18px 0; }
        .print-controls select, .print-controls button { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .print-controls button { background: #2c5282; color: #fff; border: none; cursor: pointer; }
        .bloc-patient-table .praticien-value { padding-left: 2px !important; }
    </style>
</head>
<body>
<div class="a4" id="documentContainer">
    <div class="print-controls">
        <select id="documentType" onchange="updateDocumentType()">
            <option value="Facture" {{ $facture->Type === 'Facture' ? 'selected' : '' }}>Facture</option>
            <option value="Devis" {{ $facture->Type === 'Devis' ? 'selected' : '' }}>Devis</option>
        </select>
        <select id="pageFormat" onchange="updatePageFormat()">
            <option value="A4">Format A4</option>
            <option value="A5">Format A5</option>
        </select>
        <button onclick="window.print()" class="print-btn">
            Imprimer
        </button>
    </div>
    <div class="recu-header">@include('partials.recu-header')</div>
    <div class="facture-title" id="documentTitle">{{ $facture->Type ?: 'FACTURE' }}</div>
    <div class="bloc-patient">
        <table class="bloc-patient-table">
            <tr>
                <td class="label">N° Fiche :</td>
                <td class="value">{{ $facture->patient->IdentifiantPatient ?? '' }}</td>
                <td class="label">Nom Patient :</td>
                <td class="value">{{ $facture->patient->NomContact ?? '' }}</td>
                <td class="label">Réf :</td>
                <td class="value">{{ $facture->Nfacture ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Praticien :</td>
                <td class="value praticien-value">{{ $facture->medecin->Nom ?? '' }}</td>
                <td class="label">Date :</td>
                <td class="value">{{ $facture->DtFacture ? $facture->DtFacture->format('d/m/Y H:i') : '' }}</td>
                <td class="label">Tél :</td>
                <td class="value">{{ $facture->patient->Telephone1 ?? '' }}</td>
            </tr>
            @if($facture->assureur)
            <tr>
                <td class="label">Assureur :</td>
                <td class="value" colspan="5">
                    {{ $facture->assureur->LibAssurance }}
                    @if($facture->patient && $facture->patient->IdentifiantAssurance)
                        ({{ $facture->patient->IdentifiantAssurance }})
                    @endif
                </td>
            </tr>
            @endif
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
    <table class="totaux-table" id="totauxTable">
        <tr>
            <td>Total {{ strtolower($facture->Type ?: 'facture') }}</td>
            <td>{{ number_format($facture->TotFacture, 2) }} MRU</td>
        </tr>
        <tbody id="detailsFacture" style="display: {{ $facture->Type === 'Facture' ? 'table-row-group' : 'none' }}">
            @if($facture->ISTP == 1)
                <tr>
                    <td>Part assurance</td>
                    <td>{{ number_format($facture->TotalPEC, 2) }} MRU</td>
                </tr>
                <tr>
                    <td>Part patient</td>
                    <td>{{ number_format($facture->TotalfactPatient, 2) }} MRU</td>
                </tr>
            @endif
            <tr>
                <td>Total règlements</td>
                <td>{{ number_format($facture->TotReglPatient, 2) }} MRU</td>
            </tr>
            <tr>
                <td>Reste à payer</td>
                <td>{{ number_format($facture->restePatient, 2) }} MRU</td>
            </tr>
        </tbody>
    </table>
    <div class="montant-lettres">
        Arrêté le présent {{ strtolower($facture->Type ?: 'facture') }} à la somme de : <strong>{{ $facture->en_lettres ?? '' }}</strong>
    </div>
    <div class="recu-footer">@include('partials.recu-footer')</div>
</div>

<script>
// Cache des éléments DOM fréquemment utilisés
const elements = {
    documentType: document.getElementById('documentType'),
    documentTitle: document.getElementById('documentTitle'),
    montantLettres: document.querySelector('.montant-lettres'),
    totalLabel: document.querySelector('.totaux-table tr:first-child td:first-child'),
    detailsFacture: document.getElementById('detailsFacture'),
    pageFormat: document.getElementById('pageFormat'),
    container: document.getElementById('documentContainer')
};

function updateDocumentType() {
    const isDevis = elements.documentType.value === 'Devis';
    
    elements.documentTitle.textContent = isDevis ? 'DEVIS' : 'FACTURE';
    elements.montantLettres.innerHTML = elements.montantLettres.innerHTML.replace(
        isDevis ? 'facture' : 'devis',
        isDevis ? 'devis' : 'facture'
    );
    elements.totalLabel.textContent = `Total ${isDevis ? 'devis' : 'facture'}`;
    elements.detailsFacture.style.display = isDevis ? 'none' : 'table-row-group';
}

function updatePageFormat() {
    const isA5 = elements.pageFormat.value === 'A5';
    elements.container.classList.toggle('a4', !isA5);
    elements.container.classList.toggle('a5', isA5);
}
</script>
</body>
</html> 