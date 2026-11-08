(() => {
    "use strict";

    const scene = document.querySelector("[data-scene]");

    if (!scene) {
        return;
    }

    const { gsap, ScrollTrigger } = window;
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (reduceMotion || !gsap || !ScrollTrigger) {
        scene.classList.add("is-static");

        return;
    }

    gsap.registerPlugin(ScrollTrigger);
    scene.classList.add("is-scrubbing");

    const bar = scene.querySelector("[data-scene-progress]");
    const chapters = Array.from(scene.querySelectorAll("[data-chapter]")).map((element) => ({
        element,
        start: Number.parseFloat(element.dataset.start) || 0,
        end: Number.parseFloat(element.dataset.end) || 1,
    }));
    const shots = Array.from(scene.querySelectorAll("[data-scene-video]")).map((video) => ({
        video,
        start: Number.parseFloat(video.dataset.start) || 0,
        end: Number.parseFloat(video.dataset.end) || 1,
        target: 0,
        requested: false,
        ready: false,
        failed: false,
    }));
    const saveData = Boolean(navigator.connection && navigator.connection.saveData);
    let activeChapter = -1;
    let activeShot = -1;
    let sceneNear = false;

    function rangeIndex(items, progress) {
        const index = items.findIndex((item) => progress >= item.start && progress < item.end);

        if (index !== -1) {
            return index;
        }

        return progress >= 1 ? items.length - 1 : 0;
    }

    function setChapter(progress) {
        const index = rangeIndex(chapters, progress);

        if (index === activeChapter) {
            return;
        }

        activeChapter = index;
        chapters.forEach((chapter, position) => {
            chapter.element.classList.toggle("is-active", position === index);
        });
    }

    function seek(shot) {
        const { video } = shot;

        if (video.readyState < 1 || !Number.isFinite(video.duration) || video.duration <= 0 || video.seeking) {
            return;
        }

        const time = Math.min(video.duration - 0.05, Math.max(0, shot.target * video.duration));

        if (Math.abs(video.currentTime - time) > 0.02) {
            video.currentTime = time;
        }
    }

    function requestShot(index) {
        const shot = shots[index];

        if (!shot || shot.requested || saveData) {
            return;
        }

        shot.requested = true;
        const source = shot.video.dataset.src;

        if (!source) {
            shot.failed = true;

            return;
        }

        shot.video.preload = "auto";
        shot.video.src = source;
        shot.video.load();
    }

    function showFrame() {
        let visible = -1;

        for (let position = activeShot; position >= 0; position -= 1) {
            if (shots[position].ready) {
                visible = position;
                break;
            }
        }

        shots.forEach((shot, position) => {
            shot.video.classList.toggle("is-current", position === visible);
        });
    }

    function requestNext() {
        if (sceneNear) {
            requestShot(shots.findIndex((shot) => !shot.requested));
        }
    }

    function render(progress) {
        if (bar) {
            bar.style.transform = `scaleX(${progress})`;
        }

        setChapter(progress);

        if (!shots.length) {
            return;
        }

        const index = rangeIndex(shots, progress);
        const current = shots[index];
        const span = current.end - current.start || 1;

        shots.forEach((shot, position) => {
            if (position < index) {
                shot.target = 1;
            } else if (position > index) {
                shot.target = 0;
            } else {
                shot.target = Math.min(1, Math.max(0, (progress - current.start) / span));
            }
        });

        if (index === activeShot) {
            seek(current);

            return;
        }

        activeShot = index;
        shots.forEach(seek);
        showFrame();

        if (sceneNear) {
            requestShot(index);
        }
    }

    function approach() {
        if (sceneNear) {
            return;
        }

        sceneNear = true;
        requestShot(Math.max(activeShot, 0));
    }

    shots.forEach((shot) => {
        const { video } = shot;

        video.addEventListener("loadedmetadata", () => {
            const attempt = video.play();

            if (attempt && typeof attempt.then === "function") {
                attempt.then(() => {
                    video.pause();
                    seek(shot);
                }).catch(() => {});
            }

            seek(shot);
        });
        video.addEventListener("loadeddata", () => {
            shot.ready = true;
            video.classList.add("is-ready");
            seek(shot);
            showFrame();
            requestNext();
        });
        video.addEventListener("seeked", () => seek(shot));
        video.addEventListener("error", () => {
            shot.failed = true;
            shot.ready = false;
            video.classList.remove("is-ready");
            showFrame();
            scene.classList.toggle("is-video-failed", shots.every((item) => item.failed));
            requestNext();
        });
    });

    if (shots.length) {
        ScrollTrigger.create({
            trigger: scene,
            start: "top 85%",
            end: "bottom top",
            onEnter: approach,
            onEnterBack: approach,
        });

        if (scene.getBoundingClientRect().top < window.innerHeight * 0.85) {
            approach();
        }
    }

    const state = { progress: 0 };

    gsap.to(state, {
        progress: 1,
        ease: "none",
        onUpdate: () => render(state.progress),
        scrollTrigger: {
            trigger: scene,
            start: "top top",
            end: "bottom bottom",
            scrub: 0.6,
        },
    });

    render(0);
})();
