
    function openSiteMap() {
    document.getElementById('sitemapModal').style.display = 'block';
}

    function closeSiteMap() {
    document.getElementById('sitemapModal').style.display = 'none';
}

    window.onclick = function(event) {
    const modal = document.getElementById('sitemapModal');
    if (event.target == modal) {
    modal.style.display = 'none';
}
}
