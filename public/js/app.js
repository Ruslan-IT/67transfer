document.addEventListener("DOMContentLoaded", () => {

    const menuButton = document.querySelector(".menu-button");
    const menuClose = document.querySelector(".menu-close");
    const menuOverlay = document.querySelector(".menu-overlay");
    const menuLinks = document.querySelectorAll(".menu-overlay .menu__item");

    if (!menuButton || !menuClose || !menuOverlay) {
        return;
    }

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



document.addEventListener('DOMContentLoaded', () => {

    gsap.registerPlugin(ScrollTrigger);


    /* =========================================
       PRELOADER + HERO
       ========================================= */

    const preloader = document.querySelector('.preloader');

    if (preloader) {

        const preloaderTitle = new SplitType('.preloader__title', {
            types: 'chars'
        });

        const preloaderTimeline = gsap.timeline();

        // Буквы собираются в название
        preloaderTimeline.from(preloaderTitle.chars, {
            opacity: 0,
            y: 40,
            x: 15,
            rotation: 5,
            stagger: 0.04,
            duration: 0.7,
            ease: 'power3.out'
        })

            // Небольшая пауза
            .to({}, {
                duration: 0.5
            })

            // Чёрный экран уезжает вверх
            .to(preloader, {
                yPercent: -100,
                duration: 1.2,
                ease: 'power4.inOut'
            })

            // Badge
            .from('.hero__badge', {
                opacity: 0,
                scale: 0.8,
                duration: 0.7,
                ease: 'power3.out'
            }, '-=0.5')

            // Заголовок
            .from('.hero__text h1', {
                opacity: 0,
                y: 80,
                duration: 1,
                ease: 'power4.out'
            }, '-=0.4')

            // Подзаголовок
            .from('.hero__subtitle', {
                opacity: 0,
                y: 30,
                duration: 0.6,
                ease: 'power3.out'
            }, '-=0.6')

            // Описание
            .from('.hero__description', {
                opacity: 0,
                y: 30,
                duration: 0.6,
                ease: 'power3.out'
            }, '-=0.4')

            // Кнопка
            .from('.hero__button', {
                opacity: 0,
                y: 25,
                duration: 0.6,
                ease: 'power3.out'
            }, '-=0.4')

            // Фотография
            .from('.hero__visual', {
                opacity: 0,
                x: 80,
                duration: 1,
                ease: 'power4.out'
            }, '-=0.8')

            // Вертикальная надпись
            .from('.hero__vertical-text', {
                opacity: 0,
                y: 40,
                duration: 0.7,
                ease: 'power3.out'
            }, '-=0.6');
    }


    /* =========================================
       INTRO ANIMATION
       ========================================= */

    const introSection = document.querySelector('.intro');

    if (introSection) {

        const introNumber = introSection.querySelector('.section-number');
        const introTitle = introSection.querySelector('h2');
        const introDescription = introSection.querySelector('p');

        // Разбиваем H2 на строки
        const introTitleSplit = new SplitType(introTitle, {
            types: 'lines'
        });

        // Начальное состояние
        gsap.set(introNumber, {
            opacity: 0,
            y: 30
        });

        gsap.set(introTitleSplit.lines, {
            opacity: 0,
            y: 80
        });

        gsap.set(introDescription, {
            opacity: 0,
            y: 40
        });


        // Timeline INTRO
        const introTimeline = gsap.timeline({
            scrollTrigger: {
                trigger: introSection,
                start: 'top 75%',
                toggleActions: 'play none none none'
            }
        });


        // Номер секции
        introTimeline.to(introNumber, {
            opacity: 1,
            y: 0,
            duration: 0.7,
            ease: 'power3.out'
        });


        // Заголовок построчно
        introTimeline.to(introTitleSplit.lines, {
            opacity: 1,
            y: 0,
            duration: 0.9,
            stagger: 0.12,
            ease: 'power4.out'
        }, '-=0.35');


        // Описание
        introTimeline.to(introDescription, {
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: 'power3.out'
        }, '-=0.5');

    }

});

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.querySelector("[data-contact-modal]");

    if (!modal) {
        return;
    }

    const form = modal.querySelector("[data-contact-form]");
    const success = modal.querySelector("[data-contact-success]");
    const errorBox = modal.querySelector("[data-contact-error]");
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

    const openModal = () => {
        modal.hidden = false;
        document.body.classList.add("modal-open");
    };

    const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove("modal-open");
    };

    document.querySelectorAll("[data-contact-open]").forEach((button) => {
        button.addEventListener("click", openModal);
    });

    modal.querySelectorAll("[data-contact-close]").forEach((element) => {
        element.addEventListener("click", closeModal);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modal.hidden) {
            closeModal();
        }
    });

    if (!form) {
        return;
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (success) {
            success.hidden = true;
        }

        if (errorBox) {
            errorBox.hidden = true;
            errorBox.textContent = "";
        }

        try {
            const response = await fetch(form.action, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": token || "",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: new FormData(form),
            });

            const payload = await response.json().catch(() => ({}));

            if (response.ok && payload.ok) {
                form.reset();

                if (success) {
                    success.hidden = false;
                    success.textContent = payload.message || "Сообщение успешно отправлено";
                }

                return;
            }

            const errors = payload.errors ? Object.values(payload.errors).flat() : [];

            if (errorBox) {
                errorBox.hidden = false;
                errorBox.textContent = errors[0] || "Проверьте поля и попробуйте ещё раз.";
            }
        } catch (error) {
            if (errorBox) {
                errorBox.hidden = false;
                errorBox.textContent = "Не удалось отправить сообщение. Попробуйте ещё раз.";
            }
        }
    });
});
