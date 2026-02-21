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
