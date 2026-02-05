import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import csLocale from "@fullcalendar/core/locales/cs";

import tippy from "tippy.js";
import "tippy.js/dist/tippy.css";
import "./bootstrap";

console.log("app.js loaded");
window.__APP_JS_LOADED__ = true;

document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById("calendar");
    if (!el) return;

    console.log("DOMContentLoaded, calendar el:", el);

    const dialog = document.getElementById("attendanceDialog");
    const form = document.getElementById("attendanceForm");
    const cancelBtn = document.getElementById("cancelDialog");

    // 🔎 filtr (select nahoře)
    // POZOR: v Blade musíš mít select s id="team_member_filter"
    const memberFilter = document.getElementById("team_member_filter");

    // ✅ zapisovaná hodnota do formu (modal)
    const teamMemberHidden = document.getElementById("team_member_id_hidden");

    const attendanceIdInput = document.getElementById("attendance_id");
    const methodInput = document.getElementById("_method");
    const deleteBtn = document.getElementById("deleteAttendance");
    const dialogHint = document.getElementById("dialogHint");

    const from = document.getElementById("from_date");
    const to = document.getElementById("to_date");
    const fromView = document.getElementById("from_date_view");
    const toView = document.getElementById("to_date_view");
    const activity = document.getElementById("activity");
    const note = document.getElementById("note");

    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    // XSS-safe escape pro data z DB (tooltipy, HTML šablony apod.)
    const esc = (s = "") =>
        String(s).replace(/[&<>"']/g, (c) =>
            ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;",
            })[c],
        );

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

    function selectedMemberId() {
        return memberFilter?.value ? String(memberFilter.value) : "";
    }

    function mustHaveMemberForWrite() {
        if (!selectedMemberId()) {
            alert("Nejdřív vyber člena týmu (pro zápis).");
            return false;
        }

        if (teamMemberHidden) teamMemberHidden.value = selectedMemberId();
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

        if (!preset?.skipMemberCheck) {
            if (!mustHaveMemberForWrite()) return;
        }

        if (from) from.value = fromDate;
        if (to) to.value = toDate;

        if (fromView) fromView.value = fromDate;
        if (toView) toView.value = toDate;

        if (activity) activity.value = preset.activity ?? "";
        if (note) note.value = preset.note ?? "";

        dialog.showModal();
    }

    function closeDialog() {
        dialog?.close();
    }

    cancelBtn?.addEventListener("click", () => closeDialog());

    fromView?.addEventListener("change", () => {
        if (from) from.value = fromView.value;
    });
    toView?.addEventListener("change", () => {
        if (to) to.value = toView.value;
    });

    function dateFromYmd(ymd) {
        const [y, m, d] = String(ymd)
            .split("-")
            .map((n) => parseInt(n, 10));
        return new Date(y, m - 1, d);
    }

    function ymdFromDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const d = String(date.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
    }

    function inclusiveToDateFromEventEnd(exclusiveEnd) {
        const d = dateFromYmd(exclusiveEnd);
        d.setDate(d.getDate() - 1);
        return ymdFromDate(d);
    }

    function attendanceItemUrl(id) {
        return `/attendance/${id}`;
    }

    console.log("creating calendar");

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: "dayGridMonth",
        locale: csLocale,
        firstDay: 1,
        height: "auto",
        timeZone: "Europe/Prague",

        selectable: true,
        selectMirror: true,
        dayMaxEventRows: 3,
        moreLinkClick: "popover",

        dateClick: (info) => {
            setCreateMode();
            openDialog(info.dateStr, info.dateStr);
        },

        select: (info) => {
            setCreateMode();

            const end = new Date(info.end);
            end.setDate(end.getDate() - 1);
            const toDate = ymdFromDate(end);

            openDialog(info.startStr.slice(0, 10), toDate);
        },

        eventClick: (arg) => {
            if (arg.event.display === "background") return;

            const memberId = arg.event.extendedProps?.memberId;
            if (!memberId) return;

            setEditMode(arg.event.id);

            // filtr nastavíme, aby UI sedělo s editovaným záznamem
            if (memberFilter) memberFilter.value = String(memberId);

            // do formu posíláme team_member_id
            if (teamMemberHidden) teamMemberHidden.value = String(memberId);

            const startDate = arg.event.startStr?.slice(0, 10);
            const endExclusive = arg.event.endStr
                ? arg.event.endStr.slice(0, 10)
                : startDate;

            const endDate = arg.event.endStr
                ? inclusiveToDateFromEventEnd(endExclusive)
                : startDate;

            openDialog(startDate, endDate, {
                activity: arg.event.title || "",
                note: arg.event.extendedProps?.note || "",
                skipMemberCheck: true,
            });
        },

        eventSources: [
            {
                url: "/attendance/events",
                extraParams: () => ({
                    member_id: selectedMemberId(),
                }),
                success: () => console.log("attendance loaded"),
                failure: (err) => console.error("attendance failed", err),
            },
            {
                url: "/holidays/cz",
                success: () => console.log("holidays loaded"),
                failure: (err) => console.error("holidays failed", err),
            },
        ],

        eventContent: (arg) => {
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
            if (info.event.display === "background") return;

            const memberId = info.event.extendedProps?.memberId;

            if (memberId) {
                const c = colorForMember(memberId);
                info.el.style.backgroundColor = c;
                info.el.style.borderColor = c;
                info.el.style.color = "white";
                info.el.style.cursor = "pointer";
            }

            const name = info.event.extendedProps?.memberName || "";
            const noteText = info.event.extendedProps?.note || "";

            if (!memberId) return;

            const safeName = esc(name);
            const safeTitle = esc(info.event.title || "");
            const safeNote = esc(noteText).replace(/\n/g, "<br>");

            info.el.title = name
                ? `${name}: ${info.event.title || ""}`
                : info.event.title || "";

            if (info.el._tippy) info.el._tippy.destroy();

            tippy(info.el, {
                allowHTML: true,
                placement: "top",
                interactive: false,
                theme: "light",
                content: `
                  <div style="font-weight:700;margin-bottom:4px;">
                    ${safeName}
                  </div>
                  <div style="font-weight:600;">
                    ${safeTitle}
                  </div>
                  ${
                      safeNote
                          ? `<div style="margin-top:6px;font-size:12px;opacity:.9;">
                               ${safeNote}
                             </div>`
                          : ""
                  }
                `,
            });
        },
    });

    calendar.render();
    console.log("calendar rendered");

    memberFilter?.addEventListener("change", () => {
        calendar.refetchEvents();
    });

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

    form?.addEventListener("submit", async (e) => {
        e.preventDefault();

        if (!csrf) {
            alert("Chybí CSRF token (meta csrf-token).");
            return;
        }

        const edit = isEditMode();
        const id = attendanceIdInput?.value;

        if (!edit) {
            if (!mustHaveMemberForWrite()) return;
            if (teamMemberHidden) teamMemberHidden.value = selectedMemberId();
            setCreateMode();
        } else {
            const memberId = selectedMemberId();
            if (!memberId) {
                alert("Pro editaci musíš mít vybraného člena.");
                return;
            }
            if (teamMemberHidden) teamMemberHidden.value = memberId;
            setEditMode(id);
        }

        const formData = new FormData(form);
        formData.set("_method", edit ? "PATCH" : "POST");

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
                alert("Nepodařilo se uložit. Mrkni do konzole.");
                return;
            }

            closeDialog();
            calendar.refetchEvents();
        } catch (err) {
            console.error(err);
            alert("Chyba při ukládání. Mrkni do konzole.");
        }
    });

    dialog?.addEventListener("close", () => {
        setCreateMode();
        if (note) note.value = "";
    });
});
