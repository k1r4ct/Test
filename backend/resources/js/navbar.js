// SHOW MENU
const showMenu = (toggleId, navbarId, bodyId, sidebarbrand) => {
    const toggle = document.getElementById(toggleId),
        navbar = document.getElementById(navbarId),
        bodypadding = document.getElementById(bodyId);

    if (toggle && navbar) {
        toggle.addEventListener("click", () => {
            // APARECER MENU
            navbar.classList.toggle("show");

            // ROTATE TOGGLE
            toggle.classList.toggle("rotate");
            // PADDING BODY
            bodypadding.classList.toggle("expander");
        });
    }
};
showMenu("nav-toggle", "navbar", "body", "sidebarbrand");

// Change active link when clicked
const linkColor = document.querySelectorAll(".nav-link");

function colorLink() {
    linkColor.forEach((l) => l.classList.remove("active"));
    this.classList.add("active");
}

linkColor.forEach((l) => l.addEventListener("click", colorLink));