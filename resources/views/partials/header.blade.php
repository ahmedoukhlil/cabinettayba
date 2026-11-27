<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $cabinetBrand['name'] }} - Documents</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 100%;
            height: auto;
            max-width: 800px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $cabinetBrand['name'] }}</h1>
        <p>{{ $cabinetBrand['address'] }}</p>
    </div>
</body>
</html>
