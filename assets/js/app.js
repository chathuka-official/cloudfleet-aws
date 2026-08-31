const menuButton = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');

function closeMenu() {
    sidebar?.classList.remove('open');
    backdrop?.classList.remove('show');
}

menuButton?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
    backdrop?.classList.toggle('show');
});

backdrop?.addEventListener('click', closeMenu);

sidebar?.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', closeMenu);
});
