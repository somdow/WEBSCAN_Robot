import "./bootstrap";

import Alpine from "alpinejs";
import collapse from "@alpinejs/collapse";
import { Chart, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Filler } from "chart.js";

Chart.register(LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Filler);
window.Chart = Chart;

Alpine.plugin(collapse);

/**
 * Global toast notification store.
 * Manages a stack of toast messages with auto-dismiss.
 */
Alpine.store("toasts", {
	items: [],
	counter: 0,

	add(message, type = "success", duration = 4000) {
		const validTypes = ["success", "error", "warning", "info"];
		if (!validTypes.includes(type)) {
			type = "info";
		}

		if (this.items.length >= 5) {
			this.dismiss(this.items[0].id);
		}

		const id = ++this.counter;
		this.items.push({ id, message, type, visible: true });

		if (duration > 0) {
			setTimeout(() => this.dismiss(id), duration);
		}
	},

	dismiss(id) {
		const toast = this.items.find((item) => item.id === id);
		if (toast && toast.visible) {
			toast.visible = false;
			setTimeout(() => {
				this.items = this.items.filter((item) => item.id !== id);
			}, 300);
		}
	},
});

/**
 * Global toast function for use outside Alpine components.
 * Usage: window.toast("Project created!", "success")
 */
window.toast = function (message, type = "success", duration = 4000) {
	Alpine.store("toasts").add(message, type, duration);
};

window.Alpine = Alpine;
Alpine.start();

/* ────────────────────────────────────────────────────────────────────
 * Idle-session redirect
 * After SESSION_LIFETIME minutes of inactivity, redirect the user to
 * the landing page automatically — no need to click a form to discover
 * their session is gone. The timer is reset on real input events
 * (clicks, keystrokes, touches), so an active user is never bumped.
 * ──────────────────────────────────────────────────────────────────── */
(function initIdleSessionRedirect() {
	const lifetimeMeta = document.querySelector("meta[name='session-lifetime']");
	const lifetimeMinutes = lifetimeMeta ? parseInt(lifetimeMeta.getAttribute("content"), 10) : 0;

	if (!lifetimeMinutes || lifetimeMinutes <= 0) {
		return;
	}

	/* Server-rendered URL — uses route("home", ["session_expired" => 1]) so the
	   redirect respects APP_URL, locale prefixes, subdirectory deployments, etc.
	   Fallback to "/" only if the meta tag is missing for any reason. */
	const expiredUrlMeta = document.querySelector("meta[name='session-expired-url']");
	const expiredUrl = expiredUrlMeta?.getAttribute("content") || "/";

	const idleMs = (lifetimeMinutes * 60 + 5) * 1000;
	let idleTimerHandle = null;

	function redirectToHomeOnExpiry() {
		window.location.href = expiredUrl;
	}

	function resetIdleTimer() {
		if (idleTimerHandle !== null) {
			clearTimeout(idleTimerHandle);
		}
		idleTimerHandle = window.setTimeout(redirectToHomeOnExpiry, idleMs);
	}

	/* Only real user-input events count as activity. We deliberately exclude
	   "visibilitychange" — it fires both when the tab gains AND loses focus,
	   so including it would reset the idle clock every time the user Alt-Tabs
	   AWAY, defeating idle detection for users with the tab in the background. */
	["click", "keydown", "mousedown", "touchstart"].forEach((eventName) => {
		document.addEventListener(eventName, resetIdleTimer, { passive: true });
	});

	resetIdleTimer();
})();
