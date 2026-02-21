{{-- Alpine scanResultsManager() with pages management --}}
{{-- Extracted to its own partial to avoid Blade directives inside <script> tags --}}
{{-- Expects: $project, $pagesJsonData, $isSinglePage, $scanViewData --}}

<script>
function scanResultsManager() {
	const projectKey = @json($project->getRouteKey());

	return {
		activeCategory: @json($isSinglePage ? ($scanViewData["groupedResults"]->keys()->first() ?? "") : ""),
		statusFilter: '',
		activeSection: '',
		searchQuery: '',
		scoreTab: 'all',
		healthSection: '',
		seoSection: '',
		liveOverallScore: @json($scan->overall_score ?? null),
		liveSeoScore: @json($scan->seo_score ?? null),
		liveHealthScore: @json($scan->health_score ?? null),

		competitors: [],
		maxCompetitors: 0,
		circumference: 0,
		ownModuleStatuses: {},
		comparisonGroups: {},
		moduleLabels: {},
		newCompetitorUrl: '',
		competitorError: '',
		isAddingCompetitor: false,
		showCompetitorInput: false,
		competitorPollingIntervals: {},

		newPageUrl: '',
		errorMessage: '',
		isSubmitting: false,
		pollingIntervals: {},
		pollingFailures: {},
		pages: @json($pagesJsonData),
		discoveryStatus: @json($project->discovery_status ?? ''),
		discoveredPages: [],
		selectedDiscoveredIds: [],
		isAnalyzingSelected: false,
		discoveryError: '',
		discoveryPollInterval: null,
		showManualInput: false,

		init() {
			this.pages.forEach(page => {
				if (page.analysis_status === 'pending' || page.analysis_status === 'running') {
					this.startPolling(page);
				}
			});

			if (this.discoveryStatus === 'completed') {
				this.loadDiscoveredPages();
			} else if (this.discoveryStatus === 'running' || this.discoveryStatus === 'pending') {
				this.startDiscoveryPolling();
			}

			this.competitors.forEach(competitor => {
				if (competitor.scan_status === 'pending' || competitor.scan_status === 'running') {
					this.startCompetitorPolling(competitor);
				}
			});
		},

		async submitPage() {
			if (this.isSubmitting || !this.newPageUrl.trim()) return;

			this.isSubmitting = true;
			this.errorMessage = '';

			try {
				const response = await fetch(`/projects/${projectKey}/pages`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
						'Accept': 'application/json',
					},
					body: JSON.stringify({ url: this.newPageUrl.trim() }),
				});

				const data = await response.json();

				if (!response.ok) {
					this.errorMessage = data.error || data.errors?.url?.[0] || 'Failed to add page.';
					return;
				}

				this.upsertPage({
					id: data.page.id,
					uuid: data.page.uuid,
					url: data.page.url,
					page_score: null,
					source: data.page.source,
					analysis_status: data.page.analysis_status,
					error_message: null,
					scan_page_url: null,
					scanned_at: data.page.scanned_at ?? null,
					_rescanning: false,
					_isNew: true,
				});
				this.newPageUrl = '';
				this.showManualInput = false;
				this.scoreTab = 'pagesList';
				this.startPolling({ id: data.page.id, uuid: data.page.uuid });
				window.dispatchEvent(new CustomEvent('credits-used', { detail: { count: 1 } }));
			} catch (err) {
				this.errorMessage = 'Network error. Please try again.';
			} finally {
				this.isSubmitting = false;
			}
		},

		startPolling(page) {
			if (this.pollingIntervals[page.id]) return;

			this.pollingFailures[page.id] = 0;
			const pollUrl = `/projects/${projectKey}/pages/${page.uuid}/progress`;
			const maxFailures = 15;

			this.pollingIntervals[page.id] = setInterval(async () => {
				try {
					const response = await fetch(pollUrl, {
						headers: { 'Accept': 'application/json' },
					});

					if (!response.ok) {
						this.pollingFailures[page.id]++;
					} else {
						this.pollingFailures[page.id] = 0;
						const data = await response.json();
						const idx = this.pages.findIndex(p => p.id === page.id);
						if (idx === -1) return;

						this.pages[idx].analysis_status = data.analysis_status;
						this.pages[idx].page_score = data.page_score;
						this.pages[idx].error_message = data.error_message;
						this.pages[idx].scanned_at = data.scanned_at ?? this.pages[idx].scanned_at;
						this.pages[idx]._rescanning = false;
						if (data.scan_page_url) {
							this.pages[idx].scan_page_url = data.scan_page_url;
						}

						if (data.analysis_status === 'completed' || data.analysis_status === 'failed') {
							clearInterval(this.pollingIntervals[page.id]);
							delete this.pollingIntervals[page.id];

							if (data.analysis_status === 'completed' && data.scan_scores) {
								this.liveOverallScore = data.scan_scores.overall_score ?? this.liveOverallScore;
								this.liveSeoScore = data.scan_scores.seo_score ?? this.liveSeoScore;
								this.liveHealthScore = data.scan_scores.health_score ?? this.liveHealthScore;
								this.$dispatch('scores-updated', data.scan_scores);
							}
						}
					}
				} catch (err) {
					this.pollingFailures[page.id]++;
				}

				if (this.pollingFailures[page.id] >= maxFailures) {
					clearInterval(this.pollingIntervals[page.id]);
					delete this.pollingIntervals[page.id];
					const idx = this.pages.findIndex(p => p.id === page.id);
					if (idx !== -1) {
						this.pages[idx].analysis_status = 'failed';
						this.pages[idx].error_message = 'Connection lost. Please refresh the page.';
					}
				}
			}, 2000);
		},

		async rescanPage(page) {
			if (page._rescanning) return;

			const idx = this.pages.findIndex(p => p.id === page.id);
			if (idx === -1) return;

			this.pages[idx]._rescanning = true;

			try {
				const response = await fetch(`/projects/${projectKey}/pages/${page.uuid}/rescan`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
						'Accept': 'application/json',
					},
				});

				const data = await response.json();

				if (!response.ok) {
					this.pages[idx]._rescanning = false;
					alert(data.error || 'Failed to rescan page.');
					return;
				}

				this.pages[idx].analysis_status = 'pending';
				this.pages[idx].page_score = null;
				this.pages[idx].error_message = null;
				this.startPolling(page);
				window.dispatchEvent(new CustomEvent('credits-used', { detail: { count: 1 } }));
			} catch (err) {
				this.pages[idx]._rescanning = false;
				alert('Network error. Please try again.');
			}
		},

		truncateUrl(url, maxLength) {
			if (url.length <= maxLength) return url;
			return url.substring(0, maxLength - 3) + '...';
		},

		scoreColorClass(score) {
			if (score === null) return 'text-gray-400';
			if (score >= 80) return 'text-emerald-600';
			if (score >= 50) return 'text-amber-600';
			return 'text-red-600';
		},

		async startDiscovery() {
			this.discoveryError = '';
			this.discoveredPages = [];
			this.selectedDiscoveredIds = [];

			try {
				const response = await fetch(`/projects/${projectKey}/discover`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
						'Accept': 'application/json',
					},
				});

				const data = await response.json();
				if (!response.ok) {
					this.discoveryError = data.error || 'Failed to start discovery.';
					return;
				}

				this.discoveryStatus = data.discovery_status;
				this.startDiscoveryPolling();
				window.dispatchEvent(new CustomEvent('credits-used', { detail: { count: 1 } }));
			} catch (err) {
				this.discoveryError = 'Network error. Please try again.';
			}
		},

		startDiscoveryPolling() {
			if (this.discoveryPollInterval) return;

			const discoveredUrl = `/projects/${projectKey}/discovered`;
			let failures = 0;

			this.discoveryPollInterval = setInterval(async () => {
				try {
					const response = await fetch(discoveredUrl, {
						headers: { 'Accept': 'application/json' },
					});

					if (!response.ok) { failures++; }
					else {
						failures = 0;
						const data = await response.json();
						this.discoveryStatus = data.discovery_status;
						this.discoveredPages = data.pages;

						if (data.discovery_status === 'completed' || data.discovery_status === 'failed') {
							clearInterval(this.discoveryPollInterval);
							this.discoveryPollInterval = null;
						}
					}
				} catch (err) { failures++; }

				if (failures >= 10) {
					clearInterval(this.discoveryPollInterval);
					this.discoveryPollInterval = null;
					this.discoveryStatus = 'failed';
					this.discoveryError = 'Connection lost during discovery. Please try again.';
				}
			}, 2500);
		},

		async loadDiscoveredPages() {
			try {
				const response = await fetch(`/projects/${projectKey}/discovered`, {
					headers: { 'Accept': 'application/json' },
				});
				if (response.ok) {
					const data = await response.json();
					this.discoveredPages = data.pages;
				}
			} catch (err) { /* silent */ }
		},

		toggleDiscoveredSelection(pageId) {
			const idx = this.selectedDiscoveredIds.indexOf(pageId);
			if (idx > -1) {
				this.selectedDiscoveredIds.splice(idx, 1);
			} else if (this.selectedDiscoveredIds.length < 5) {
				this.selectedDiscoveredIds.push(pageId);
			}
		},

		async analyzeSelected() {
			if (this.selectedDiscoveredIds.length === 0 || this.isAnalyzingSelected) return;

			this.isAnalyzingSelected = true;
			this.discoveryError = '';

			try {
				const response = await fetch(`/projects/${projectKey}/analyze-selected`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
						'Accept': 'application/json',
					},
					body: JSON.stringify({ page_ids: this.selectedDiscoveredIds }),
				});

				const data = await response.json();
				if (!response.ok) {
					this.discoveryError = data.error || 'Failed to start analysis.';
					return;
				}

				data.pages.forEach(newPage => {
					this.upsertPage({
						id: newPage.id,
						uuid: newPage.uuid,
						url: newPage.url,
						page_score: newPage.page_score ?? null,
						source: 'discovery',
						analysis_status: newPage.analysis_status,
						error_message: null,
						scan_page_url: null,
						scanned_at: newPage.scanned_at ?? null,
						_rescanning: false,
						_isNew: true,
					});

					if (newPage.analysis_status === 'pending' || newPage.analysis_status === 'running') {
						this.startPolling({ id: newPage.id, uuid: newPage.uuid });
					}
				});

				this.selectedDiscoveredIds.forEach(id => {
					const dp = this.discoveredPages.find(p => p.id === id);
					if (dp) dp.is_analyzed = true;
				});

				this.selectedDiscoveredIds = [];
				this.scoreTab = 'pagesList';

				const creditsConsumed = data.pages.filter(p => p.analysis_status === 'pending').length;
				if (creditsConsumed > 0) {
					window.dispatchEvent(new CustomEvent('credits-used', { detail: { count: creditsConsumed } }));
				}
			} catch (err) {
				this.discoveryError = 'Network error. Please try again.';
			} finally {
				this.isAnalyzingSelected = false;
			}
		},

		upsertPage(pageData) {
			const existingByUrl = this.pages.findIndex(p => p.url === pageData.url);
			if (existingByUrl !== -1) {
				this.pages.splice(existingByUrl, 1);
			}
			this.pages.unshift(pageData);
		},

		formatScannedDate(isoString) {
			if (!isoString) return '—';
			const date = new Date(isoString);
			const month = date.toLocaleString('en-US', { month: 'short' });
			const day = date.getDate();
			const year = date.getFullYear();
			const hours = date.getHours();
			const minutes = String(date.getMinutes()).padStart(2, '0');
			const ampm = hours >= 12 ? 'PM' : 'AM';
			const displayHour = hours % 12 || 12;
			return `${month} ${day}, ${year} ${displayHour}:${minutes} ${ampm}`;
		},

		async submitCompetitor() {
			if (this.isAddingCompetitor || !this.newCompetitorUrl.trim()) return;

			this.isAddingCompetitor = true;
			this.competitorError = '';

			try {
				const response = await fetch(`/projects/${projectKey}/competitors`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
						'Accept': 'application/json',
					},
					body: JSON.stringify({ url: this.newCompetitorUrl.trim() }),
				});

				const data = await response.json();

				if (!response.ok) {
					this.competitorError = data.error || data.errors?.url?.[0] || 'Failed to add competitor.';
					return;
				}

				this.competitors.push({
					id: data.competitor.id,
					uuid: data.competitor.uuid,
					url: data.competitor.url,
					name: data.competitor.name,
					overall_score: null,
					seo_score: null,
					health_score: null,
					scan_status: 'pending',
					scanned_at: null,
					category_scores: [],
					_expanded: false,
					_rescanning: false,
					_removing: false,
				});

				this.newCompetitorUrl = '';
				this.showCompetitorInput = false;
				this.startCompetitorPolling(data.competitor);
				window.dispatchEvent(new CustomEvent('credits-used', { detail: { count: 1 } }));
			} catch (err) {
				this.competitorError = 'Network error. Please try again.';
			} finally {
				this.isAddingCompetitor = false;
			}
		},

		startCompetitorPolling(competitor) {
			if (this.competitorPollingIntervals[competitor.id]) return;

			const pollUrl = `/projects/${projectKey}/competitors/${competitor.uuid}/progress`;
			let failures = 0;

			this.competitorPollingIntervals[competitor.id] = setInterval(async () => {
				try {
					const response = await fetch(pollUrl, {
						headers: { 'Accept': 'application/json' },
					});

					if (!response.ok) {
						failures++;
					} else {
						failures = 0;
						const data = await response.json();
						const idx = this.competitors.findIndex(c => c.id === competitor.id);
						if (idx === -1) return;

						this.competitors[idx].scan_status = data.status;

						if (data.is_complete) {
							clearInterval(this.competitorPollingIntervals[competitor.id]);
							delete this.competitorPollingIntervals[competitor.id];

							if (data.scores) {
								this.competitors[idx].overall_score = data.scores.overall_score;
								this.competitors[idx].seo_score = data.scores.seo_score;
								this.competitors[idx].health_score = data.scores.health_score;
							}
							this.competitors[idx].scanned_at = new Date().toISOString();
							this.competitors[idx]._rescanning = false;

							this.reloadCompetitorCategories(competitor);
						}
					}
				} catch (err) {
					failures++;
				}

				if (failures >= 15) {
					clearInterval(this.competitorPollingIntervals[competitor.id]);
					delete this.competitorPollingIntervals[competitor.id];
					const failIdx = this.competitors.findIndex(c => c.id === competitor.id);
					if (failIdx !== -1) {
						this.competitors[failIdx].scan_status = 'failed';
						this.competitors[failIdx]._rescanning = false;
					}
				}
			}, 2000);
		},

		async reloadCompetitorCategories(competitor) {
			try {
				const response = await fetch(`/projects/${projectKey}/competitors/${competitor.uuid}/progress`, {
					headers: { 'Accept': 'application/json' },
				});
				if (!response.ok) return;
				const data = await response.json();
				const idx = this.competitors.findIndex(c => c.id === competitor.id);
				if (idx === -1) return;

				if (data.category_scores && data.category_scores.length > 0) {
					this.competitors[idx].category_scores = data.category_scores;
				}
			} catch (err) { /* silent */ }
		},

		async rescanCompetitor(competitor) {
			if (competitor._rescanning) return;

			const idx = this.competitors.findIndex(c => c.id === competitor.id);
			if (idx === -1) return;

			this.competitors[idx]._rescanning = true;

			try {
				const response = await fetch(`/projects/${projectKey}/competitors/${competitor.uuid}/rescan`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
						'Accept': 'application/json',
					},
				});

				const data = await response.json();

				if (!response.ok) {
					this.competitors[idx]._rescanning = false;
					alert(data.error || 'Failed to rescan competitor.');
					return;
				}

				this.competitors[idx].scan_status = 'pending';
				this.competitors[idx].overall_score = null;
				this.competitors[idx].seo_score = null;
				this.competitors[idx].health_score = null;
				this.competitors[idx].category_scores = [];
				this.startCompetitorPolling(competitor);
				window.dispatchEvent(new CustomEvent('credits-used', { detail: { count: 1 } }));
			} catch (err) {
				this.competitors[idx]._rescanning = false;
				alert('Network error. Please try again.');
			}
		},

		async removeCompetitor(competitor) {
			if (competitor._removing) return;
			if (!confirm('Remove this competitor? All scan data will be deleted.')) return;

			const idx = this.competitors.findIndex(c => c.id === competitor.id);
			if (idx === -1) return;

			this.competitors[idx]._removing = true;

			try {
				const response = await fetch(`/projects/${projectKey}/competitors/${competitor.uuid}`, {
					method: 'DELETE',
					headers: {
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
						'Accept': 'application/json',
					},
				});

				if (response.ok) {
					this.competitors.splice(idx, 1);
					if (this.competitorPollingIntervals[competitor.id]) {
						clearInterval(this.competitorPollingIntervals[competitor.id]);
						delete this.competitorPollingIntervals[competitor.id];
					}
				} else {
					this.competitors[idx]._removing = false;
					alert('Failed to remove competitor.');
				}
			} catch (err) {
				this.competitors[idx]._removing = false;
				alert('Network error. Please try again.');
			}
		},

		scoreStrokeClass(score) {
			if (score === null) return 'stroke-gray-300';
			if (score >= 80) return 'stroke-emerald-500';
			if (score >= 50) return 'stroke-amber-500';
			return 'stroke-red-500';
		},

		destroy() {
			Object.values(this.pollingIntervals).forEach(id => clearInterval(id));
			Object.values(this.competitorPollingIntervals).forEach(id => clearInterval(id));
			if (this.discoveryPollInterval) clearInterval(this.discoveryPollInterval);
		},
	};
}
</script>
