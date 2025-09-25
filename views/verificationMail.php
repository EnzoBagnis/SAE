<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <meta name="description" content="Ceci est une meta description">
    <title>Vérification Email</title>
    <!-- <link rel="stylesheet" href="../public/css/style.css"> -->
</head>
<body>
<h1>Vérification de l'Email</h1>
<?php if(isset($_GET['erreur']) && $_GET['erreur'] == 'code_incorrect'): ?>
    <p style="color:red;">Code incorrect, veuillez réessayer.</p>
<?php endif; ?>
<form action="../controllers/traitement.php" method="POST">
    <label for="mailVerifcation">Code : </label>
    <input type="number" id="code" name="code" required>
    <button type="submit" name="verifier">Vérifier</button>
</form>
</body>
</html>