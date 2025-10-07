function confirmLogout() {
    if (confirm("Vous voulez bien vous déconecter ?")) {
        window.location.href='../../controllers/deconnexion.php';
    }
}