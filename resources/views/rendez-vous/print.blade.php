<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REÇU DE RENDEZ-VOUS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #fff; font-size: 12px; }
        .a4 { width: 210mm; min-height: 297mm; margin: auto; background: #fff; padding: 0 18mm 0 10mm; position: relative; box-sizing: border-box; display: flex; flex-direction: column; min-height: 297mm; }
        .a5 { width: 148mm; min-height: 210mm; margin: auto; background: #fff; padding: 0 10mm 0 5mm; position: relative; box-sizing: border-box; display: flex; flex-direction: column; min-height: 210mm; }
        .consult-title { text-align: center; font-size: 22px; font-weight: bold; margin-top: 10px; margin-bottom: 28px; letter-spacing: 2px; }
        .a5 .consult-title { font-size: 18px; margin-bottom: 20px; }
        .bloc-patient { margin: 0 0 10px 0; }
        .bloc-patient-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .bloc-patient-table td { padding: 2px 8px; font-size: 12px; }
        .a5 .bloc-patient-table td { font-size: 10px; padding: 1px 4px; }
        .bloc-patient-table .label { font-weight: bold; color: #222; width: 80px; }
        .bloc-patient-table .value { color: #222; }
        .bloc-patient-table .ref-cell { text-align: right; padding: 2px 4px; }
        .bloc-patient-table .ref-label { font-weight: bold; padding-right: 3px; display: inline; }
        .bloc-patient-table .ref-value { display: inline; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .details-table th, .details-table td { border: 1px solid #222; font-size: 12px; padding: 6px 8px; }
        .a5 .details-table th, .a5 .details-table td { font-size: 10px; padding: 4px 6px; }
        .details-table th { background: #f4f6fa; text-align: center; }
        .details-table td { text-align: center; }
        .details-table th:first-child, .details-table td:first-child { text-align: left; }
        .details-table th:last-child, .details-table td:last-child { width: 40%; text-align: right; }
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
        <select id="pageFormat" onchange="updatePageFormat()">
            <option value="A4">Format A4</option>
            <option value="A5">Format A5</option>
        </select>
        <button onclick="window.print()" class="print-btn">
            Imprimer
        </button>
    </div>
    <div class="recu-header">@include('partials.recu-header')</div>
    <div class="consult-title">REÇU DE RENDEZ-VOUS</div>
    <div class="bloc-patient">
        <table class="bloc-patient-table">
            <tr>
                <td class="label">N° Fiche :</td>
                <td class="value">{{ $rendezVous->patient->IdentifiantPatient ?? 'N/A' }}</td>
                <td class="ref-cell" colspan="2">
                    <span class="ref-label">Réf :</span>
                    <span class="ref-value">{{ str_pad($rendezVous->OrdreRDV, 3, '0', STR_PAD_LEFT) }}</span>
                </td>
            </tr>
            <tr>
                <td class="label">Nom Patient :</td>
                <td class="value">{{ $rendezVous->patient->NomContact ?? $rendezVous->patient->Prenom ?? 'N/A' }}</td>
                <td class="ref-cell" colspan="2">
                    <span class="ref-label">Date :</span>
                    <span class="ref-value">{{ \Carbon\Carbon::parse($rendezVous->dtPrevuRDV)->format('d/m/Y') }}</span>
                </td>
            </tr>
            <tr>
                <td class="label">Praticien :</td>
                <td class="value praticien-value">Dr. {{ $rendezVous->medecin->Nom }} {{ $rendezVous->medecin->Prenom }}</td>
                <td class="ref-cell" colspan="2">
                    <span class="ref-label">Heure :</span>
                    <span class="ref-value">{{ \Carbon\Carbon::parse($rendezVous->HeureRdv)->format('H:i') }}</span>
                </td>
            </tr>
        </table>
    </div>
    <table class="details-table">
        <thead>
        <tr>
            <th style="text-align: left;">Acte prévu</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td style="text-align: left;">{{ $rendezVous->ActePrevu }}</td>
        </tr>
        </tbody>
    </table>
    <div class="recu-footer">@include('partials.recu-footer')</div>
</div>

<script>
const elements = {
    pageFormat: document.getElementById('pageFormat'),
    container: document.getElementById('documentContainer')
};

function updatePageFormat() {
    const isA5 = elements.pageFormat.value === 'A5';
    elements.container.classList.toggle('a4', !isA5);
    elements.container.classList.toggle('a5', isA5);
}
</script>
</body>
</html> 