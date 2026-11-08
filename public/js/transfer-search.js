(() => {
    if (window.__transferSearchInit) {
        return;
    }

    window.__transferSearchInit = true;

    const root = document.querySelector("[data-transfer-form]");
    const configNode = document.getElementById("transfer-search-config");

    if (!root || !configNode) {
        return;
    }

    let config = { active: false, browserKey: null, language: "ru", reason: "missing_key" };

    try {
        config = JSON.parse(configNode.textContent || "{}");
    } catch (error) {
        config = { active: false, browserKey: null, language: "ru", reason: "missing_key" };
    }

    const PLACE_FIELDS = ["id", "displayName", "formattedAddress", "location", "addressComponents", "primaryType", "types"];
    const MIN_CHARS = 3;
    const MAX_COUNT = 50;
    const MONTHS = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
    const MONTHS_GEN = ["января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря"];
    const WEEKDAYS = ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"];
    const PEOPLE = ["adults", "children", "infants"];
    const LUGGAGE = ["suitcases", "cabin", "strollers", "sports", "wheelchairs", "other"];
    const SEATS = ["seatCarrier", "seatChild", "seatBooster", "seatOwn"];

    const state = {
        returnPartyCustom: false,
        passengersConfirmed: { outbound: false, return: false },
        luggageConfirmed: { outbound: false, return: false },
    };

    const wishes = {
        pet: false,
        pet_details: "",
        boarding_help: false,
        boarding_help_details: "",
        extra_stop: false,
        extra_stop_details: "",
        other: false,
        other_details: "",
    };

    const counters = {
        outbound: blankParty(),
        return: blankParty(),
    };

    const places = {
        "outbound-pickup": blankPlace(),
        "outbound-dropoff": blankPlace(),
        "return-pickup": blankPlace(),
        "return-dropoff": blankPlace(),
    };

    const sessions = new Map();
    const requestSeq = new Map();
    const suggestionCache = new Map();
    const activeIndex = new Map();
    const choosing = new Map();
    const timers = new Map();
    let placesPromise = null;
    let placesLibrary = null;
    let placesFailed = false;
    let submitting = false;
    let lastTrigger = null;

    const alertBox = root.querySelector("[data-form-alert]");
    const section = root.closest(".transfer-search");
    const backdrop = root.querySelector("[data-backdrop]");

    function blankParty() {
        return {
            adults: 0,
            children: 0,
            infants: 0,
            childAges: [],
            infantAges: [],
            childBirths: [],
            infantBirths: [],
            seatCarrier: 0,
            seatChild: 0,
            seatBooster: 0,
            seatOwn: 0,
            seatHeight: "",
            seatWeight: "",
            suitcases: 0,
            cabin: 0,
            strollers: 0,
            sports: 0,
            wheelchairs: 0,
            other: 0,
            suitcaseSize: "",
            suitcaseDimensions: "",
            strollerFolds: "",
            strollerDimensions: "",
            sportsType: "",
            sportsDimensions: "",
            wheelchairFolds: "",
            wheelchairRide: false,
            wheelchairDimensions: "",
            otherDescription: "",
            otherDimensions: "",
        };
    }

    function blankPlace() {
        return { query: "", dirty: false, selected: null };
    }

    function plural(count, one, few, many) {
        const n = Math.abs(count) % 100;
        const n1 = n % 10;

        if (n > 10 && n < 20) {
            return many;
        }

        if (n1 > 1 && n1 < 5) {
            return few;
        }

        if (n1 === 1) {
            return one;
        }

        return many;
    }

    function fit(list, length, filler) {
        const next = list.slice(0, length);

        while (next.length < length) {
            next.push(filler);
        }

        return next;
    }

    function fitAges(party) {
        party.childAges = fit(party.childAges, party.children, null);
        party.childBirths = fit(party.childBirths, party.children, "");
        party.infantAges = fit(party.infantAges, party.infants, null);
        party.infantBirths = fit(party.infantBirths, party.infants, "");
    }

    function peopleCount(group) {
        const party = counters[group];

        return party.adults + party.children + party.infants;
    }

    function missingAges(group) {
        const party = counters[group];
        fitAges(party);

        for (let index = 0; index < party.children; index += 1) {
            if (party.childAges[index] == null || party.childAges[index] === "") {
                return true;
            }
        }

        for (let index = 0; index < party.infants; index += 1) {
            if (party.infantAges[index] == null || party.infantAges[index] === "") {
                return true;
            }
        }

        return false;
    }

    function asText(value) {
        if (typeof value === "string" && value.trim() !== "") {
            return value.trim();
        }

        if (value && typeof value.text === "string" && value.text.trim() !== "") {
            return value.text.trim();
        }

        if (value && typeof value.toString === "function") {
            const text = value.toString();

            if (text && text !== "[object Object]") {
                return text;
            }
        }

        return null;
    }

    function addressPart(components, type, form) {
        if (!Array.isArray(components)) {
            return null;
        }

        const found = components.find((component) => Array.isArray(component.types) && component.types.includes(type));

        if (!found) {
            return null;
        }

        const value = form === "short" ? (found.shortText ?? found.short_name ?? null) : (found.longText ?? found.long_name ?? null);

        return typeof value === "string" && value !== "" ? value : null;
    }

    function localityOf(components) {
        return addressPart(components, "locality", "long")
            ?? addressPart(components, "postal_town", "long")
            ?? addressPart(components, "administrative_area_level_2", "long")
            ?? addressPart(components, "administrative_area_level_1", "long");
    }

    function normalizeId(id) {
        if (typeof id !== "string" || id === "") {
            return null;
        }

        return id.startsWith("places/") ? id.slice("places/".length) : id;
    }

    function coordinate(location, axis) {
        if (!location) {
            return null;
        }

        const value = typeof location[axis] === "function" ? location[axis]() : location[axis];

        return typeof value === "number" && Number.isFinite(value) ? value : null;
    }

    function toSelected(place, query) {
        const components = place.addressComponents || null;
        const formatted = asText(place.formattedAddress);
        const displayName = asText(place.displayName);

        return {
            place_id: normalizeId(place.id),
            input_text: query || null,
            formatted_address: formatted,
            display_name: displayName,
            latitude: coordinate(place.location, "lat"),
            longitude: coordinate(place.location, "lng"),
            country: addressPart(components, "country", "long"),
            country_code: addressPart(components, "country", "short"),
            locality: localityOf(components),
            postal_code: addressPart(components, "postal_code", "long"),
            primary_type: asText(place.primaryType),
            types: Array.isArray(place.types) && place.types.length ? place.types.slice() : null,
        };
    }

    function labelOf(selected) {
        if (!selected) {
            return "";
        }

        return selected.formatted_address || selected.display_name || selected.input_text || "";
    }

    function shortName(selected) {
        if (!selected) {
            return "";
        }

        return selected.display_name || selected.locality || String(selected.formatted_address || "").split(",")[0];
    }

    function isAirport(selected) {
        if (!selected) {
            return false;
        }

        const types = Array.isArray(selected.types) ? selected.types : [];

        return selected.primary_type === "airport" || types.includes("airport");
    }

    function inputFor(key) {
        return root.querySelector(`[data-place-input="${key}"]`);
    }

    function listFor(key) {
        return root.querySelector(`#suggestions-${key}`);
    }

    function hostFor(key) {
        return root.querySelector(`[data-place-field="${key}"], [data-field="${key}"]`);
    }

    function whenInput(name) {
        return root.querySelector(`[data-when="${name}"]`);
    }

    function whenValue(name) {
        return whenInput(name)?.value || "";
    }

    function contactValue(name) {
        return root.querySelector(`[data-contact="${name}"]`)?.value || "";
    }

    function todayIso() {
        const now = new Date();
        const month = String(now.getMonth() + 1).padStart(2, "0");
        const day = String(now.getDate()).padStart(2, "0");

        return `${now.getFullYear()}-${month}-${day}`;
    }

    function formatDate(iso) {
        const [year, month, day] = iso.split("-").map(Number);

        return `${day} ${MONTHS_GEN[month - 1]} ${year}`;
    }

    function nextSeq(key) {
        const seq = (requestSeq.get(key) || 0) + 1;
        requestSeq.set(key, seq);

        return seq;
    }

    function isCurrent(key, seq) {
        return requestSeq.get(key) === seq;
    }

    function finishSession(key) {
        sessions.delete(key);
        choosing.delete(key);
    }

    function installGoogleMapsLoader(apiKey) {
        if (window.google?.maps?.importLibrary) {
            return;
        }

        ((g) => {
            let loader;
            let script;
            let keyName;
            const message = "The Google Maps JavaScript API";
            const namespace = "google";
            const method = "importLibrary";
            const callback = "__ib__";
            const doc = document;
            let googleObject = window;
            googleObject = googleObject[namespace] || (googleObject[namespace] = {});
            const maps = googleObject.maps || (googleObject.maps = {});
            const libraries = new Set();
            const params = new URLSearchParams();
            const start = () => loader || (loader = new Promise(async (resolve, reject) => {
                script = doc.createElement("script");
                params.set("libraries", [...libraries].join(","));

                for (keyName in g) {
                    params.set(keyName.replace(/[A-Z]/g, (letter) => "_" + letter[0].toLowerCase()), g[keyName]);
                }

                params.set("callback", namespace + ".maps." + callback);
                script.src = "https://maps." + namespace + "apis.com/maps/api/js?" + params.toString();
                maps[callback] = resolve;
                script.async = true;
                script.onerror = () => {
                    loader = reject(new Error(message + " could not load."));
                };
                const nonce = doc.querySelector("script[nonce]");
                script.nonce = nonce ? nonce.nonce : "";
                doc.head.append(script);
            }));

            if (!maps[method]) {
                maps[method] = (library, ...args) => libraries.add(library) && start().then(() => maps[method](library, ...args));
            }
        })({
            key: apiKey,
            v: "weekly",
        });
    }

    function loadPlaces() {
        if (placesFailed) {
            return Promise.reject(new Error("maps-unavailable"));
        }

        if (!config.active || !config.browserKey) {
            return Promise.reject(new Error("maps-unavailable"));
        }

        if (!placesPromise) {
            installGoogleMapsLoader(config.browserKey);
            placesPromise = window.google.maps.importLibrary("places")
                .then((library) => {
                    placesLibrary = library;

                    return library;
                })
                .catch((error) => {
                    placesFailed = true;
                    throw error;
                });
        }

        return placesPromise;
    }

    function tokenFor(key) {
        if (!sessions.has(key)) {
            sessions.set(key, new placesLibrary.AutocompleteSessionToken());
        }

        return sessions.get(key);
    }

    function safeMessage(error) {
        const raw = String(error && error.message ? error.message : error || "");

        if (/RefererNotAllowed/i.test(raw)) {
            return "Ключ Google не разрешает этот сайт. Добавьте http://67transfer.local/* в HTTP referrers.";
        }

        if (/ApiNotActivated|ApiTargetBlocked|REQUEST_DENIED/i.test(raw)) {
            return "В Google Cloud включите Maps JavaScript API и Places API (New) и разрешите их для этого ключа.";
        }

        if (/InvalidKey/i.test(raw)) {
            return "Браузерный ключ Google отклонён. Проверьте его в админке.";
        }

        if (/could not load|maps-unavailable|Failed to fetch|NetworkError|Load failed/i.test(raw)) {
            return "Не удалось загрузить подсказки. Проверьте соединение и браузерный ключ.";
        }

        return "Не удалось получить подсказки. Проверьте соединение и настройки ключа.";
    }

    function showAlert(kind, message) {
        if (!alertBox) {
            return;
        }

        alertBox.hidden = false;
        alertBox.className = `transfer-search__alert transfer-search__alert--${kind}`;
        alertBox.textContent = message;
    }

    function hideAlert() {
        if (!alertBox) {
            return;
        }

        alertBox.hidden = true;
        alertBox.textContent = "";
    }

    function revealFor(key) {
        if (key === "return-pickup" || key === "return-dropoff") {
            const route = root.querySelector("[data-return-route]");

            if (route) {
                route.hidden = false;
            }
        }

        if (key === "return-passengers" || key === "return-luggage") {
            const party = root.querySelector("[data-return-party]");

            if (party) {
                party.hidden = false;
            }
        }

        if (key === "contact-flight") {
            const flight = root.querySelector('[data-flight="outbound"]');

            if (flight) {
                flight.hidden = false;
            }
        }

        if (key === "contact-return-flight") {
            const flight = root.querySelector('[data-flight="return"]');

            if (flight) {
                flight.hidden = false;
            }
        }
    }

    function showFieldError(key, message) {
        const host = hostFor(key);

        if (!host) {
            return;
        }

        revealFor(key);
        host.classList.add("is-invalid");
        const error = host.querySelector("[data-field-error]");

        if (error) {
            error.hidden = false;
            error.textContent = message;
        }
    }

    function clearFieldError(key) {
        const host = hostFor(key);

        if (!host) {
            return;
        }

        host.classList.remove("is-invalid");
        const error = host.querySelector("[data-field-error]");

        if (error) {
            error.hidden = true;
            error.textContent = "";
        }
    }

    function clearErrors() {
        root.querySelectorAll("[data-field-error], [data-panel-error]").forEach((error) => {
            error.hidden = true;
            error.textContent = "";
        });
        root.querySelectorAll(".is-invalid").forEach((host) => host.classList.remove("is-invalid"));
    }

    function applyErrors(errors) {
        clearErrors();
        Object.entries(errors).forEach(([key, message]) => showFieldError(key, message));
        showAlert("error", "Проверьте поля с сообщениями.");
        const first = root.querySelector(".is-invalid input, .is-invalid button, .is-invalid select, .is-invalid textarea");
        first?.focus();
        first?.scrollIntoView({ block: "nearest" });
    }

    function hideList(key) {
        const list = listFor(key);
        const input = inputFor(key);

        if (!list) {
            return;
        }

        list.hidden = true;
        list.replaceChildren();
        activeIndex.set(key, -1);

        if (input) {
            input.setAttribute("aria-expanded", "false");
        }
    }

    function showListMessage(key, message) {
        const list = listFor(key);
        const input = inputFor(key);

        if (!list) {
            return;
        }

        list.hidden = false;
        list.replaceChildren();
        const item = document.createElement("li");
        item.className = "transfer-search__suggestion-status";
        item.textContent = message;
        list.append(item);

        if (input) {
            input.setAttribute("aria-expanded", "true");
        }
    }

    function renderSuggestions(key, predictions) {
        const list = listFor(key);
        const input = inputFor(key);
        suggestionCache.set(key, predictions);
        activeIndex.set(key, -1);

        if (!predictions.length) {
            showListMessage(key, "Ничего не найдено");

            return;
        }

        list.hidden = false;
        list.replaceChildren();

        predictions.forEach((prediction, index) => {
            const item = document.createElement("li");
            item.setAttribute("role", "presentation");
            const button = document.createElement("button");
            button.type = "button";
            button.className = "transfer-search__suggestion";
            button.setAttribute("role", "option");
            button.id = `suggestion-${key}-${index}`;
            button.dataset.index = String(index);

            const title = document.createElement("span");
            title.className = "transfer-search__suggestion-title";
            title.textContent = asText(prediction.mainText) || asText(prediction.text) || "Место";
            button.append(title);

            const metaText = asText(prediction.secondaryText);

            if (metaText) {
                const meta = document.createElement("span");
                meta.className = "transfer-search__suggestion-meta";
                meta.textContent = metaText;
                button.append(meta);
            }

            button.addEventListener("pointerdown", (event) => {
                event.preventDefault();
            });
            button.addEventListener("click", () => {
                choose(key, index);
            });
            item.append(button);
            list.append(item);
        });

        if (input) {
            input.setAttribute("aria-expanded", "true");
        }
    }

    function moveActive(key, delta) {
        const list = listFor(key);
        const options = [...list.querySelectorAll("[role='option']")];

        if (!options.length) {
            return;
        }

        const current = activeIndex.get(key) ?? -1;
        const next = (current + delta + options.length) % options.length;
        activeIndex.set(key, next);
        options.forEach((option, index) => {
            option.setAttribute("aria-selected", index === next ? "true" : "false");
        });
        options[next].scrollIntoView({ block: "nearest" });
        inputFor(key)?.setAttribute("aria-activedescendant", options[next].id);
    }

    async function search(key) {
        const input = inputFor(key);
        const query = input.value.trim();
        const seq = nextSeq(key);

        if (query.length < MIN_CHARS) {
            hideList(key);

            return;
        }

        if (!config.active || placesFailed) {
            hideList(key);

            return;
        }

        showListMessage(key, "Ищем места…");

        try {
            const library = await Promise.race([
                loadPlaces(),
                new Promise((_, reject) => {
                    setTimeout(() => reject(new Error("maps-unavailable")), 4000);
                }),
            ]);

            if (!isCurrent(key, seq)) {
                return;
            }

            const { suggestions } = await library.AutocompleteSuggestion.fetchAutocompleteSuggestions({
                input: query,
                sessionToken: tokenFor(key),
                language: config.language || "ru",
            });

            if (!isCurrent(key, seq)) {
                return;
            }

            const predictions = (suggestions || []).map((suggestion) => suggestion.placePrediction).filter(Boolean);
            renderSuggestions(key, predictions);
        } catch (error) {
            if (!isCurrent(key, seq)) {
                return;
            }

            placesFailed = true;
            suggestionCache.set(key, []);
            showListMessage(key, "Подсказки недоступны. Адрес можно оставить вручную.");
        }
    }

    function schedule(key) {
        clearTimeout(timers.get(key));
        timers.set(key, setTimeout(() => search(key), 250));
    }

    function syncClear(key) {
        const button = root.querySelector(`[data-clear-place="${key}"]`);
        const input = inputFor(key);

        if (button && input) {
            button.hidden = input.value.trim() === "";
        }
    }

    async function choose(key, index) {
        const prediction = (suggestionCache.get(key) || [])[index];

        if (!prediction) {
            return;
        }

        const input = inputFor(key);
        const query = input.value.trim();
        const seq = nextSeq(key);
        choosing.set(key, seq);
        hideList(key);
        input.setAttribute("aria-busy", "true");

        try {
            const place = prediction.toPlace();
            await place.fetchFields({ fields: PLACE_FIELDS });

            if (!isCurrent(key, seq) || choosing.get(key) !== seq) {
                return;
            }

            finishSession(key);
            const selected = toSelected(place, query);

            if (!selected.place_id) {
                places[key].selected = null;
                showFieldError(key, "Google не вернул идентификатор места. Выберите другой вариант.");

                return;
            }

            places[key].selected = selected;
            places[key].query = labelOf(selected);
            places[key].dirty = key.startsWith("return-");
            input.value = places[key].query;
            syncClear(key);
            clearFieldError(key);

            if (key.startsWith("outbound-")) {
                mirrorRoutes();
            }

            updateTimeLabels();
            updateReturnRouteSummary();
        } catch (error) {
            if (!isCurrent(key, seq) || choosing.get(key) !== seq) {
                return;
            }

            finishSession(key);
            places[key].selected = null;
            showFieldError(key, safeMessage(error));
        } finally {
            input.removeAttribute("aria-busy");
            refreshContinue();
        }
    }

    function writeMirrored(fromKey, toKey) {
        if (places[toKey].dirty) {
            return;
        }

        const source = places[fromKey].selected;
        const typed = typedPlace(fromKey);
        const input = inputFor(toKey);

        if (source?.place_id) {
            places[toKey].selected = JSON.parse(JSON.stringify(source));
            places[toKey].query = labelOf(source) || typed;
        } else {
            places[toKey].selected = null;
            places[toKey].query = typed;
        }

        if (input) {
            input.value = places[toKey].query;
        }
    }

    function mirrorRoutes() {
        if (root.dataset.trip !== "return") {
            return;
        }

        writeMirrored("outbound-dropoff", "return-pickup");
        writeMirrored("outbound-pickup", "return-dropoff");
        syncClear("return-pickup");
        syncClear("return-dropoff");
        updateReturnRouteSummary();
        updateTimeLabels();
    }

    function clearPlace(key) {
        const dirty = key.startsWith("return-");
        places[key] = blankPlace();
        places[key].dirty = dirty;
        const input = inputFor(key);

        if (input) {
            input.value = "";
        }

        syncClear(key);
        hideList(key);
        nextSeq(key);

        if (key.startsWith("outbound-")) {
            mirrorRoutes();
        }

        updateTimeLabels();
        updateReturnRouteSummary();
        refreshContinue();
    }

    function swapPlaces() {
        const pickup = places["outbound-pickup"];
        places["outbound-pickup"] = places["outbound-dropoff"];
        places["outbound-dropoff"] = pickup;
        ["outbound-pickup", "outbound-dropoff"].forEach((key) => {
            const input = inputFor(key);

            if (input) {
                input.value = places[key].query || "";
            }

            syncClear(key);
        });
        mirrorRoutes();
        updateTimeLabels();
        updateReturnRouteSummary();
        refreshContinue();
    }

    function renderCounter(stepper) {
        const groupName = stepper.closest("[data-counter-group]")?.dataset.counterGroup;
        const name = stepper.dataset.counter;
        const value = Number(counters[groupName][name] || 0);
        stepper.querySelector("[data-count]").textContent = String(value);
        stepper.querySelector('[data-step="-1"]').disabled = value <= 0;
        stepper.querySelector('[data-step="1"]').disabled = value >= MAX_COUNT;
    }

    function paintExtras(group) {
        const party = counters[group];
        root.querySelectorAll(`[data-counter-group="${group}"] [data-show-when]`).forEach((node) => {
            const mode = node.dataset.showWhen;
            let show = false;

            if (mode === "seats") {
                show = party.seatCarrier + party.seatChild + party.seatBooster + party.seatOwn > 0;
            } else if (mode === "suitcase-large") {
                show = party.suitcases > 0 && party.suitcaseSize === "large";
            } else {
                show = Number(party[mode] || 0) > 0;
            }

            node.hidden = !show;
        });
    }

    function paintDetails(group) {
        root.querySelectorAll(`[data-counter-group="${group}"] [data-detail]`).forEach((input) => {
            const value = counters[group][input.dataset.detail];

            if (input.type === "radio") {
                input.checked = input.value === value;
            } else if (input.type === "checkbox") {
                input.checked = Boolean(value);
            } else if (document.activeElement !== input) {
                input.value = value ?? "";
            }
        });
    }

    function ageBlock(group, kind, index, age, birth) {
        const party = counters[group];
        const wrap = document.createElement("div");
        wrap.className = "transfer-search__detail";
        const title = document.createElement("span");
        const several = (kind === "child" ? party.children : party.infants) > 1;
        title.textContent = `${kind === "child" ? "Возраст ребёнка" : "Возраст младенца"}${several ? ` ${index + 1}` : ""}`;
        const select = document.createElement("select");
        select.dataset.age = kind;
        select.dataset.index = String(index);
        const empty = document.createElement("option");
        empty.value = "";
        empty.textContent = "Выберите";
        select.append(empty);

        if (kind === "child") {
            for (let year = 2; year <= 12; year += 1) {
                const option = document.createElement("option");
                option.value = String(year);
                option.textContent = `${year} ${plural(year, "год", "года", "лет")}`;
                select.append(option);
            }
        } else {
            for (let month = 0; month <= 23; month += 1) {
                const option = document.createElement("option");
                option.value = String(month);
                option.textContent = month === 0 ? "меньше месяца" : `${month} ${plural(month, "месяц", "месяца", "месяцев")}`;
                select.append(option);
            }
        }

        select.value = age == null || age === "" ? "" : String(age);
        const birthTitle = document.createElement("span");
        birthTitle.textContent = "Дата рождения, если к обратной поездке возраст может перейти в другую группу";
        const birthInput = document.createElement("input");
        birthInput.type = "date";
        birthInput.dataset.birth = kind;
        birthInput.dataset.index = String(index);
        birthInput.value = birth || "";
        wrap.append(title, select, birthTitle, birthInput);

        return wrap;
    }

    function renderAges(group) {
        const host = root.querySelector(`[data-ages="${group}"]`);

        if (!host) {
            return;
        }

        const party = counters[group];
        fitAges(party);
        host.replaceChildren();
        party.childAges.forEach((age, index) => host.append(ageBlock(group, "child", index, age, party.childBirths[index])));
        party.infantAges.forEach((age, index) => host.append(ageBlock(group, "infant", index, age, party.infantBirths[index])));
    }

    function paintGroup(group) {
        fitAges(counters[group]);
        root.querySelectorAll(`[data-counter-group="${group}"] [data-counter]`).forEach(renderCounter);
        renderAges(group);
        paintDetails(group);
        paintExtras(group);
        updateSummaries();
    }

    function passengerSummary(group) {
        if (!state.passengersConfirmed[group]) {
            return "Укажите пассажиров";
        }

        const party = counters[group];
        const parts = [];

        if (party.adults) {
            parts.push(`${party.adults} ${plural(party.adults, "взрослый", "взрослых", "взрослых")}`);
        }

        if (party.children) {
            parts.push(`${party.children} ${plural(party.children, "ребёнок", "ребёнка", "детей")}`);
        }

        if (party.infants) {
            parts.push(`${party.infants} ${plural(party.infants, "младенец", "младенца", "младенцев")}`);
        }

        return parts.join(" · ") || "Укажите пассажиров";
    }

    function luggageSummary(group) {
        if (!state.luggageConfirmed[group]) {
            return "Укажите багаж";
        }

        const party = counters[group];
        const parts = [];

        if (party.suitcases) {
            parts.push(`${party.suitcases} ${plural(party.suitcases, "чемодан", "чемодана", "чемоданов")}`);
        }

        if (party.cabin) {
            parts.push(`${party.cabin} ${plural(party.cabin, "место ручной клади", "места ручной клади", "мест ручной клади")}`);
        }

        if (party.strollers) {
            parts.push(`${party.strollers} ${plural(party.strollers, "коляска", "коляски", "колясок")}`);
        }

        if (party.sports) {
            parts.push(`${party.sports} ${plural(party.sports, "ед. спортинвентаря", "ед. спортинвентаря", "ед. спортинвентаря")}`);
        }

        if (party.wheelchairs) {
            parts.push(`${party.wheelchairs} ${plural(party.wheelchairs, "кресло-коляска", "кресла-коляски", "кресел-колясок")}`);
        }

        if (party.other) {
            parts.push(`${party.other} ${plural(party.other, "другая вещь", "другие вещи", "других вещей")}`);
        }

        return parts.join(" · ") || "Без багажа";
    }

    function updateSummaries() {
        ["outbound", "return"].forEach((group) => {
            const passengers = root.querySelector(`[data-summary="passengers-${group}"]`);
            const luggage = root.querySelector(`[data-summary="luggage-${group}"]`);

            if (passengers) {
                const text = passengerSummary(group);
                passengers.textContent = text;
                passengers.classList.toggle("is-placeholder", !state.passengersConfirmed[group]);
            }

            if (luggage) {
                const text = luggageSummary(group);
                luggage.textContent = text;
                luggage.classList.toggle("is-placeholder", !state.luggageConfirmed[group]);
            }
        });
    }

    function syncReturnParty() {
        if (state.returnPartyCustom || root.dataset.trip !== "return") {
            return;
        }

        counters.return = JSON.parse(JSON.stringify(counters.outbound));
        state.passengersConfirmed.return = state.passengersConfirmed.outbound;
        state.luggageConfirmed.return = state.luggageConfirmed.outbound;
        paintGroup("return");
    }

    function enableReturnParty() {
        if (!state.returnPartyCustom) {
            counters.return = JSON.parse(JSON.stringify(counters.outbound));
            state.passengersConfirmed.return = state.passengersConfirmed.outbound;
            state.luggageConfirmed.return = state.luggageConfirmed.outbound;
            state.returnPartyCustom = true;
            paintGroup("return");
        }

        const party = root.querySelector("[data-return-party]");
        const button = root.querySelector("[data-edit-return-party]");

        if (party) {
            party.hidden = false;
        }

        if (button) {
            button.hidden = true;
        }
    }

    function enableReturnRoute() {
        mirrorRoutes();
        const route = root.querySelector("[data-return-route]");
        const button = root.querySelector("[data-edit-return-route]");

        if (route) {
            route.hidden = false;
        }

        if (button) {
            button.hidden = true;
        }
    }

    function updateReturnRouteSummary() {
        const node = root.querySelector("[data-return-summary]");

        if (!node) {
            return;
        }

        const from = shortName(places["return-pickup"].selected);
        const to = shortName(places["return-dropoff"].selected);
        node.textContent = from && to ? `${from} → ${to}` : "Обратные адреса повторяют прямой маршрут";
    }

    function timeMeta(placeKey) {
        const selected = places[placeKey]?.selected;

        if (isAirport(selected)) {
            return { label: "Время прилёта", note: "Местное время аэропорта" };
        }

        if (selected) {
            return { label: "Время подачи", note: "Местное время места отправления" };
        }

        return { label: "Время", note: "Местное время места отправления" };
    }

    function updateTimeLabels() {
        const map = {
            "outbound-time": "outbound-pickup",
            "return-time": "return-pickup",
        };

        Object.entries(map).forEach(([when, placeKey]) => {
            const meta = timeMeta(placeKey);
            const label = root.querySelector(`[data-time-label="${when}"]`);
            const note = root.querySelector(`[data-time-note="${when}"]`);

            if (label) {
                label.textContent = meta.label;
            }

            if (note) {
                note.textContent = meta.note;
            }
        });
    }

    function setWhen(name, value) {
        const input = whenInput(name);
        const label = root.querySelector(`[data-when-label="${name}"]`);

        if (input) {
            input.value = value || "";
        }

        if (!label) {
            refreshContinue();

            return;
        }

        if (!value) {
            label.textContent = name.includes("date") ? "Выберите дату" : "Выберите время";
            label.classList.add("is-placeholder");
            refreshContinue();

            return;
        }

        label.textContent = name.includes("date") ? formatDate(value) : value;
        label.classList.remove("is-placeholder");
        refreshContinue();
    }

    function minDateFor(which) {
        const today = todayIso();

        if (which === "return-date") {
            return whenValue("outbound-date") || today;
        }

        return today;
    }

    function renderCalendar(which) {
        const host = root.querySelector(`[data-calendar="${which}"]`);

        if (!host) {
            return;
        }

        const selected = whenValue(which);
        const minDate = minDateFor(which);

        if (!host.dataset.view || host.dataset.view < minDate.slice(0, 7)) {
            host.dataset.view = (selected && selected >= minDate ? selected : minDate).slice(0, 7);
        }

        const [year, month] = host.dataset.view.split("-").map(Number);
        const first = new Date(year, month - 1, 1);
        let offset = first.getDay() - 1;

        if (offset < 0) {
            offset = 6;
        }

        const days = new Date(year, month, 0).getDate();
        host.replaceChildren();
        const nav = document.createElement("div");
        nav.className = "transfer-search__cal-nav";
        const prev = document.createElement("button");
        prev.type = "button";
        prev.dataset.cal = "prev";
        prev.dataset.calFor = which;
        prev.setAttribute("aria-label", "Предыдущий месяц");
        prev.textContent = "‹";
        prev.disabled = host.dataset.view <= minDate.slice(0, 7);
        const title = document.createElement("p");
        title.textContent = `${MONTHS[month - 1]} ${year}`;
        const next = document.createElement("button");
        next.type = "button";
        next.dataset.cal = "next";
        next.dataset.calFor = which;
        next.setAttribute("aria-label", "Следующий месяц");
        next.textContent = "›";
        nav.append(prev, title, next);

        const grid = document.createElement("div");
        grid.className = "transfer-search__cal-grid";
        WEEKDAYS.forEach((name) => {
            const cell = document.createElement("span");
            cell.className = "transfer-search__cal-weekday";
            cell.textContent = name;
            grid.append(cell);
        });

        for (let blank = 0; blank < offset; blank += 1) {
            grid.append(document.createElement("span"));
        }

        for (let day = 1; day <= days; day += 1) {
            const iso = `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
            const button = document.createElement("button");
            button.type = "button";
            button.className = "transfer-search__cal-day";
            button.dataset.date = iso;
            button.dataset.dateFor = which;
            button.textContent = String(day);
            button.setAttribute("aria-label", formatDate(iso));
            button.setAttribute("aria-pressed", iso === selected ? "true" : "false");

            if (iso === todayIso()) {
                button.classList.add("is-today");
            }

            if (iso < minDate) {
                button.disabled = true;
            }

            grid.append(button);
        }

        host.append(nav, grid);
    }

    function shiftMonth(which, delta) {
        const host = root.querySelector(`[data-calendar="${which}"]`);
        const [year, month] = (host?.dataset.view || todayIso().slice(0, 7)).split("-").map(Number);
        const next = new Date(year, month - 1 + delta, 1);

        if (host) {
            host.dataset.view = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, "0")}`;
        }

        renderCalendar(which);
    }

    function renderTime(which) {
        const host = root.querySelector(`[data-time-cols="${which}"]`);

        if (!host) {
            return;
        }

        const current = whenValue(which);
        const [hour, minute] = current.includes(":") ? current.split(":") : ["", ""];
        host.dataset.pendingHour = hour;
        host.dataset.pendingMinute = minute;
        host.replaceChildren();
        host.append(timeColumn("Часы", "hour", 24, hour), timeColumn("Минуты", "minute", 60, minute, 5));
        window.requestAnimationFrame(() => {
            host.querySelectorAll('[aria-selected="true"]').forEach((button) => {
                const list = button.parentElement;
                list.scrollTop = Math.max(0, button.offsetTop - (list.clientHeight / 2));
            });
        });
    }

    function timeColumn(title, unit, count, selected, step) {
        const wrap = document.createElement("div");
        wrap.className = "transfer-search__time-col";
        const heading = document.createElement("p");
        heading.textContent = title;
        const list = document.createElement("div");
        list.className = "transfer-search__time-list";
        list.setAttribute("role", "listbox");
        list.setAttribute("aria-label", title);
        const increment = step > 0 ? step : 1;

        for (let index = 0; index < count; index += increment) {
            const value = String(index).padStart(2, "0");
            const button = document.createElement("button");
            button.type = "button";
            button.dataset.timeUnit = unit;
            button.dataset.value = value;
            button.textContent = value;
            button.setAttribute("role", "option");
            button.setAttribute("aria-selected", value === selected ? "true" : "false");
            list.append(button);
        }

        wrap.append(heading, list);

        return wrap;
    }

    function commitTime(which) {
        const host = root.querySelector(`[data-time-cols="${which}"]`);
        const error = host?.closest(".transfer-search__pop-panel")?.querySelector("[data-panel-error]");
        const hour = host?.dataset.pendingHour || "";
        const minute = host?.dataset.pendingMinute || "";

        if (hour === "" || minute === "") {
            if (error) {
                error.hidden = false;
                error.textContent = "Выберите часы и минуты.";
            }

            return false;
        }

        if (error) {
            error.hidden = true;
            error.textContent = "";
        }

        setWhen(which, `${hour}:${minute}`);
        clearFieldError(which);

        return true;
    }

    function closePanels(restoreFocus) {
        root.querySelectorAll("[data-pop]").forEach((pop) => {
            const panel = pop.querySelector(".transfer-search__pop-panel");
            const trigger = pop.querySelector(".transfer-search__pop-trigger");

            if (panel) {
                panel.hidden = true;
            }

            if (trigger) {
                trigger.setAttribute("aria-expanded", "false");
            }
        });
        section?.classList.remove("is-panel-open");

        if (backdrop) {
            backdrop.hidden = true;
        }

        if (restoreFocus && lastTrigger) {
            lastTrigger.focus();
        }
    }

    function openPanel(pop) {
        closePanels(false);
        const panel = pop.querySelector(".transfer-search__pop-panel");
        const trigger = pop.querySelector(".transfer-search__pop-trigger");

        if (!panel) {
            return;
        }

        panel.hidden = false;
        trigger?.setAttribute("aria-expanded", "true");
        lastTrigger = trigger || null;
        section?.classList.add("is-panel-open");

        if (backdrop) {
            backdrop.hidden = false;
        }

        Object.keys(places).forEach(hideList);

        if (pop.dataset.pop === "date") {
            renderCalendar(pop.dataset.whenKey);
        }

        if (pop.dataset.pop === "time") {
            updateTimeLabels();
            renderTime(pop.dataset.whenKey);
        }

        panel.querySelector("button, select, input, textarea")?.focus();
    }

    function setTrip(mode) {
        root.dataset.trip = mode;
        root.querySelectorAll('input[name="trip"]').forEach((input) => {
            input.checked = input.value === mode;
        });
        root.querySelectorAll("[data-return-only]").forEach((node) => {
            node.hidden = mode !== "return";
        });

        if (mode === "return") {
            mirrorRoutes();

            if (!state.returnPartyCustom) {
                syncReturnParty();
            }
        }

        updateReturnRouteSummary();
        updateFlightFields();
        updateTimeLabels();
        refreshContinue();
    }

    function updateFlightFields() {
        const outbound = root.querySelector('[data-flight="outbound"]');
        const inbound = root.querySelector('[data-flight="return"]');

        if (outbound) {
            outbound.hidden = !isAirport(places["outbound-pickup"].selected);
        }

        if (inbound) {
            inbound.hidden = root.dataset.trip !== "return" || !isAirport(places["return-pickup"].selected);
        }
    }

    function updateContactLabel() {
        const labels = {
            phone: "Телефон",
            email: "Email",
            telegram: "Telegram",
            whatsapp: "WhatsApp",
        };
        const method = contactValue("method") || "phone";
        const label = root.querySelector("[data-contact-label]");
        const input = root.querySelector('[data-contact="value"]');

        if (label) {
            label.textContent = labels[method] || "Контакт";
        }

        if (input) {
            input.autocomplete = method === "email" ? "email" : "tel";
            input.inputMode = method === "email" ? "email" : "tel";
        }
    }

    function updateWishesLabel() {
        const label = root.querySelector("[data-wishes-label]");

        if (!label) {
            return;
        }

        const parts = [];

        if (wishes.pet) {
            parts.push("питомец");
        }

        if (wishes.boarding_help) {
            parts.push("помощь при посадке");
        }

        if (wishes.extra_stop) {
            parts.push("дополнительная остановка");
        }

        if (wishes.other) {
            parts.push("другие особенности");
        }

        label.textContent = parts.length ? `Особые пожелания: ${parts.join(" · ")}` : "Особые пожелания";
    }

    function ageOn(birthIso, onIso) {
        if (!birthIso || !onIso) {
            return null;
        }

        const [by, bm, bd] = birthIso.split("-").map(Number);
        const [oy, om, od] = onIso.split("-").map(Number);

        if (!by || !oy) {
            return null;
        }

        let years = oy - by;
        let months = om - bm;

        if (od < bd) {
            months -= 1;
        }

        if (months < 0) {
            years -= 1;
            months += 12;
        }

        if (years < 0) {
            return null;
        }

        return { years, months };
    }

    function refreshAgeNote() {
        ["outbound", "return"].forEach((group) => {
            const note = root.querySelector(`[data-age-note="${group}"]`);

            if (!note) {
                return;
            }

            const text = ageShiftText(group);
            note.hidden = !text;
            note.textContent = text;
        });
    }

    function ageShiftText(group) {
        const returnDate = whenValue("return-date");

        if (root.dataset.trip !== "return" || !returnDate) {
            return "";
        }

        const party = counters[group];
        const shifts = [];

        party.childBirths.forEach((birth, index) => {
            const age = ageOn(birth, returnDate);

            if (!age) {
                return;
            }

            const next = age.years >= 13 ? "взрослые" : (age.years >= 2 ? "дети" : "младенцы");

            if (next !== "дети") {
                shifts.push(`ребёнок ${index + 1} на дату обратной поездки попадает в группу «${next}»`);
            }
        });
        party.infantBirths.forEach((birth, index) => {
            const age = ageOn(birth, returnDate);

            if (!age) {
                return;
            }

            const next = age.years >= 13 ? "взрослые" : (age.years >= 2 ? "дети" : "младенцы");

            if (next !== "младенцы") {
                shifts.push(`младенец ${index + 1} на дату обратной поездки попадает в группу «${next}»`);
            }
        });

        return shifts.length ? `Дата рождения сохранена. ${shifts.join(". ")}.` : "";
    }

    function updateReview() {
        const node = root.querySelector("[data-review]");

        if (!node) {
            return;
        }

        const lines = [
            `Туда: ${labelOf(places["outbound-pickup"].selected) || "адрес не выбран"} → ${labelOf(places["outbound-dropoff"].selected) || "адрес не выбран"}`,
            `${formatDate(whenValue("outbound-date"))} ${whenValue("outbound-time")}`,
            `Пассажиры: ${passengerSummary("outbound")}`,
            `Багаж: ${luggageSummary("outbound")}`,
        ];

        if (root.dataset.trip === "return") {
            lines.push(`Обратно: ${labelOf(places["return-pickup"].selected) || "адрес не выбран"} → ${labelOf(places["return-dropoff"].selected) || "адрес не выбран"}`);
            lines.push(`${formatDate(whenValue("return-date"))} ${whenValue("return-time")}`);

            if (state.returnPartyCustom) {
                lines.push(`Пассажиры обратно: ${passengerSummary("return")}`);
                lines.push(`Багаж обратно: ${luggageSummary("return")}`);
            }
        }

        node.textContent = lines.join("\n");
    }

    function updateStepLabel() {
        const label = root.querySelector("[data-step-label]");

        if (!label) {
            return;
        }

        const step = root.dataset.step;

        if (step === "done") {
            label.hidden = true;

            return;
        }

        label.hidden = false;
        label.textContent = step === "contact"
            ? "Шаг 2 из 2 · Контакты"
            : "Шаг 1 из 2 · Поездка";
    }

    function showStep(step) {
        root.dataset.step = step;
        const routePanel = root.querySelector('[data-step-panel="route"]');
        const contactPanel = root.querySelector('[data-step-panel="contact"]');

        if (routePanel) {
            routePanel.hidden = step !== "route";
        }

        if (contactPanel) {
            contactPanel.hidden = step !== "contact";
        }
        closePanels(false);
        hideAlert();
        updateStepLabel();

        if (step === "contact") {
            updateFlightFields();
            updateContactLabel();
            updateReview();
            root.querySelector('[data-contact="name"]')?.focus();
        }
    }

    function typedPlace(key) {
        return String(places[key].query || inputFor(key)?.value || "").trim();
    }

    function placeProblem(key) {
        const selected = places[key].selected;

        if (selected?.place_id && typeof selected.latitude === "number" && typeof selected.longitude === "number") {
            return "";
        }

        if (typedPlace(key).length >= 2) {
            return "";
        }

        return "Укажите адрес.";
    }

    function placesMatch(leftKey, rightKey) {
        const leftId = places[leftKey].selected?.place_id || "";
        const rightId = places[rightKey].selected?.place_id || "";

        if (leftId && leftId === rightId) {
            return true;
        }

        const leftText = typedPlace(leftKey).toLowerCase();
        const rightText = typedPlace(rightKey).toLowerCase();

        return leftText.length >= 2 && leftText === rightText;
    }

    function validateRoute() {
        const errors = {};

        ["outbound-pickup", "outbound-dropoff"].forEach((key) => {
            const problem = placeProblem(key);

            if (problem) {
                errors[key] = problem;
            }
        });

        if (placesMatch("outbound-pickup", "outbound-dropoff")) {
            errors["outbound-dropoff"] = "Места отправления и прибытия должны отличаться.";
        }

        const outboundDate = whenValue("outbound-date");
        const outboundTime = whenValue("outbound-time");
        const today = todayIso();

        if (!outboundDate) {
            errors["outbound-date"] = "Укажите дату поездки.";
        } else if (outboundDate < today) {
            errors["outbound-date"] = "Дата поездки не может быть в прошлом.";
        }

        if (!outboundTime) {
            errors["outbound-time"] = "Укажите время поездки.";
        }

        if (!state.passengersConfirmed.outbound) {
            errors["outbound-passengers"] = "Подтвердите пассажиров кнопкой «Готово».";
        } else if (peopleCount("outbound") < 1) {
            errors["outbound-passengers"] = "Укажите хотя бы одного пассажира.";
        } else if (missingAges("outbound")) {
            errors["outbound-passengers"] = "Укажите возраст каждого ребёнка и младенца.";
        }

        if (!state.luggageConfirmed.outbound) {
            errors["outbound-luggage"] = "Укажите багаж или подтвердите, что его нет.";
        }

        if (root.dataset.trip === "return") {
            if (!places["return-pickup"].dirty && !places["return-dropoff"].dirty) {
                mirrorRoutes();
            }

            ["return-pickup", "return-dropoff"].forEach((key) => {
                const problem = placeProblem(key);

                if (problem) {
                    errors[key] = problem;
                }
            });

            if (placesMatch("return-pickup", "return-dropoff")) {
                errors["return-dropoff"] = "Места обратного маршрута должны отличаться.";
            }

            const returnDate = whenValue("return-date");
            const returnTime = whenValue("return-time");

            if (!returnDate) {
                errors["return-date"] = "Укажите дату обратной поездки.";
            }

            if (!returnTime) {
                errors["return-time"] = "Укажите время обратной поездки.";
            }

            if (outboundDate && outboundTime && returnDate && returnTime && `${returnDate}T${returnTime}` <= `${outboundDate}T${outboundTime}`) {
                errors["return-date"] = "Обратная поездка должна быть позже первой. Сравниваются и дата, и время.";
            }

            if (state.returnPartyCustom) {
                if (!state.passengersConfirmed.return) {
                    errors["return-passengers"] = "Подтвердите пассажиров обратной поездки кнопкой «Готово».";
                } else if (peopleCount("return") < 1) {
                    errors["return-passengers"] = "Укажите хотя бы одного пассажира обратной поездки.";
                } else if (missingAges("return")) {
                    errors["return-passengers"] = "Укажите возраст каждого ребёнка и младенца обратной поездки.";
                }

                if (!state.luggageConfirmed.return) {
                    errors["return-luggage"] = "Укажите багаж обратной поездки или подтвердите, что его нет.";
                }
            }
        }

        const consent = root.querySelector('[name="personal_data_consent"]');

        if (consent && !consent.checked) {
            errors.consent = "Нужно отдельное согласие на обработку персональных данных.";
        }

        return errors;
    }

    function validateContact() {
        const errors = {};
        const name = contactValue("name").trim();
        const method = contactValue("method");
        const value = contactValue("value").trim();

        if (!name) {
            errors["contact-name"] = "Укажите имя.";
        }

        if (!value) {
            errors["contact-value"] = "Укажите контакт для связи.";
        } else if (method === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errors["contact-value"] = "Укажите email.";
        } else if ((method === "phone" || method === "whatsapp") && (value.replace(/\D/g, "").length < 10 || value.replace(/\D/g, "").length > 15)) {
            errors["contact-value"] = "Укажите телефон.";
        } else if (method === "telegram" && value.length < 3) {
            errors["contact-value"] = "Укажите Telegram.";
        }

        if (isAirport(places["outbound-pickup"].selected) && !contactValue("flight").trim()) {
            errors["contact-flight"] = "Укажите номер рейса: поездка начинается в аэропорту.";
        }

        if (root.dataset.trip === "return" && isAirport(places["return-pickup"].selected) && !contactValue("return-flight").trim()) {
            errors["contact-return-flight"] = "Укажите номер обратного рейса.";
        }

        return errors;
    }

    function optionalInt(value) {
        if (value == null || String(value).trim() === "") {
            return null;
        }

        return /^\d+$/.test(String(value)) ? Number(value) : null;
    }

    function partyPayload(group) {
        const party = counters[group];

        return {
            confirmed: state.passengersConfirmed[group] === true,
            adults: party.adults,
            children: party.childAges.map((age, index) => ({
                age_years: age,
                birth_date: party.childBirths[index] || null,
            })),
            infants: party.infantAges.map((age, index) => ({
                age_months: age,
                birth_date: party.infantBirths[index] || null,
            })),
            seats: {
                carrier: party.seatCarrier,
                child: party.seatChild,
                booster: party.seatBooster,
                own: party.seatOwn,
                height_cm: optionalInt(party.seatHeight),
                weight_kg: optionalInt(party.seatWeight),
            },
        };
    }

    function luggagePayload(group) {
        const party = counters[group];

        return {
            confirmed: state.luggageConfirmed[group] === true,
            suitcases: party.suitcases,
            suitcase_size: party.suitcaseSize || null,
            suitcase_dimensions: party.suitcaseDimensions,
            cabin: party.cabin,
            strollers: party.strollers,
            stroller_folds: party.strollerFolds || null,
            stroller_dimensions: party.strollerDimensions,
            sports: party.sports,
            sports_type: party.sportsType,
            sports_dimensions: party.sportsDimensions,
            wheelchairs: party.wheelchairs,
            wheelchair_folds: party.wheelchairFolds || null,
            wheelchair_ride: party.wheelchairs > 0 ? Boolean(party.wheelchairRide) : null,
            wheelchair_dimensions: party.wheelchairDimensions,
            other: party.other,
            other_description: party.otherDescription,
            other_dimensions: party.otherDimensions,
        };
    }

    function placePayload(key) {
        const selected = places[key].selected;
        const typed = typedPlace(key);

        if (selected?.place_id) {
            return {
                place_id: selected.place_id,
                formatted_address: selected.formatted_address || typed,
                display_name: selected.display_name || typed,
                latitude: selected.latitude,
                longitude: selected.longitude,
                primary_type: selected.primary_type,
                types: selected.types,
                country: selected.country,
                locality: selected.locality,
            };
        }

        if (typed.length < 2) {
            return null;
        }

        return {
            place_id: null,
            formatted_address: typed,
            display_name: typed,
            latitude: null,
            longitude: null,
            primary_type: null,
            types: [],
            country: null,
            locality: null,
        };
    }

    function snapshot() {
        if (root.dataset.trip === "return" && !places["return-pickup"].dirty && !places["return-dropoff"].dirty) {
            mirrorRoutes();
        }

        if (root.dataset.trip === "return" && !state.returnPartyCustom) {
            syncReturnParty();
        }

        const data = {
            trip: root.dataset.trip,
            outbound: {
                pickup: placePayload("outbound-pickup"),
                dropoff: placePayload("outbound-dropoff"),
                date: whenValue("outbound-date"),
                time: whenValue("outbound-time"),
                passengers: partyPayload("outbound"),
                luggage: luggagePayload("outbound"),
            },
            wishes: { ...wishes },
            personal_data_consent: root.querySelector('[name="personal_data_consent"]')?.checked === true,
            contact: {
                name: contactValue("name").trim(),
                method: contactValue("method"),
                value: contactValue("value").trim(),
                flight_number: isAirport(places["outbound-pickup"].selected) ? contactValue("flight").trim() : "",
                return_flight_number: root.dataset.trip === "return" && isAirport(places["return-pickup"].selected) ? contactValue("return-flight").trim() : "",
                comment: contactValue("comment").trim(),
            },
        };

        if (root.dataset.trip === "return") {
            data.return = {
                pickup: placePayload("return-pickup"),
                dropoff: placePayload("return-dropoff"),
                date: whenValue("return-date"),
                time: whenValue("return-time"),
                route_custom: Boolean(places["return-pickup"].dirty || places["return-dropoff"].dirty),
                party_custom: state.returnPartyCustom,
                passengers: partyPayload("return"),
                luggage: luggagePayload("return"),
            };
        }

        return data;
    }

    function fieldForServerKey(key) {
        const table = {
            "outbound.date": "outbound-date",
            "outbound.time": "outbound-time",
            "return.date": "return-date",
            "return.time": "return-time",
            "contact.name": "contact-name",
            "contact.value": "contact-value",
            "contact.method": "contact-value",
            "contact.flight_number": "contact-flight",
            "contact.return_flight_number": "contact-return-flight",
            "contact.comment": "contact-comment",
            "contact.personal_data_consent": "consent",
            personal_data_consent: "consent",
        };

        if (table[key]) {
            return table[key];
        }

        if (key.startsWith("outbound.passengers")) {
            return "outbound-passengers";
        }

        if (key.startsWith("outbound.luggage")) {
            return "outbound-luggage";
        }

        if (key.startsWith("return.passengers")) {
            return "return-passengers";
        }

        if (key.startsWith("return.luggage")) {
            return "return-luggage";
        }

        if (key.startsWith("return.pickup")) {
            return "return-pickup";
        }

        if (key.startsWith("return.dropoff")) {
            return "return-dropoff";
        }

        if (key.startsWith("outbound.pickup")) {
            return "outbound-pickup";
        }

        if (key.startsWith("outbound.dropoff")) {
            return "outbound-dropoff";
        }

        return null;
    }

    function mapServerErrors(errors) {
        const mapped = {};

        Object.entries(errors || {}).forEach(([key, messages]) => {
            const field = fieldForServerKey(key);
            const message = Array.isArray(messages) ? messages[0] : String(messages);

            if (field && !mapped[field]) {
                mapped[field] = message;
            }
        });

        return mapped;
    }

    async function sendEnquiry() {
        if (submitting) {
            return;
        }

        const contactErrors = validateContact();

        if (Object.keys(contactErrors).length) {
            applyErrors(contactErrors);

            return;
        }

        const routeErrors = validateRoute();

        if (Object.keys(routeErrors).length) {
            showStep("route");
            applyErrors(routeErrors);

            return;
        }

        submitting = true;
        const button = root.querySelector("[data-send]");

        if (button) {
            button.disabled = true;
            button.setAttribute("aria-busy", "true");
        }

        hideAlert();

        try {
            const response = await fetch(root.dataset.enquiryUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                },
                body: JSON.stringify(snapshot()),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || !payload.ok) {
                const mapped = mapServerErrors(payload.errors);

                if (Object.keys(mapped).some((key) => !key.startsWith("contact-"))) {
                    showStep("route");
                }

                if (Object.keys(mapped).length) {
                    applyErrors(mapped);
                }

                showAlert("error", payload.message || "Не удалось отправить заявку. Попробуйте ещё раз.");
                submitting = false;

                if (button) {
                    button.disabled = false;
                    button.removeAttribute("aria-busy");
                }

                return;
            }

            root.dataset.step = "done";
            const routePanel = root.querySelector('[data-step-panel="route"]');
            const contactPanel = root.querySelector('[data-step-panel="contact"]');

            if (routePanel) {
                routePanel.hidden = true;
            }

            if (contactPanel) {
                contactPanel.hidden = true;
            }
            updateStepLabel();
            const success = root.querySelector("[data-success]");
            success.hidden = false;
            success.querySelector("[data-success-text]").textContent = payload.message;
        } catch (error) {
            submitting = false;

            if (button) {
                button.disabled = false;
                button.removeAttribute("aria-busy");
            }

            showAlert("error", "Не удалось отправить заявку. Проверьте соединение и попробуйте ещё раз.");
        }
    }

    function storeDetail(input) {
        const group = input.closest("[data-counter-group]")?.dataset.counterGroup;

        if (!group) {
            return;
        }

        const key = input.dataset.detail;

        if (input.type === "checkbox") {
            counters[group][key] = input.checked;
        } else if (input.type === "radio") {
            if (!input.checked) {
                return;
            }

            counters[group][key] = input.value;
        } else {
            counters[group][key] = input.value;
        }

        if (key === "suitcaseSize") {
            paintExtras(group);
        }

        if (group === "outbound") {
            syncReturnParty();
        }
    }

    root.querySelectorAll("[data-place-input]").forEach((input) => {
        const key = input.dataset.placeInput;
        let composing = false;

        input.addEventListener("compositionstart", () => {
            composing = true;
        });
        input.addEventListener("compositionend", () => {
            composing = false;
            schedule(key);
        });
        input.addEventListener("input", () => {
            places[key].selected = null;
            places[key].dirty = true;
            places[key].query = input.value;
            syncClear(key);
            nextSeq(key);

            if (choosing.has(key)) {
                finishSession(key);
            }

            if (key.startsWith("outbound-")) {
                mirrorRoutes();
            }

            updateTimeLabels();
            refreshContinue();

            if (!composing) {
                schedule(key);
            }
        });
        input.addEventListener("keydown", (event) => {
            const list = listFor(key);

            if (event.key === "ArrowDown" && !list.hidden) {
                event.preventDefault();
                moveActive(key, 1);
            } else if (event.key === "ArrowUp" && !list.hidden) {
                event.preventDefault();
                moveActive(key, -1);
            } else if (event.key === "Enter" && !list.hidden && list.querySelector("[role='option']")) {
                event.preventDefault();
                const index = (activeIndex.get(key) ?? -1) >= 0 ? activeIndex.get(key) : 0;
                choose(key, index);
            } else if (event.key === "Escape") {
                hideList(key);
            }
        });
        input.addEventListener("focus", () => closePanels(false));
    });

    root.querySelectorAll('input[name="trip"]').forEach((input) => {
        input.addEventListener("change", () => {
            if (input.checked) {
                setTrip(input.value);
            }
        });
    });

    root.querySelector('[data-contact="method"]')?.addEventListener("change", updateContactLabel);

    root.addEventListener("click", (event) => {
        const stepButton = event.target.closest("[data-counter] [data-step]");

        if (stepButton && root.contains(stepButton)) {
            const stepper = stepButton.closest("[data-counter]");
            const groupName = stepButton.closest("[data-counter-group]")?.dataset.counterGroup;
            const name = stepper?.dataset.counter;
            const next = Number(counters[groupName][name]) + Number(stepButton.dataset.step);

            if (!name || next < 0 || next > MAX_COUNT) {
                return;
            }

            counters[groupName][name] = next;

            if (name === "children" || name === "infants") {
                fitAges(counters[groupName]);
                renderAges(groupName);
                state.passengersConfirmed[groupName] = false;
            }

            if (PEOPLE.includes(name)) {
                state.passengersConfirmed[groupName] = false;
            }

            if (LUGGAGE.includes(name)) {
                state.luggageConfirmed[groupName] = false;
            }

            renderCounter(stepper);
            paintExtras(groupName);
            updateSummaries();
            refreshAgeNote();

            if (groupName === "outbound") {
                syncReturnParty();
            }

            return;
        }

        if (event.target.closest("[data-panel-close]")) {
            closePanels(true);

            return;
        }

        const done = event.target.closest("[data-panel-done]");

        if (done) {
            const pop = done.closest("[data-pop]");
            const kind = done.dataset.panelDone;

            if (kind === "time") {
                if (commitTime(pop.dataset.whenKey)) {
                    closePanels(true);
                }

                return;
            }

            if (kind === "passengers") {
                const group = pop.dataset.counterGroup;

                if (peopleCount(group) < 1) {
                    showFieldError(`${group}-passengers`, "Укажите хотя бы одного пассажира.");

                    return;
                }

                if (missingAges(group)) {
                    showFieldError(`${group}-passengers`, "Укажите возраст каждого ребёнка и младенца.");

                    return;
                }

                state.passengersConfirmed[group] = true;
                clearFieldError(`${group}-passengers`);
                updateSummaries();

                if (group === "outbound") {
                    syncReturnParty();
                }

                closePanels(true);

                return;
            }

            if (kind === "luggage") {
                const group = pop.dataset.counterGroup;
                state.luggageConfirmed[group] = true;
                clearFieldError(`${group}-luggage`);
                updateSummaries();

                if (group === "outbound") {
                    syncReturnParty();
                }

                closePanels(true);

                return;
            }

            updateWishesLabel();
            closePanels(true);

            return;
        }

        const day = event.target.closest("[data-date]");

        if (day && !day.disabled) {
            setWhen(day.dataset.dateFor, day.dataset.date);
            clearFieldError(day.dataset.dateFor);
            refreshAgeNote();
            closePanels(true);

            return;
        }

        const cal = event.target.closest("[data-cal]");

        if (cal && !cal.disabled) {
            shiftMonth(cal.dataset.calFor, cal.dataset.cal === "next" ? 1 : -1);

            return;
        }

        const unit = event.target.closest("[data-time-unit]");

        if (unit) {
            const cols = unit.closest("[data-time-cols]");
            const kind = unit.dataset.timeUnit;
            cols.querySelectorAll(`[data-time-unit="${kind}"]`).forEach((button) => {
                button.setAttribute("aria-selected", button === unit ? "true" : "false");
            });

            if (kind === "hour") {
                cols.dataset.pendingHour = unit.dataset.value;
            } else {
                cols.dataset.pendingMinute = unit.dataset.value;
            }

            const which = cols.dataset.timeCols;

            if (cols.dataset.pendingHour !== "" && cols.dataset.pendingMinute !== "" && commitTime(which)) {
                closePanels(true);
            }

            return;
        }

        const seatsToggle = event.target.closest("[data-seats-toggle]");

        if (seatsToggle) {
            const panel = document.getElementById(seatsToggle.getAttribute("aria-controls"));
            const willOpen = panel.hidden;
            panel.hidden = !willOpen;
            seatsToggle.setAttribute("aria-expanded", willOpen ? "true" : "false");

            return;
        }

        if (event.target.closest("[data-edit-return-route]")) {
            enableReturnRoute();

            return;
        }

        if (event.target.closest("[data-edit-return-party]")) {
            enableReturnParty();

            return;
        }

        if (event.target.closest("[data-swap-places]")) {
            swapPlaces();

            return;
        }

        const clear = event.target.closest("[data-clear-place]");

        if (clear) {
            clearPlace(clear.dataset.clearPlace);

            return;
        }

        if (event.target.closest("[data-back]")) {
            showStep("route");

            return;
        }

        const trigger = event.target.closest(".transfer-search__pop-trigger");

        if (trigger && root.contains(trigger)) {
            const pop = trigger.closest("[data-pop]");
            const panel = pop.querySelector(".transfer-search__pop-panel");
            const wasOpen = panel && !panel.hidden;
            closePanels(false);

            if (!wasOpen) {
                openPanel(pop);
            }
        }
    });

    root.addEventListener("change", (event) => {
        const age = event.target.closest("[data-age]");

        if (age) {
            const group = age.closest("[data-counter-group]").dataset.counterGroup;
            const index = Number(age.dataset.index);
            const list = age.dataset.age === "child" ? "childAges" : "infantAges";
            counters[group][list][index] = age.value === "" ? null : Number(age.value);

            if (group === "outbound") {
                syncReturnParty();
            }

            return;
        }

        const birth = event.target.closest("[data-birth]");

        if (birth) {
            const group = birth.closest("[data-counter-group]").dataset.counterGroup;
            const index = Number(birth.dataset.index);
            const list = birth.dataset.birth === "child" ? "childBirths" : "infantBirths";
            counters[group][list][index] = birth.value;
            refreshAgeNote();

            if (group === "outbound") {
                syncReturnParty();
            }

            return;
        }

        const detail = event.target.closest("[data-detail]");

        if (detail) {
            storeDetail(detail);
        }

        const wish = event.target.closest("[data-wish]");

        if (wish) {
            wishes[wish.dataset.wish] = wish.checked;
            const extra = root.querySelector(`[data-wish-detail="${wish.dataset.wish}"]`);

            if (extra) {
                extra.hidden = !wish.checked;
            }

            updateWishesLabel();
        }
    });

    root.addEventListener("input", (event) => {
        const detail = event.target.closest("[data-detail]");

        if (detail && detail.type !== "radio" && detail.type !== "checkbox") {
            storeDetail(detail);
        }

        const wishText = event.target.closest("[data-wish-text]");

        if (wishText) {
            wishes[wishText.dataset.wishText] = wishText.value;
        }
    });

    document.addEventListener("pointerdown", (event) => {
        if (event.target.closest("[data-backdrop]")) {
            closePanels(false);

            return;
        }

        if (!root.contains(event.target)) {
            closePanels(false);
            Object.keys(places).forEach(hideList);

            return;
        }

        if (!event.target.closest("[data-pop]")) {
            closePanels(false);
        }

        const field = event.target.closest("[data-place-field]");
        Object.keys(places).forEach((key) => {
            if (!field || field.dataset.placeField !== key) {
                hideList(key);
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closePanels(true);
            Object.keys(places).forEach(hideList);
        }
    });

    function routeGateReady() {
        if (placeProblem("outbound-pickup") || placeProblem("outbound-dropoff")) {
            return false;
        }

        if (placesMatch("outbound-pickup", "outbound-dropoff")) {
            return false;
        }

        const outboundDate = whenValue("outbound-date");

        if (!outboundDate || outboundDate < todayIso()) {
            return false;
        }

        if (root.dataset.trip === "return") {
            const returnDate = whenValue("return-date");

            if (!returnDate || returnDate < outboundDate) {
                return false;
            }
        }

        return true;
    }

    function refreshContinue() {
        const button = root.querySelector("[data-continue]");

        if (!button || submitting) {
            return;
        }

        button.disabled = false;
        button.removeAttribute("aria-disabled");
    }

    function applyPlace(key, place, dirty) {
        if (!place || typeof place !== "object") {
            return;
        }

        const address = String(place.formatted_address || place.display_name || "").trim();
        const id = String(place.place_id || "").trim();

        if (!id && address.length < 2) {
            return;
        }

        if (id) {
            const latitude = Number(place.latitude);
            const longitude = Number(place.longitude);
            places[key].selected = {
                place_id: id,
                formatted_address: address,
                display_name: place.display_name || address,
                latitude: Number.isFinite(latitude) ? latitude : null,
                longitude: Number.isFinite(longitude) ? longitude : null,
                primary_type: place.primary_type || null,
                types: Array.isArray(place.types) ? place.types : null,
                country: place.country || null,
                locality: place.locality || null,
            };
            places[key].query = labelOf(places[key].selected) || address;
        } else {
            places[key].selected = null;
            places[key].query = address;
        }

        places[key].dirty = Boolean(dirty);
        const input = inputFor(key);

        if (input) {
            input.value = places[key].query;
        }

        syncClear(key);
    }

    function applyParty(group, passengers, luggage) {
        const party = blankParty();
        const people = passengers && typeof passengers === "object" ? passengers : {};
        const bags = luggage && typeof luggage === "object" ? luggage : {};
        const children = Array.isArray(people.children) ? people.children : [];
        const infants = Array.isArray(people.infants) ? people.infants : [];
        const seats = people.seats && typeof people.seats === "object" ? people.seats : {};
        party.adults = Number(people.adults || 0);
        party.children = children.length;
        party.infants = infants.length;
        party.childAges = children.map((item) => (item && item.age_years != null && item.age_years !== "" ? Number(item.age_years) : null));
        party.childBirths = children.map((item) => (item && item.birth_date) || "");
        party.infantAges = infants.map((item) => (item && item.age_months != null && item.age_months !== "" ? Number(item.age_months) : null));
        party.infantBirths = infants.map((item) => (item && item.birth_date) || "");
        party.seatCarrier = Number(seats.carrier || 0);
        party.seatChild = Number(seats.child || 0);
        party.seatBooster = Number(seats.booster || 0);
        party.seatOwn = Number(seats.own || 0);
        party.seatHeight = seats.height_cm == null ? "" : String(seats.height_cm);
        party.seatWeight = seats.weight_kg == null ? "" : String(seats.weight_kg);
        party.suitcases = Number(bags.suitcases || 0);
        party.cabin = Number(bags.cabin || 0);
        party.strollers = Number(bags.strollers || 0);
        party.sports = Number(bags.sports || 0);
        party.wheelchairs = Number(bags.wheelchairs || 0);
        party.other = Number(bags.other || 0);
        party.suitcaseSize = bags.suitcase_size || "";
        party.suitcaseDimensions = bags.suitcase_dimensions || "";
        party.strollerFolds = bags.stroller_folds || "";
        party.strollerDimensions = bags.stroller_dimensions || "";
        party.sportsType = bags.sports_type || "";
        party.sportsDimensions = bags.sports_dimensions || "";
        party.wheelchairFolds = bags.wheelchair_folds || "";
        party.wheelchairRide = Boolean(bags.wheelchair_ride);
        party.wheelchairDimensions = bags.wheelchair_dimensions || "";
        party.otherDescription = bags.other_description || "";
        party.otherDimensions = bags.other_dimensions || "";
        counters[group] = party;
        state.passengersConfirmed[group] = people.confirmed === true || people.confirmed === 1 || people.confirmed === "1";
        state.luggageConfirmed[group] = bags.confirmed === true || bags.confirmed === 1 || bags.confirmed === "1";
        paintGroup(group);
    }

    function restoreDraft(draft) {
        if (!draft || typeof draft !== "object" || !draft.outbound) {
            return false;
        }

        applyPlace("outbound-pickup", draft.outbound.pickup, false);
        applyPlace("outbound-dropoff", draft.outbound.dropoff, false);
        applyParty("outbound", draft.outbound.passengers, draft.outbound.luggage);
        setTrip(draft.trip === "oneway" ? "oneway" : "return");
        setWhen("outbound-date", draft.outbound.date || "");
        setWhen("outbound-time", draft.outbound.time || "");

        if (draft.trip !== "oneway" && draft.return) {
            setWhen("return-date", draft.return.date || "");
            setWhen("return-time", draft.return.time || "");

            if (draft.return.route_custom) {
                applyPlace("return-pickup", draft.return.pickup, true);
                applyPlace("return-dropoff", draft.return.dropoff, true);
                enableReturnRoute();
            }

            if (draft.return.party_custom) {
                enableReturnParty();
                applyParty("return", draft.return.passengers, draft.return.luggage);
            }
        }

        const savedWishes = draft.wishes || {};
        ["pet", "boarding_help", "extra_stop", "other"].forEach((key) => {
            wishes[key] = Boolean(savedWishes[key]);
            const box = root.querySelector(`[data-wish="${key}"]`);

            if (box) {
                box.checked = wishes[key];
            }

            const extra = root.querySelector(`[data-wish-detail="${key}"]`);

            if (extra) {
                extra.hidden = !wishes[key];
            }

            const detailKey = `${key}_details`;
            wishes[detailKey] = savedWishes[detailKey] || "";
            const text = root.querySelector(`[data-wish-text="${detailKey}"]`);

            if (text) {
                text.value = wishes[detailKey];
            }
        });
        updateWishesLabel();
        updateTimeLabels();
        updateReturnRouteSummary();
        updateSummaries();

        const consent = root.querySelector('[name="personal_data_consent"]');

        if (consent) {
            consent.checked = draft.personal_data_consent === true
                || draft.personal_data_consent === 1
                || draft.personal_data_consent === "1";
        }

        refreshContinue();

        return true;
    }

    async function continueToRequest() {
        if (submitting) {
            return;
        }

        const errors = validateRoute();

        if (Object.keys(errors).length) {
            applyErrors(errors);

            return;
        }

        if (!root.dataset.continueUrl) {
            showAlert("error", "Не удалось перейти к оформлению. Обновите страницу и попробуйте ещё раз.");

            return;
        }

        const button = root.querySelector("[data-continue]");
        const data = snapshot();
        delete data.contact;
        submitting = true;

        if (button) {
            button.disabled = true;
            button.setAttribute("aria-busy", "true");
        }

        hideAlert();

        try {
            const response = await fetch(root.dataset.continueUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                },
                body: JSON.stringify(data),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || !payload.ok || !payload.redirect) {
                const mapped = mapServerErrors(payload.errors);

                if (Object.keys(mapped).length) {
                    applyErrors(mapped);
                }

                showAlert("error", payload.message || "Не удалось перейти к оформлению. Проверьте данные и попробуйте ещё раз.");
                submitting = false;

                if (button) {
                    button.removeAttribute("aria-busy");
                }

                refreshContinue();

                return;
            }

            window.location.assign(payload.redirect);
        } catch (error) {
            submitting = false;

            if (button) {
                button.removeAttribute("aria-busy");
            }

            refreshContinue();
            showAlert("error", "Не удалось перейти к оформлению. Проверьте соединение и попробуйте ещё раз.");
        }
    }

    root.addEventListener("submit", (event) => {
        event.preventDefault();

        if (root.dataset.step === "done" || submitting) {
            return;
        }

        clearErrors();

        if (root.dataset.step === "contact") {
            sendEnquiry();

            return;
        }

        continueToRequest();
    });

    window.gm_authFailure = () => {
        placesFailed = true;
    };

    const draftNode = document.getElementById("transfer-search-draft");
    let restored = false;

    if (draftNode) {
        try {
            restored = restoreDraft(JSON.parse(draftNode.textContent || "null")) === true;
        } catch (error) {
            restored = false;
        }
    }

    if (!restored) {
        paintGroup("outbound");
        paintGroup("return");
        updateSummaries();
        updateTimeLabels();
        updateReturnRouteSummary();
        updateContactLabel();
        setTrip("return");
    }

    updateStepLabel();
    refreshContinue();
})();
