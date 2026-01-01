<?php
// public/errors/404.php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - <?= SITE_NAME ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #667eea;
            margin: 0;
        }
        .error-message {
            font-size: 24px;
            margin: 20px 0;
            color: #333;
        }
        .home-link {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
        }
        .home-link:hover {
            background: #5a67d8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <p class="error-message">Oops ! La page que vous cherchez n'existe pas.</p>
        <a href="/" class="home-link">Retour à l'accueil</a>
    </div>
</body>
</html>