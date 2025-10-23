// public/js/resources_list.js
function filterResources() {
    const searchText = document.getElementById('searchBar').value.toLowerCase();
    const filterType = document.getElementById('filterType').value; // 'all', 'owner', 'shared'
    const sortOrder = document.getElementById('sortOrder').value;
    const grid = document.getElementById('resourcesGrid');
    let cards = Array.from(grid.getElementsByClassName('resource-card'));

    cards.forEach(card => {
        const name = card.dataset.name.toLowerCase();
        const owner = card.dataset.owner.toLowerCase();
        const accessType = card.dataset.accessType; // 'owner' ou 'shared'

        const matchesSearch = name.includes(searchText) || owner.includes(searchText);
        const matchesType = (filterType === 'all' || accessType === filterType);

        if (matchesSearch && matchesType) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });

    cards.sort((a, b) => {
        const nameA = a.dataset.name.toLowerCase();
        const nameB = b.dataset.name.toLowerCase();
        const ownerA = a.dataset.owner.toLowerCase();
        const ownerB = b.dataset.owner.toLowerCase();

        if (sortOrder === 'name_asc') {
            return nameA.localeCompare(nameB);
        } else if (sortOrder === 'name_desc') {
            return nameB.localeCompare(nameA);
        } else if (sortOrder === 'owner_name_asc') {
            return ownerA.localeCompare(ownerB);
        }
        return 0;
    });

    cards.forEach(card => grid.appendChild(card));
}

document.addEventListener('DOMContentLoaded', () => {
    const filterTypeElement = document.getElementById('filterType');
    if (filterTypeElement) filterTypeElement.addEventListener('change', filterResources);

    const searchBarElement = document.getElementById('searchBar');
    if (searchBarElement) searchBarElement.addEventListener('keyup', filterResources);

    const sortOrderElement = document.getElementById('sortOrder');
    if (sortOrderElement) sortOrderElement.addEventListener('change', filterResources);

    filterResources();
});

function toggleBurgerMenu() {
    const burgerNav = document.getElementById('burgerNav');
    burgerNav.classList.toggle('active');
    document.getElementById('burgerBtn').classList.toggle('open');
}

function openSiteMap() {
    document.getElementById('sitemapModal').style.display = "block";
}

function closeSiteMap() {
    document.getElementById('sitemapModal').style.display = "none";
}

function confirmLogout() {
    if (confirm("Voulez-vous vraiment vous déconnecter ?")) {
        window.location.href = "/SAE/index.php?action=logout";
    }
}