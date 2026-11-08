(function () {
    const root = document.querySelector("[data-h2-flight]");

    if (!root) {
        return;
    }

    const pick = (selector) => root.querySelector(selector);
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    if (!window.gsap || !window.ScrollTrigger || reduceMotion.matches) {
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    const lite = Boolean(navigator.connection?.saveData)
        || ((navigator.hardwareConcurrency || 8) <= 4 && (navigator.deviceMemory || 8) <= 4);

    root.classList.add("is-animated");
    root.classList.toggle("is-lite", lite);

    const runway = pick("[data-h2-runway]");
    const order = pick("[data-h2-order]");
    const cabin = pick("[data-h2-cabin]");
    const sky = pick("[data-h2-sky]");
    const bandNear = pick("[data-h2-band-near]");
    const bandFar = pick("[data-h2-band-far]");
    const wisps = pick("[data-h2-wisps]");
    const shade = pick("[data-h2-shade]");
    const shadeSheet = shade.querySelector(".h2-cabin__shade-sheet");
    const shadeLit = pick("[data-h2-shade-lit]");
    const scrub = lite ? true : 0.8;
    const halfWidth = () => window.innerWidth * 0.5;

    gsap.set(shade, { y: 0, yPercent: 0 });
    gsap.set(shadeSheet, { scaleY: 1 });
    gsap.set(shadeLit, { opacity: 0 });

    const wall = pick(".h2-cabin__wall img");
    const wallReady = wall.complete ? Promise.resolve() : wall.decode().catch(() => {});

    Promise.race([wallReady, new Promise((resolve) => setTimeout(resolve, 1800))]).then(() => {
        gsap.timeline({ delay: 0.35, defaults: { duration: 1.7, ease: "power3.inOut" } })
            .to(shade, { yPercent: -92 }, 0)
            .to(shadeSheet, { scaleY: 0.5 }, 0)
            .to(shadeLit, { opacity: 1, duration: 0.9, ease: "power1.out" }, 0.8);
    });

    gsap.timeline({
        defaults: { ease: "none" },
        scrollTrigger: {
            trigger: root,
            start: "top top",
            end: () => "+=" + runway.offsetHeight,
            scrub,
            invalidateOnRefresh: true,
        },
    })
        .to(cabin, { scale: 6.5, xPercent: -2, duration: 0.72 }, 0)
        .to("[data-h2-left]", { x: () => -halfWidth(), scale: 1.6, autoAlpha: 0, duration: 0.34 }, 0)
        .to("[data-h2-right]", { x: () => halfWidth(), scale: 1.6, autoAlpha: 0, duration: 0.34 }, 0)
        .to("[data-h2-hint]", { autoAlpha: 0, duration: 0.1 }, 0)
        .to("[data-h2-vignette]", { opacity: 0, duration: 0.42 }, 0)
        .to("[data-h2-glow]", { opacity: 0, duration: 0.28 }, 0)
        .to("[data-h2-haze]", { opacity: 0.6, duration: 0.14, ease: "sine.in" }, 0.3)
        .to("[data-h2-haze]", { opacity: 0, duration: 0.2, ease: "sine.out" }, 0.44)
        .fromTo(sky, { yPercent: 0, scale: 1.04 }, { yPercent: -50, scale: 1.22, duration: 1 }, 0)
        .fromTo(bandNear, { yPercent: 0, scale: 1 }, { yPercent: 38, scale: 2.4, opacity: 0.25, duration: 1 }, 0)
        .fromTo(bandFar, { scale: 1 }, { yPercent: 18, scale: 1.5, duration: 1 }, 0)
        .fromTo(wisps, { opacity: 0, scale: 0.55, yPercent: 6 }, { opacity: 0.92, scale: 2.5, yPercent: -4, duration: 0.58, ease: "power1.in" }, 0.42)
        .fromTo("[data-h2-cloudwall]", { opacity: 0 }, { opacity: 1, duration: 0.24, ease: "sine.in" }, 0.6)
        .to("[data-h2-cloudwall]", { opacity: 0.12, duration: 0.16, ease: "sine.out" }, 0.84)
        .fromTo("[data-h2-puff-a]", { xPercent: -48, scale: 0.9 }, { xPercent: -72, scale: 2.1, duration: 0.45 }, 0.55)
        .fromTo("[data-h2-puff-b]", { xPercent: -52, scaleX: -0.9, scaleY: 0.9 }, { xPercent: -24, scaleX: -2.4, scaleY: 2.4, duration: 0.45 }, 0.55)
        .fromTo("[data-h2-puff-c]", { xPercent: -50, scale: 1, yPercent: 0 }, { xPercent: -46, scale: 2.8, yPercent: 30, duration: 0.45 }, 0.55);

    gsap.timeline({
        defaults: { ease: "none" },
        scrollTrigger: {
            trigger: order,
            start: "top bottom",
            end: "top 8%",
            scrub,
            invalidateOnRefresh: true,
        },
    })
        .fromTo("[data-h2-tint]", { opacity: 0 }, { opacity: 1, duration: 1 }, 0)
        .fromTo("[data-h2-head]", { autoAlpha: 0, y: 70 }, { autoAlpha: 1, y: 0, duration: 0.55, ease: "power2.out" }, 0.12)
        .fromTo("[data-h2-form] > .transfer-search", { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.45 }, 0.2)
        .fromTo("[data-h2-veil]", { opacity: 1, scale: 0.9 }, { opacity: 0, scale: 1.45, duration: 0.7, ease: "power1.in" }, 0.22);

    let refreshTimer = 0;
    let lastHeight = order.offsetHeight;

    new ResizeObserver(() => {
        if (order.offsetHeight === lastHeight) {
            return;
        }

        lastHeight = order.offsetHeight;
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => ScrollTrigger.refresh(), 200);
    }).observe(order);

    reduceMotion.addEventListener("change", () => window.location.reload());
}());
