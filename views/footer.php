<!-- Footer -->
<footer class="site-footer">
    <div class="footer-container">
        <!-- Section 1: À propos -->
        <div class="footer-section">
            <h3>À propos de StudTraj</h3>
            <p>Plateforme dédiée à l'apprentissage et à la visualisation de données pour les étudiants.</p>
        </div>

        <!-- Section 2: Contact -->
        <div class="footer-section">
            <h3>Contact</h3>
            <ul class="footer-contact">
                <li>📧 <a href="mailto:StudTraj.amu@gmail.com">StudTraj.amu@gmail.com</a></li>
                <li>📞 +33 01 23 45 67 89</li>
            </ul>
        </div>

        <!-- Section 3: Labels & Certifications -->
        <div class="footer-section">
            <h3>Qualité & Accessibilité</h3>
            <div class="footer-badges">
                <a href="https://validator.w3.org/check?uri=referer" target="_blank" rel="noopener" title="HTML5 Valide">
                    <img src="https://www.w3.org/html/logo/badge/html5-badge-h-css3-semantics.png"
                         alt="HTML5 Valide" width="100" height="auto">
                </a>
                <a href="https://jigsaw.w3.org/css-validator/check/referer" target="_blank" rel="noopener" title="CSS3 Valide">
                    <img src="https://jigsaw.w3.org/css-validator/images/vcss"
                         alt="CSS Valide" width="88" height="31">
                </a>
                <a href="https://www.ecoindex.fr" target="_blank" rel="noopener" title="Performance environnementale">
                    <span class="eco-badge">🌱 EcoIndex</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Barre inférieure -->
    <div class="footer-bottom">
        <div class="footer-bottom-content">
            <p>&copy; <?php echo date('Y'); ?> StudTraj - Tous droits réservés</p>
            <p class="footer-legal">
                <a href="mentions-legales.php">Mentions légales</a> |
                <a href="#" onclick="showCGU()">CGU</a> |
                <a href="#" onclick="showPrivacy()">Confidentialité</a> |
                <a href="../sitemap.php">Sitemap XML</a>
            </p>
            <p class="footer-compliance">
                Conformité RGPD | Accessibilité : Partiellement conforme |
                <a href="https://www.w3.org/WAI/WCAG2AA-Conformance" target="_blank" rel="noopener">WCAG 2.1 AA</a>
            </p>
        </div>
    </div>
</footer>

<!-- Modal CGU -->
<div id="cguModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeCGU()">&times;</span>
        <h2>Conditions Générales d'Utilisation</h2>
        <div class="modal-text">
            <h3>1. Acceptation des conditions</h3>
            <p>En accédant et en utilisant StudTraj, vous acceptez d'être lié par ces conditions générales d'utilisation.</p>

            <h3>2. Utilisation du service</h3>
            <p>Ce service est destiné à un usage éducatif. Toute utilisation abusive ou frauduleuse est strictement interdite.</p>

            <h3>3. Compte utilisateur</h3>
            <p>Vous êtes responsable de la confidentialité de vos identifiants de connexion.</p>

            <h3>4. Propriété intellectuelle</h3>
            <p>Tous les contenus présents sur StudTraj sont protégés par les droits d'auteur.</p>
        </div>
    </div>
</div>

<!-- Modal Politique de confidentialité -->
<div id="privacyModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closePrivacy()">&times;</span>
        <h2>Politique de confidentialité</h2>
        <div class="modal-text">
            <h3>1. Collecte des données</h3>
            <p>Nous collectons uniquement les données nécessaires à la création et gestion de votre compte (nom, prénom, email).</p>

            <h3>2. Utilisation des données</h3>
            <p>Vos données sont utilisées exclusivement pour le fonctionnement du service et ne sont jamais partagées avec des tiers.</p>

            <h3>3. Sécurité</h3>
            <p>Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos données personnelles.</p>

            <h3>4. Vos droits</h3>
            <p>Conformément au RGPD, vous disposez d'un droit d'accès, de rectification, de suppression et d'opposition.</p>

            <h3>5. Contact</h3>
            <p>Pour toute question : StudTraj.amu@gmail.com</p>
        </div>
    </div>
</div>

<script>
// Fonctions pour les modals CGU et Confidentialité
function showCGU() {
    document.getElementById('cguModal').style.display = 'block';
}

function closeCGU() {
    document.getElementById('cguModal').style.display = 'none';
}

function showPrivacy() {
    document.getElementById('privacyModal').style.display = 'block';
}

function closePrivacy() {
    document.getElementById('privacyModal').style.display = 'none';
}

// Fermer les modals en cliquant en dehors
window.onclick = function(event) {
    const cguModal = document.getElementById('cguModal');
    const privacyModal = document.getElementById('privacyModal');
    if (event.target === cguModal) {
        closeCGU();
    }
    if (event.target === privacyModal) {
        closePrivacy();
    }
}
</script>
