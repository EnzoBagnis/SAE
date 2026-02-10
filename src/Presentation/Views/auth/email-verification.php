<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">
    <meta name="description" content="Vérification de votre email">
    <title><?= htmlspecialchars($title ?? 'Vérification Email - StudTraj') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/views/verificationMail.php">
</head>
<body>

<div class="page-wrap">

    <?php if (isset($_SESSION['error'])) : ?>
        <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])) : ?>
        <div class="success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card verification-container">
        <h2>Vérification de l'email</h2>
        <p>Entrez le code à 6 chiffres envoyé par email</p>

        <form action="<?= BASE_URL ?>/index.php?action=emailverification" method="POST">
            <label for="code">Code de vérification</label>
            <input type="text"
                   id="code"
                   name="code"
                   class="code-input"
                   placeholder="000000"
                   maxlength="6"
                   pattern="[0-9]{6}"
                   inputmode="numeric"
                   required>
            <button type="submit" class="btn-submit" name="verifier">Vérifier</button>
        </form>

        <p class="mt-3">Vous n'avez pas reçu le code ?</p>
        <form action="<?= BASE_URL ?>/index.php?action=resendcode" method="POST">
            <button type="submit" class="btn-secondary" name="renvoyer_code">Renvoyer le code</button>
        </form>
    </div>

    <div class="back-arrow" onclick="window.location.href='<?= BASE_URL ?>/index.html';">←</div>

</div>

</body>
</html>
