(function () {
	const PILLAR_COLOR = { seo: "#ef5a24", health: "#2f5fa8", trust: "#6b21a8" };
	const MODULES = [
		["DUPL", "Content Duplicate", "trust"],
		["AUTHOR", "E-E-A-T Author", "trust"],
		["BIZ-SCH", "Business Schema", "trust"],
		["LEGAL", "Privacy & Terms", "trust"],
		["TRUST", "Trust Pages", "trust"],
		["CRUMB", "Breadcrumbs", "seo"],
		["CANON", "Canonical Tag", "seo"],
		["DOCTYPE", "Doctype & Charset", "seo"],
		["HREFLNG", "Hreflang", "seo"],
		["HTM-LNG", "HTML Lang Attribute", "seo"],
		["HEADERS", "HTTP Headers", "seo"],
		["META", "Meta Description", "seo"],
		["NOINDEX", "Noindex Check", "seo"],
		["R-META", "Robots Meta Tag", "seo"],
		["SEMHTML", "Semantic HTML", "seo"],
		["VPORT", "Viewport Tag", "health"],
		["READ", "Content Readability", "trust"],
		["URL", "URL Structure", "seo"],
		["THEME", "WordPress Theme", "health"],
		["FAV", "Favicon", "seo"],
		["H2-H6", "H2-H6 Tags", "seo"],
		["IMG", "Image Analysis", "seo"],
		["PERF", "Performance Hints", "health"],
		["TITLE", "Title Tag", "seo"],
		["MAP", "Google Map Embed", "seo"],
		["SERP", "SERP Preview", "seo"],
		["A11Y", "Accessibility Check", "health"],
		["GZIP", "Compression", "health"],
		["STACK", "Tech Stack Detection", "health"],
		["KEYS", "Keyword Consistency", "trust"],
		["BLKLST", "Blacklist Check", "trust"],
		["STATS", "Analytics Detection", "health"],
		["REDIR", "Redirect Chain", "seo"],
		["CWV-D", "Core Web Vitals — Desktop", "health"],
		["CWV-M", "Core Web Vitals — Mobile", "health"],
		["RBTXT", "Robots.txt", "seo"],
		["MIXED", "Mixed Content", "trust"],
		["SEC-HD", "Security Headers", "trust"],
		["SSL", "SSL Certificate", "trust"],
		["DUP-URL", "Duplicate URL", "seo"],
		["HTTPS", "HTTPS Redirect", "seo"],
		["WP", "WordPress Detection", "health"],
		["LINKS", "Link Analysis", "seo"],
		["SCHEMA", "Schema.org", "seo"],
		["SOCIAL", "Social Tags", "seo"],
		["H1", "H1 Tag", "seo"],
		["SCH-VAL", "Schema Validation", "seo"],
		["C-KEYS", "Content Keywords", "trust"],
		["LEAKS", "Exposed Sensitive Files", "trust"],
		["BROKEN", "Broken Links", "seo"],
		["SITEMAP", "Sitemap Analysis", "seo"],
		["WP-PLG", "WordPress Plugins", "health"]
	];
	const TOTAL_CELLS = 56;
	const HOLLOW = new Set([52, 53, 54, 55]);
	const grid = document.getElementById("modules-grid");
	const frag = document.createDocumentFragment();
	for (let i = 0; i < TOTAL_CELLS; i++) {
		const tile = document.createElement("div");
		if (HOLLOW.has(i)) {
			tile.className = "mod-tile hollow";
		} else {
			const [label, name, pillar] = MODULES[i];
			tile.className = "mod-tile";
			tile.style.color = PILLAR_COLOR[pillar];
			tile.textContent = label;
			tile.dataset.tooltip = name;
			tile.dataset.pillar = pillar;
		}
		frag.appendChild(tile);
	}
	grid.appendChild(frag);

	const pillarCards = document.querySelectorAll(".stats-grid .stat");
	const moduleTiles = Array.from(document.querySelectorAll(".mod-tile:not(.hollow)"));
	const hollowTiles = Array.from(document.querySelectorAll(".mod-tile.hollow"));
	let activePillar = null;

	function applyPillarFilter(pillar) {
		const oldPositions = new Map();
		moduleTiles.forEach(tile => {
			if (tile.style.display !== "none") {
				oldPositions.set(tile, tile.getBoundingClientRect());
			}
		});

		moduleTiles.forEach(tile => {
			tile.style.transition = "none";
			tile.style.transform = "";
			tile.style.opacity = "";
			const matches = pillar === null || tile.dataset.pillar === pillar;
			tile.style.display = matches ? "" : "none";
		});
		hollowTiles.forEach(tile => {
			tile.style.display = pillar === null ? "" : "none";
		});

		moduleTiles.forEach(tile => {
			if (tile.style.display === "none") return;
			const newRect = tile.getBoundingClientRect();
			const oldRect = oldPositions.get(tile);
			if (oldRect) {
				const deltaX = oldRect.left - newRect.left;
				const deltaY = oldRect.top - newRect.top;
				if (deltaX || deltaY) {
					tile.style.transform = "translate(" + deltaX + "px, " + deltaY + "px)";
				}
			} else {
				tile.style.opacity = "0";
				tile.style.transform = "scale(0.85)";
			}
		});

		requestAnimationFrame(() => {
			moduleTiles.forEach(tile => {
				if (tile.style.display === "none") return;
				tile.style.transition = "transform .45s cubic-bezier(.34, 1.2, .4, 1), opacity .25s ease";
				tile.style.transform = "";
				tile.style.opacity = "";
			});
		});
	}

	const modulesSection = document.querySelector(".modules");
	pillarCards.forEach(card => {
		card.addEventListener("click", () => {
			const pillar = card.dataset.pillar;
			if (activePillar === pillar) {
				activePillar = null;
				pillarCards.forEach(pillarCard => pillarCard.classList.remove("filter-active"));
				applyPillarFilter(null);
			} else {
				activePillar = pillar;
				pillarCards.forEach(pillarCard => pillarCard.classList.toggle("filter-active", pillarCard.dataset.pillar === pillar));
				applyPillarFilter(pillar);
			}
			modulesSection.scrollIntoView({ behavior: "smooth", block: "start" });
		});
	});

	const testimonialGrid = document.querySelector(".t-grid");
	const testimonialViewport = document.querySelector(".testimonials-track");
	if (testimonialGrid && testimonialViewport) {
		const originals = Array.from(testimonialGrid.children);
		originals.forEach(card => {
			const clone = card.cloneNode(true);
			clone.setAttribute("aria-hidden", "true");
			testimonialGrid.appendChild(clone);
		});

		let scrollOffset = 0;
		let lastFrameTime = performance.now();
		let isDragging = false;
		let dragStartX = 0;
		let dragStartOffset = 0;
		/* Pixels per millisecond. 0.04 ≈ 40px/sec — slow enough to read mid-card while still feeling alive. Increase for faster drift, decrease to pause longer per card. */
		const SCROLL_SPEED = 0.04;

		function getHalfTrackWidth() {
			return testimonialGrid.scrollWidth / 2;
		}

		function advanceScroll(now) {
			const elapsed = now - lastFrameTime;
			lastFrameTime = now;
			if (!isDragging) {
				scrollOffset += SCROLL_SPEED * elapsed;
			}
			const halfWidth = getHalfTrackWidth();
			if (halfWidth > 0) {
				if (scrollOffset >= halfWidth) scrollOffset -= halfWidth;
				if (scrollOffset < 0) scrollOffset += halfWidth;
			}
			testimonialGrid.style.transform = "translateX(-" + scrollOffset + "px)";
			requestAnimationFrame(advanceScroll);
		}
		requestAnimationFrame(advanceScroll);

		testimonialViewport.addEventListener("pointerdown", event => {
			isDragging = true;
			dragStartX = event.clientX;
			dragStartOffset = scrollOffset;
			testimonialViewport.classList.add("dragging");
			testimonialViewport.setPointerCapture(event.pointerId);
		});

		testimonialViewport.addEventListener("pointermove", event => {
			if (!isDragging) return;
			const delta = event.clientX - dragStartX;
			scrollOffset = dragStartOffset - delta;
		});

		const releaseDrag = event => {
			if (!isDragging) return;
			isDragging = false;
			testimonialViewport.classList.remove("dragging");
			if (event && event.pointerId !== undefined) {
				testimonialViewport.releasePointerCapture(event.pointerId);
			}
		};
		testimonialViewport.addEventListener("pointerup", releaseDrag);
		testimonialViewport.addEventListener("pointercancel", releaseDrag);
		testimonialViewport.addEventListener("pointerleave", releaseDrag);
	}
})();

