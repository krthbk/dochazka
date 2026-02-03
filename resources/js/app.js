import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import csLocale from "@fullcalendar/core/locales/cs";

import "./bootstrap";

document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById("calendar");
    if (!el) return;

    const dialog = document.getElementById("attendanceDialog");
    const form = document.getElementById("attendanceForm");
    const cancelBtn = document.getElementById("cancelDialog");

    const userSelect = document.getElementById("user_id");
    const userHidden = document.getElementById("user_id_hidden");

    const attendanceIdInput = document.getElementById("attendance_id");
    const methodInput = document.getElementById("_method");
    const deleteBtn = document.getElementById("deleteAttendance");
    const dialogHint = document.getElementById("dialogHint");

    const from = document.getElementById("from_date");
    const to = document.getElementById("to_date");
    const fromView = document.getElementById("from_date_view");
    const toView = document.getElementById("to_date_view");
    const activity = document.getElementById("activity");

    // CSRF token z meta tagu (Laravel)
    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    // paleta barev pro členy týmu
    const palette = [
        "#1d4ed8",
        "#16a34a",
        "#dc2626",
        "#7c3aed",
        "#ea580c",
        "#0891b2",
        "#be185d",
        "#65a30d",
        "#0f172a",
    ];

    function colorForMember(memberId) {
        const idx = Math.abs(parseInt(memberId, 10)) % palette.length;
        return palette[idx];
    }

    // 👉 vybraný člen pro filtr (prázdné = všichni)
    function selectedMemberId() {
        return userSelect?.value ? String(userSelect.value) : "";
    }

    // ✅ pro vytváření musí být vybraný člen
    function mustHaveMemberForWrite() {
        if (!selectedMemberId()) {
            alert("Nejdřív vyber člena týmu (pro zápis).");
            return false;
        }
        if (userHidden) userHidden.value = selectedMemberId();
        return true;
    }

    function isEditMode() {
        return !!attendanceIdInput?.value;
    }

    function setCreateMode() {
        if (attendanceIdInput) attendanceIdInput.value = "";
        if (methodInput) methodInput.value = "POST";
        deleteBtn?.classList.add("hidden");
        if (dialogHint) {
            dialogHint.textContent =
                "Vytváříš nový záznam. Vyplň aktivitu a ulož.";
        }
    }

    function setEditMode(id) {
        if (attendanceIdInput) attendanceIdInput.value = String(id);
        if (methodInput) methodInput.value = "PATCH";
        deleteBtn?.classList.remove("hidden");
        if (dialogHint) {
            dialogHint.textContent =
                "Upravuješ existující záznam. Ulož změny nebo smaž.";
        }
    }

    function openDialog(fromDate, toDate, preset = {}) {
        if (!dialog) return;

        // create mód -> musí být vybraný člen
        if (!preset?.skipMemberCheck) {
            if (!mustHaveMemberForWrite()) return;
        }

        if (from) from.value = fromDate;
        if (to) to.value = toDate;

        if (fromView) fromView.value = fromDate;
        if (toView) toView.value = toDate;

        if (activity) activity.value = preset.activity ?? "";

        dialog.showModal();
    }

    function closeDialog() {
        dialog?.close();
    }

    // zavření dialogu
    cancelBtn?.addEventListener("click", () => closeDialog());

    // ruční změna dat v dialogu -> sync do hidden inputů
    fromView?.addEventListener("change", () => {
        if (from) from.value = fromView.value;
    });
    toView?.addEventListener("change", () => {
        if (to) to.value = toView.value;
    });

    // Helper: FullCalendar posílá end jako exclusive, my chceme inclusive v inputu
    function inclusiveToDateFromEventEnd(exclusiveEnd) {
        // exclusiveEnd je string YYYY-MM-DD
        const d = new Date(exclusiveEnd);
        d.setDate(d.getDate() - 1);
        return d.toISOString().slice(0, 10);
    }

    // ✅ URL pro update/delete
    function attendanceItemUrl(id) {
        return `/attendance/${id}`;
    }

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: "dayGridMonth",
        locale: csLocale,
        firstDay: 1,
        height: "auto",

        selectable: true,
        selectMirror: true,
        dayMaxEventRows: 3, // ✅ ukáže max 3 záznamy
        moreLinkClick: "popover", // ✅ zbytek se otevře v popoveru
        // klik na den => CREATE
        dateClick: (info) => {
            setCreateMode();
            openDialog(info.dateStr, info.dateStr);
        },

        // výběr rozsahu => CREATE
        select: (info) => {
            setCreateMode();

            // end je exclusive -> -1 den
            const end = new Date(info.end);
            end.setDate(end.getDate() - 1);
            const toDate = end.toISOString().slice(0, 10);

            openDialog(info.startStr.slice(0, 10), toDate);
        },

        // ✅ klik na existující event => EDIT (ignoruj svátky)
        eventClick: (arg) => {
            // svátky / background eventy nechceme editovat
            if (arg.event.display === "background") return;

            const memberId = arg.event.extendedProps?.memberId;
            if (!memberId) return; // např. holiday label event

            // přepni do edit módu
            setEditMode(arg.event.id);

            // nastav select na člena (ať to sedí vizuálně)
            if (userSelect) userSelect.value = String(memberId);
            if (userHidden) userHidden.value = String(memberId);

            const startDate = arg.event.startStr?.slice(0, 10);
            // end je exclusive -> převedeme na inclusive
            const endExclusive = arg.event.endStr
                ? arg.event.endStr.slice(0, 10)
                : startDate;
            const endDate = arg.event.endStr
                ? inclusiveToDateFromEventEnd(endExclusive)
                : startDate;

            openDialog(startDate, endDate, {
                activity: arg.event.title || "",
                skipMemberCheck: true, // editace nevyžaduje "vybraného člena" pro zápis
            });
        },

        eventSources: [
            {
                // ✅ docházka – umí filtr podle člena
                url: "/attendance/events",
                extraParams: () => ({
                    member_id: selectedMemberId(), // "" => všichni
                }),
            },
            {
                // svátky
                url: "/holidays/cz",
            },
        ],

        // ✅ VARIANTA B: jméno + aktivita v buňce
        eventContent: (arg) => {
            // background eventy (svátky) neřeš
            if (arg.event.display === "background") return;

            const name = arg.event.extendedProps?.memberName || "";
            const activityText = arg.event.title || "";

            const wrap = document.createElement("div");
            wrap.style.whiteSpace = "normal";
            wrap.style.lineHeight = "1.1";
            wrap.style.textAlign = "center";

            if (name) {
                const line1 = document.createElement("div");
                line1.style.fontWeight = "700";
                line1.style.fontSize = "0.7rem";
                line1.textContent = name;
                wrap.appendChild(line1);
            }

            const line2 = document.createElement("div");
            line2.style.fontWeight = "600";
            line2.style.fontSize = "0.7rem";
            line2.textContent = activityText;
            wrap.appendChild(line2);

            return { domNodes: [wrap] };
        },

        eventDidMount: (info) => {
            const memberId = info.event.extendedProps?.memberId;
            if (!memberId) return; // svátky a jiné eventy

            const c = colorForMember(memberId);
            info.el.style.backgroundColor = c;
            info.el.style.borderColor = c;
            info.el.style.color = "white";

            // tooltip title (hover)
            const name = info.event.extendedProps?.memberName || "";
            info.el.title = name
                ? `${name}: ${info.event.title}`
                : info.event.title;

            // cursor, ať je jasný, že jde kliknout/editovat
            info.el.style.cursor = "pointer";
        },
    });

    calendar.render();

    // ✅ filtr: změna selectu -> refetch eventů
    userSelect?.addEventListener("change", () => {
        calendar.refetchEvents();
    });

    // ✅ mazání záznamu (jen edit mód)
    deleteBtn?.addEventListener("click", async () => {
        const id = attendanceIdInput?.value;
        if (!id) return;

        const ok = confirm("Fakt to chceš smazat? (Už to nepůjde vrátit)");
        if (!ok) return;

        if (!csrf) {
            alert("Chybí CSRF token (meta csrf-token).");
            return;
        }

        try {
            const res = await fetch(attendanceItemUrl(id), {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrf,
                    Accept: "application/json",
                },
            });

            if (!res.ok) {
                const text = await res.text();
                console.error(text);
                alert("Smazání se nepovedlo. Mrkni do konzole.");
                return;
            }

            closeDialog();
            calendar.refetchEvents();
        } catch (err) {
            console.error(err);
            alert("Chyba při mazání (network/JS). Mrkni do konzole.");
        }
    });

    // ✅ submit bez reloadu + refetch eventů (CREATE i EDIT)
    form?.addEventListener("submit", async (e) => {
        e.preventDefault();

        if (!csrf) {
            alert("Chybí CSRF token (meta csrf-token).");
            return;
        }

        const edit = isEditMode();
        const id = attendanceIdInput?.value;

        // CREATE: musí být vybraný člen
        if (!edit) {
            if (!mustHaveMemberForWrite()) return;
            if (userHidden) userHidden.value = selectedMemberId();
            setCreateMode(); // jistota
        } else {
            // EDIT: vezmi člena ze selectu (nebo z hidden), musí existovat
            const memberId = selectedMemberId();
            if (!memberId) {
                alert(
                    "Pro editaci musíš mít vybraného člena (aspoň toho původního).",
                );
                return;
            }
            if (userHidden) userHidden.value = memberId;
            setEditMode(id);
        }

        const formData = new FormData(form);

        // Laravel: když posíláš fetch, nejjednodušší je vždy POST a pro update přidat _method=PATCH
        // (proto máme hidden #_method)
        if (edit) {
            formData.set("_method", "PATCH");
        } else {
            formData.set("_method", "POST");
        }

        const url = edit ? attendanceItemUrl(id) : form.getAttribute("action");

        try {
            const res = await fetch(url, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrf,
                    Accept: "application/json",
                },
                body: formData,
            });

            if (!res.ok) {
                const text = await res.text();
                console.error(text);
                alert("Nepodařilo se uložit. Mrkni do konzole (Console).");
                return;
            }

            closeDialog();
            calendar.refetchEvents();
        } catch (err) {
            console.error(err);
            alert("Chyba při ukládání (network/JS). Mrkni do konzole.");
        }
    });

    // když dialog zavřeš, další otevření ať defaultne na CREATE
    dialog?.addEventListener("close", () => {
        setCreateMode();
    });
});
