/**
 * Mobile Navigation Script for MyLearn LMS
 * Include this script on all dashboard pages
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (sidebarToggle && sidebar && sidebarOverlay) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('show')) {
                icon.classList.remove('bi-list');
                icon.classList.add('bi-x');
            } else {
                icon.classList.remove('bi-x');
                icon.classList.add('bi-list');
            }
        });
        
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            const icon = sidebarToggle.querySelector('i');
            icon.classList.remove('bi-x');
            icon.classList.add('bi-list');
        });
        
        // Close sidebar when clicking on a link (for mobile)
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                    const icon = sidebarToggle.querySelector('i');
                    icon.classList.remove('bi-x');
                    icon.classList.add('bi-list');
                }
            });
        });
    }
});
