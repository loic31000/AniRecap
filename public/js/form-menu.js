document.addEventListener("DOMContentLoaded", () => {
    const openButton = document.querySelector("[data-form-menu-open]");
    const overlay = document.querySelector("[data-form-menu-overlay]");

    if (!openButton || !overlay) {
        return;
    }

    const closeButtons = overlay.querySelectorAll("[data-form-menu-close]");

    function openMenu() {
        overlay.classList.add("is-open");
        overlay.setAttribute("aria-hidden", "false");
        openButton.setAttribute("aria-expanded", "true");
        document.body.classList.add("form-menu-is-open");
    }

    function closeMenu() {
        overlay.classList.remove("is-open");
        overlay.setAttribute("aria-hidden", "true");
        openButton.setAttribute("aria-expanded", "false");
        document.body.classList.remove("form-menu-is-open");
        openButton.focus();
    }

    openButton.addEventListener("click", openMenu);

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeMenu);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && overlay.classList.contains("is-open")) {
            closeMenu();
        }
    });
});