/* ────────────────────────────────────────────────────────────────────
 * Waitlist form (only present when registration is disabled)
 * Submits via fetch so the user sees an inline success state rather
 * than a full page reload.
 * ──────────────────────────────────────────────────────────────────── */
(function initWaitlistForm() {
	const form = document.getElementById("waitlistForm");
	const success = document.getElementById("waitlistSuccess");
	if (!form || !success) {
		return;
	}

	form.addEventListener("submit", async function handleWaitlistSubmit(event) {
		event.preventDefault();

		const submitButton = form.querySelector("button[type='submit']");
		const originalLabel = submitButton.textContent;
		submitButton.disabled = true;
		submitButton.textContent = "Saving…";

		const csrfToken = document.querySelector("meta[name='csrf-token']")?.getAttribute("content") || "";
		const payload = new FormData(form);

		try {
			const response = await fetch(form.action, {
				method: "POST",
				headers: {
					"Accept": "application/json",
					"X-Requested-With": "XMLHttpRequest",
					"X-CSRF-TOKEN": csrfToken,
				},
				body: payload,
			});

			const responseBody = await response.json().catch(() => ({}));

			if (!response.ok || responseBody?.success === false) {
				const message = responseBody?.error || "Something went wrong. Please try again.";
				submitButton.disabled = false;
				submitButton.textContent = originalLabel;
				alert(message);
				return;
			}

			form.hidden = true;
			success.hidden = false;
		} catch (networkError) {
			submitButton.disabled = false;
			submitButton.textContent = originalLabel;
			alert("Network error. Please try again.");
		}
	});
})();
