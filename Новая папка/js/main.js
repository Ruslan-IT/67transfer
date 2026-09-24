document.addEventListener("DOMContentLoaded", () => {

    const menuButton = document.querySelector(".menu-button");
    const menuClose = document.querySelector(".menu-close");
    const menuOverlay = document.querySelector(".menu-overlay");
    const menuLinks = document.querySelectorAll(".menu__item");


    function openMenu() {

        menuOverlay.classList.add("active");

        document.body.classList.add("menu-open");

        menuButton.setAttribute("aria-expanded", "true");
    }


    function closeMenu() {

        menuOverlay.classList.remove("active");

        document.body.classList.remove("menu-open");

        menuButton.setAttribute("aria-expanded", "false");
    }


    menuButton.addEventListener("click", openMenu);

    menuClose.addEventListener("click", closeMenu);


    menuLinks.forEach(link => {

        link.addEventListener("click", () => {

            closeMenu();

        });

    });


    document.addEventListener("keydown", event => {

        if (event.key === "Escape") {

            closeMenu();

        }

    });

});