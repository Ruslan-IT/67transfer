(function () {
    const root = document.querySelector("[data-h3-flight]");

    if (!root) {
        return;
    }

    const pick = (selector) => root.querySelector(selector);
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    const header = document.querySelector(".header");
    const cabinBox = pick("[data-h3-cabin]");
    let fit = 1;

    // The frame image is drawn in a 2:1 cover box; the window box is 18.96% of its width
    // with a 1:1.38 aspect, and the bevelled frame around it adds ~38% width and ~30% height.
    // On landscape keep --h3-fit at 1 so the cabin wall covers the viewport like home-2.
    // Portrait may still shrink the cabin so the porthole clears the fixed header.
    const fitPorthole = () => {
        const headerHeight = header ? header.offsetHeight : 0;
        const stageHeight = cabinBox.offsetHeight;
        const layerScale = parseFloat(getComputedStyle(root).getPropertyValue("--h3-layer-scale")) || 1;
        const coverWidth = Math.max(cabinBox.offsetWidth, stageHeight * 2);
        const boxWidth = coverWidth * 0.1896 * layerScale;
        const frameWidth = boxWidth * 1.38;
        const frameHeight = boxWidth * 1.38 * 1.3;
        const gapTop = Math.min(64, Math.max(20, window.innerHeight * 0.06));
        const gapBottom = gapTop + Math.min(72, Math.max(40, window.innerHeight * 0.07));
        const roomHeight = window.innerHeight - headerHeight - gapTop - gapBottom;
        const roomWidth = window.innerWidth - 32;
        const roomCenter = headerHeight + gapTop + roomHeight / 2;
        const portrait = window.innerHeight > window.innerWidth;
        // Minimum fit that still keeps the scaled cabin wall covering the stage width.
        const coverFit = cabinBox.offsetWidth > 0
            ? root.clientWidth / (cabinBox.offsetWidth * layerScale)
            : 1;

        if (portrait) {
            fit = Math.min(1, roomHeight / frameHeight, roomWidth / frameWidth);
            if (Number.isFinite(coverFit) && coverFit > 0) {
                fit = Math.max(fit, Math.min(1, coverFit));
            }
        } else {
            fit = 1;
        }

        root.style.setProperty("--h3-fit", fit.toFixed(4));
        root.style.setProperty("--h3-porthole-shift", Math.round(roomCenter - stageHeight / 2) + "px");
        document.documentElement.style.setProperty("--h3-header-h", headerHeight + "px");
    };

    fitPorthole();
    window.addEventListener("resize", fitPorthole);

    const sectionsMenu = document.querySelector("[data-h3-nav-mobile]");

    if (sectionsMenu) {
        sectionsMenu.addEventListener("click", (event) => {
            if (event.target.closest("a")) {
                sectionsMenu.open = false;
            }
        });
        document.addEventListener("click", (event) => {
            if (sectionsMenu.open && !sectionsMenu.contains(event.target)) {
                sectionsMenu.open = false;
            }
        });
    }

    if (!window.gsap || !window.ScrollTrigger || reduceMotion.matches) {
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    const lite = Boolean(navigator.connection?.saveData)
        || ((navigator.hardwareConcurrency || 8) <= 4 && (navigator.deviceMemory || 8) <= 4);

    root.classList.add("is-animated");
    root.classList.toggle("is-lite", lite);

    const runway = pick("[data-h3-runway]");
    const order = pick("[data-h3-order]");
    const cabin = cabinBox;
    const sky = pick("[data-h3-sky]");
    const bandNear = pick("[data-h3-band-near]");
    const bandFar = pick("[data-h3-band-far]");
    const wisps = pick("[data-h3-wisps]");
    const land = pick("[data-h3-land]");
    const deck = pick("[data-h3-deck]");
    const fog = pick("[data-h3-fog]");
    const shade = pick("[data-h3-shade]");
    const shadeSheet = shade.querySelector(".h3-cabin__shade-sheet");
    const shadeLit = pick("[data-h3-shade-lit]");
    const scrub = lite ? true : 0.8;
    const halfWidth = () => window.innerWidth * 0.5;

    // Home 2 porthole timing, compressed into the first part of the longer runway.
    const porthole = 0.55;
    const at = (position) => position * porthole;

    // Where each cloud mass drifts while the camera sinks below the deck.
    const masses = [
        { x: -58, y: -72, scale: 1.5, fade: 0.69 },
        { x: 56, y: -74, scale: 1.5, fade: 0.71 },
        { x: -78, y: -36, scale: 1.65, fade: 0.65 },
        { x: 78, y: -34, scale: 1.65, fade: 0.67 },
        { x: 26, y: -48, scale: 2.2, fade: 0.62 },
        { x: -4, y: -88, scale: 2.4, fade: 0.64 },
        { x: -12, y: -64, scale: 1.9, fade: 0.72 },
    ];

    gsap.set(shade, { y: 0, yPercent: 0 });
    gsap.set(shadeSheet, { scaleY: 1 });
    gsap.set(shadeLit, { opacity: 0 });

    const wall = pick(".h3-cabin__wall img");
    const wallReady = wall.complete ? Promise.resolve() : wall.decode().catch(() => {});

    Promise.race([wallReady, new Promise((resolve) => setTimeout(resolve, 1800))]).then(() => {
        gsap.timeline({ delay: 0.35, defaults: { duration: 1.7, ease: "power3.inOut" } })
            .to(shade, { yPercent: -92 }, 0)
            .to(shadeSheet, { scaleY: 0.5 }, 0)
            .to(shadeLit, { opacity: 1, duration: 0.9, ease: "power1.out" }, 0.8);
    });

    const scene = gsap.timeline({
        defaults: { ease: "none" },
        scrollTrigger: {
            trigger: root,
            start: "top top",
            end: () => "+=" + runway.offsetHeight,
            scrub,
            invalidateOnRefresh: true,
        },
    });

    scene
        .to(cabin, { scale: () => 6.5 / fit, xPercent: -2, duration: at(0.72) }, 0)
        .to("[data-h3-left]", { x: () => -halfWidth(), scale: 1.6, autoAlpha: 0, duration: at(0.34) }, 0)
        .to("[data-h3-right]", { x: () => halfWidth(), scale: 1.6, autoAlpha: 0, duration: at(0.34) }, 0)
        .to("[data-h3-hint]", { autoAlpha: 0, duration: at(0.1) }, 0)
        .to("[data-h3-vignette]", { opacity: 0, duration: at(0.42) }, 0)
        .to("[data-h3-glow]", { opacity: 0, duration: at(0.28) }, 0)
        .to("[data-h3-haze]", { opacity: 0.6, duration: at(0.14), ease: "sine.in" }, at(0.3))
        .to("[data-h3-haze]", { opacity: 0, duration: at(0.2), ease: "sine.out" }, at(0.44))
        .fromTo(sky, { yPercent: 0, scale: 1.04 }, { yPercent: -50, scale: 1.22, duration: at(1) }, 0)
        .fromTo(bandNear, { yPercent: 0, scale: 1 }, { yPercent: 38, scale: 2.4, opacity: 0.25, duration: at(1) }, 0)
        .fromTo(bandFar, { scale: 1 }, { yPercent: 18, scale: 1.5, duration: at(1) }, 0)
        .fromTo(wisps, { opacity: 0, scale: 0.55, yPercent: 6 }, { opacity: 0.92, scale: 2.5, yPercent: -4, duration: at(0.58), ease: "power1.in" }, at(0.42))
        .to(wisps, { opacity: 0, duration: 0.03 }, 0.555)
        .fromTo("[data-h3-cloudwall]", { opacity: 0 }, { opacity: 1, duration: at(0.24), ease: "sine.in" }, at(0.6))
        .to("[data-h3-cloudwall]", { opacity: 0, duration: 0.08 }, 0.56)
        .fromTo("[data-h3-puff-a]", { xPercent: -48, scale: 0.9 }, { xPercent: -72, scale: 2.1, duration: at(0.45) }, at(0.55))
        .fromTo("[data-h3-puff-b]", { xPercent: -52, scaleX: -0.9, scaleY: 0.9 }, { xPercent: -24, scaleX: -2.4, scaleY: 2.4, duration: at(0.45) }, at(0.55))
        .fromTo("[data-h3-puff-c]", { xPercent: -50, scale: 1, yPercent: 0 }, { xPercent: -46, scale: 2.8, yPercent: 30, duration: at(0.45) }, at(0.55));

    // Inside the cloud: a full white-out hides the switch from sky to land.
    scene
        .fromTo(fog, { opacity: 0 }, { opacity: 1, duration: 0.09, ease: "sine.inOut" }, 0.44)
        .fromTo(deck, { opacity: 0 }, { opacity: 1, duration: 0.02 }, 0.51)
        .fromTo(land, { opacity: 0 }, { opacity: 1, duration: 0.02 }, 0.52)
        .to(fog, { opacity: 0, duration: 0.12, ease: "sine.inOut" }, 0.57);

    // Below the deck: cloud masses part up and to the sides, land opens through the gaps.
    masses.forEach((mass, index) => {
        scene
            .fromTo(`[data-h3-mass="${index + 1}"]`,
                { xPercent: 0, yPercent: 0, scale: 1, opacity: 1 },
                { xPercent: mass.x, yPercent: mass.y, scale: mass.scale, duration: 0.32, ease: "power1.in" },
                0.58)
            .to(`[data-h3-mass="${index + 1}"]`, { opacity: 0, duration: 0.14, ease: "sine.in" }, mass.fade);
    });

    scene
        .fromTo("[data-h3-texture-a]", { xPercent: -50, yPercent: 0, scale: 1.4, opacity: 1 }, { xPercent: -78, yPercent: -90, scale: 3, opacity: 0, duration: 0.3 }, 0.58)
        .fromTo("[data-h3-texture-b]", { xPercent: -44, yPercent: 0, scaleX: -1.5, scaleY: 1.5, opacity: 1 }, { xPercent: -20, yPercent: -110, scaleX: -3.4, scaleY: 3.4, opacity: 0, duration: 0.3 }, 0.6)
        .fromTo("[data-h3-deck-veils]", { xPercent: -50, yPercent: 0, scale: 1, opacity: 0.7 }, { xPercent: -50, yPercent: -40, scale: 2.2, opacity: 0, duration: 0.3 }, 0.58)
        .to(deck, { opacity: 0, duration: 0.06 }, 0.82)
        .fromTo("[data-h3-land-camera]", { scale: 1.16, yPercent: -4 }, { scale: 1, yPercent: 0, duration: 0.3, ease: "power2.out" }, 0.53)
        .to("[data-h3-land-camera]", { scale: 1.12, duration: 0.17 }, 0.83)
        .fromTo("[data-h3-land-haze]", { opacity: 1, yPercent: 0 }, { opacity: 0.45, yPercent: -6, duration: 0.35 }, 0.53);

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
        .fromTo("[data-h3-land-drift]", { scale: 1, yPercent: 0 }, { scale: 1.06, yPercent: -2, duration: 1 }, 0)
        .fromTo("[data-h3-tint]", { opacity: 0 }, { opacity: 1, duration: 1 }, 0)
        .fromTo("[data-h3-head]", { autoAlpha: 0, y: 70 }, { autoAlpha: 1, y: 0, duration: 0.55, ease: "power2.out" }, 0.12)
        .fromTo("[data-h3-form] > .transfer-search", { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.45 }, 0.2)
        .fromTo("[data-h3-veil]", { opacity: 1, scale: 0.9 }, { opacity: 0, scale: 1.45, duration: 0.7, ease: "power1.in" }, 0.22);

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
