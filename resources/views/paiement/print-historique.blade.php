<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des paiements</title>
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
        .histo-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .histo-table th,
        .histo-table td {
            border: 1px solid #222;
            font-size: 11px;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }
        .histo-table th {
            background: #f4f6fa;
            font-weight: bold;
            color: #222;
        }
        .histo-table th:first-child,
        .histo-table td:first-child {
            text-align: center;
            width: 15%;
        }
        .histo-table th:nth-child(2),
        .histo-table td:nth-child(2) {
            text-align: left;
            width: 35%;
        }
        .histo-table th:nth-child(3),
        .histo-table td:nth-child(3) {
            text-align: right;
            width: 20%;
        }
        @media print {
            .a4 { box-shadow: none; }
            .recu-footer { position: fixed; bottom: 0; left: 0; width: 100%; }
        }
    </style>
</head>
<body>
<div class="a4">
    <div class="recu-header">
        @include('partials.recu-header')
    </div>
    <div class="consult-title">HISTORIQUE DES PAIEMENTS</div>
    <div class="main-content">
        <div class="table-container">
            <table class="histo-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Désignation</th>
                        <th>Montant</th>
                        <th>Type</th>
                        <th>Médecin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentHistory as $payment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($payment->dateoper)->format('d/m/Y H:i') }}</td>
                            <td>{{ $payment->designation }}</td>
                            <td>{{ number_format(abs($payment->MontantOperation), 0, ',', ' ') }} MRU</td>
                            <td>{{ $payment->entreEspece ? 'Entrée' : 'Sortie' }}</td>
                            <td>{{ $payment->medecin }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="height: 60px;"></div>
    </div>
    <div class="recu-footer">
        @include('partials.recu-footer')
    </div>
</div>
<script>
    window.onload = function() {
        window.print();
    };
</script>
</body>
</html> 