<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">
    <meta name="description" content="V├®rification de votre email">
    <title><?= htmlspecialchars($title ?? 'V├®rification Email - StudTraj') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/views/verificationMail.php">
</head>
<body>

<div class="page-wrap">

    <?php if (isset($error_message)) : ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if (isset($success_message)) : ?>
        <div class="success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <div class="card verification-container">
        <h2>V├®rification de l'email</h2>
        <p>Entrez le code ├á 6 chiffres envoy├® par email</p>

        <form action="<?= BASE_URL ?>/index.php?action=emailverification" method="POST">
            <label for="code">Code de v├®rification</label>
            <input type="number"
                   id="code"
                   name="code"
                   class="code-input"
                   placeholder="000000"
                   maxlength="6"
                   required>
            <button type="submit" class="btn-submit" name="verifier">V├®rifier</button>
        </form>

        <p class="mt-3">Vous n'avez pas re├ºu le code ?</p>
        <form action="<?= BASE_URL ?>/index.php?action=resendcode" method="POST">
            <button type="submit" class="btn-secondary" name="renvoyer_code">Renvoyer le code</button>
        </form>
    </div>

    <div class="back-arrow" onclick="window.location.href='<?= BASE_URL ?>/index.html';">ÔåÉ</div>

</div>

</body>
</html>