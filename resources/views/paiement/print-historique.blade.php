<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des paiements</title>
    <style>
        @media print {
            .footer-fixe { display: none !important; }
        }
    </style>
</head>
<body>
<div class="recu-header">
    @include('partials.recu-header')
</div>
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
<script>
    window.onload = function() {
        window.print();
    };
</script>
</body>
</html> 