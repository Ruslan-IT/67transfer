(function () {
    const configNode = document.getElementById("cookie-consent-config");
    const banner = document.querySelector("[data-cookie-banner]");
    const panel = document.querySelector("[data-cookie-panel]");

    if (!configNode || !banner || !panel) {
        return;
    }

    let config = {};

    try {
        config = JSON.parse(configNode.textContent || "{}");
    } catch (error) {
        config = {};
    }

    const storageKey = "tp-cookie-consent";
    const analyticsBox = panel.querySelector("[data-cookie-analytics]");
    const marketingBox = panel.querySelector("[data-cookie-marketing]");
    let metrikaLoaded = false;

    const text = (selector, value) => {
        const node = document.querySelector(selector);

        if (node) {
            node.textContent = value || "";
        }
    };

    text("[data-cookie-banner-text]", config.banner);
    text("[data-cookie-settings-text]", config.settings);
    text("[data-cookie-necessary-title]", config.categories?.necessary?.title);
    text("[data-cookie-necessary-text]", config.categories?.necessary?.text);
    text("[data-cookie-analytics-title]", config.categories?.analytics?.title);
    text("[data-cookie-analytics-text]", config.categories?.analytics?.text);
    text("[data-cookie-marketing-title]", config.categories?.marketing?.title);
    text("[data-cookie-marketing-text]", config.categories?.marketing?.text);

    function read() {
        try {
            const stored = JSON.parse(localStorage.getItem(storageKey) || "null");

            if (!stored || String(stored.version) !== String(config.version || "1")) {
                return null;
            }

            return {
                version: stored.version,
                analytics: stored.analytics === true,
                marketing: stored.marketing === true,
                at: stored.at || "",
            };
        } catch (error) {
            return null;
        }
    }

    function write(choice) {
        const payload = {
            version: config.version || "1",
            analytics: choice.analytics === true,
            marketing: choice.marketing === true,
            necessary: true,
            at: new Date().toISOString(),
        };

        try {
            localStorage.setItem(storageKey, JSON.stringify(payload));
        } catch (error) {
            return null;
        }

        // 180 дней: выбор переживает перезагрузку, но не вечен.
        document.cookie = storageKey + "=" + encodeURIComponent(JSON.stringify({
            version: payload.version,
            analytics: payload.analytics,
            marketing: payload.marketing,
            at: payload.at,
        })) + "; Max-Age=15552000; Path=/; SameSite=Lax";

        return payload;
    }

    function analyticsReady() {
        const analytics = config.analytics || {};

        return analytics.enabled === true
            && analytics.service === "yandex"
            && /^\d{4,15}$/.test(String(analytics.counterId || ""));
    }

    function loadYandex(counterId) {
        const id = String(counterId || "");

        if (metrikaLoaded || !/^\d{4,15}$/.test(id)) {
            return;
        }

        metrikaLoaded = true;

        const src = "https://mc.yandex.ru/metrika/tag.js?id=" + id;

        (function (m, e, t, r, i, k, a) {
            m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
            m[i].l = 1 * new Date();

            for (let j = 0; j < document.scripts.length; j += 1) {
                if (document.scripts[j].src === r) {
                    return;
                }
            }

            k = e.createElement(t);
            a = e.getElementsByTagName(t)[0];
            k.async = 1;
            k.src = r;
            a.parentNode.insertBefore(k, a);
        }(window, document, "script", src, "ym"));

        window.ym(id, "init", {
            ssr: true,
            webvisor: true,
            clickmap: true,
            ecommerce: "dataLayer",
            referrer: document.referrer,
            url: location.href,
            accurateTrackBounce: true,
            trackLinks: true,
        });
    }

    function apply(choice) {
        if (choice && choice.analytics && analyticsReady()) {
            loadYandex(config.analytics.counterId);
        }
    }

    function showBanner(show) {
        banner.hidden = !show;
    }

    function openPanel() {
        const choice = read();
        analyticsBox.checked = Boolean(choice?.analytics);
        marketingBox.checked = Boolean(choice?.marketing);
        panel.hidden = false;
        document.body.classList.add("cookie-open");
        panel.querySelector("[data-cookie-close].cookie-panel__close")?.focus();
    }

    function closePanel() {
        panel.hidden = true;
        document.body.classList.remove("cookie-open");
    }

    function commit(choice) {
        const previous = read();
        const saved = write(choice);

        if (!saved) {
            return;
        }

        showBanner(false);
        closePanel();

        // Скрипт уже в памяти браузера — только перезагрузка надёжно останавливает Метрику.
        if (previous?.analytics && !saved.analytics && metrikaLoaded) {
            window.location.reload();

            return;
        }

        apply(saved);
    }

    banner.querySelector("[data-cookie-accept]").addEventListener("click", () => {
        commit({ analytics: true, marketing: true });
    });
    banner.querySelector("[data-cookie-reject]").addEventListener("click", () => {
        commit({ analytics: false, marketing: false });
    });
    banner.querySelector("[data-cookie-configure]").addEventListener("click", openPanel);
    panel.querySelector("[data-cookie-save]").addEventListener("click", () => {
        commit({
            analytics: analyticsBox.checked,
            marketing: marketingBox.checked,
        });
    });
    panel.querySelectorAll("[data-cookie-close]").forEach((node) => {
        node.addEventListener("click", closePanel);
    });
    document.querySelectorAll("[data-cookie-open]").forEach((node) => {
        node.addEventListener("click", (event) => {
            event.preventDefault();
            openPanel();
        });
    });
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !panel.hidden) {
            closePanel();
        }
    });

    const existing = read();

    if (existing) {
        showBanner(false);
        apply(existing);
    } else {
        showBanner(true);
    }
}());
