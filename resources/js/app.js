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

  function mustHaveMember() {
    if (!userSelect?.value) {
      alert("Nejdřív vyber člena týmu.");
      return false;
    }
    if (userHidden) userHidden.value = userSelect.value;
    return true;
  }

  function openDialog(fromDate, toDate) {
    if (!dialog) return;
    if (!mustHaveMember()) return;

    if (from) from.value = fromDate;
    if (to) to.value = toDate;

    if (fromView) fromView.value = fromDate;
    if (toView) toView.value = toDate;

    if (activity) activity.value = "";

    dialog.showModal();
  }

  // zavření dialogu
  cancelBtn?.addEventListener("click", () => dialog?.close());

  // ruční změna dat v dialogu -> sync do hidden inputů
  fromView?.addEventListener("change", () => {
    if (from) from.value = fromView.value;
  });
  toView?.addEventListener("change", () => {
    if (to) to.value = toView.value;
  });

  const calendar = new Calendar(el, {
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    locale: csLocale,
    firstDay: 1,
    height: "auto",

    selectable: true,
    selectMirror: true,

    dateClick: (info) => {
      openDialog(info.dateStr, info.dateStr);
    },

    select: (info) => {
      // end je exclusive -> -1 den
      const end = new Date(info.end);
      end.setDate(end.getDate() - 1);
      const toDate = end.toISOString().slice(0, 10);
      openDialog(info.startStr.slice(0, 10), toDate);
    },

    eventSources: [
      { url: "/attendance/events" }, // ✅ všichni uživatelé
      { url: "/holidays/cz" }, // svátky
    ],

    // ✅ VARIANTA B: vlastní vykreslení textu (jméno nahoře, aktivita pod tím)
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
      info.el.title = name ? `${name}: ${info.event.title}` : info.event.title;
    },
  });

  calendar.render();

  // ✅ submit bez reloadu + refetch eventů
  form?.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (!mustHaveMember()) return;
    if (!csrf) {
      alert("Chybí CSRF token (meta csrf-token).");
      return;
    }

    const formData = new FormData(form);

    try {
      const res = await fetch(form.getAttribute("action"), {
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

      dialog?.close();
      calendar.refetchEvents();
    } catch (err) {
      console.error(err);
      alert("Chyba při ukládání (network/JS). Mrkni do konzole.");
    }
  });
});
