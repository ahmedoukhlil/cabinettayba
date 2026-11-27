<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pied de page - {{ $cabinetBrand['name'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .footer {
            width: 100%;
            text-align: center;
            margin-top: 20px;
        }
        .footer img {
            width: 100%;
            height: auto;
            max-width: 800px;
        }
    </style>
</head>
<body>
    <div class="footer">
        <p>{{ $cabinetBrand['name'] }} · {{ $cabinetBrand['phone'] }} · {{ $cabinetBrand['email'] }}</p>
    </div>
</body>
</html>
