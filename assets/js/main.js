// Wrap everything in a DOMContentLoaded listener 
// This ensures the HTML is loaded before the JS tries to find the buttons
document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    // Safety check: only run if the elements exist on the current page
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation(); // Stops the document click from instantly closing it
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('is-active');
        });

        document.addEventListener('click', (e) => {
            if (navLinks.classList.contains('active') && 
                !menuToggle.contains(e.target) && 
                !navLinks.contains(e.target)) {
                navLinks.classList.remove('active');
                menuToggle.classList.remove('is-active');
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // 1. Match the ID from your HTML: <select id="priority">
    const prioritySelect = document.getElementById('priority');
    const notesArea = document.getElementById('notes-plan-trip');

    // 2. Only add the listener if the elements actually exist on the page
    if (prioritySelect && notesArea) {
        prioritySelect.addEventListener('change', function() {
            const selectedValue = this.value;
            
            // Only auto-fill if the user hasn't typed anything yet
            if (notesArea.value.trim() === "") {
                if (selectedValue === 'carbon') {
                    notesArea.value = "I am prioritizing a low carbon footprint. Please consider land-based transport options.";
                } else if (selectedValue === 'local') {
                    notesArea.value = "I want to support local operators. Please suggest community-led tours.";
                }
            }
        });
    }
});


document.addEventListener('DOMContentLoaded', function () {
    // 1. Select all toggles on the page
    const toggles = document.querySelectorAll('.toggle-password');

    toggles.forEach(toggle => {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            
            // 2. Find the input that belongs to THIS eye icon
            // It is the 'previousElementSibling' inside the wrapper
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });
});