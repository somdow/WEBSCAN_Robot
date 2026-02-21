@props(["scans", "height" => "h-64"])

@php
	$chartScans = $scans
		->filter(fn ($scan) => $scan->status === \App\Enums\ScanStatus::Completed && $scan->overall_score !== null)
		->sortBy("created_at")
		->take(20)
		->values();

	$labels = $chartScans->map(fn ($scan) => $scan->created_at->format("M j"))->toArray();
	$scores = $chartScans->map(fn ($scan) => $scan->overall_score)->toArray();
@endphp

@if(count($scores) >= 2)
<div
	x-data="{
		chart: null,
		init() {
			const existing = Chart.getChart(this.$refs.canvas);
			if (existing) existing.destroy();

			const ctx = this.$refs.canvas.getContext('2d');

			const gradient = ctx.createLinearGradient(0, 0, 0, 256);
			gradient.addColorStop(0, 'rgba(242, 90, 21, 0.15)');
			gradient.addColorStop(1, 'rgba(242, 90, 21, 0.01)');

			const scores = {{ Js::from($scores) }};
			const pointColors = scores.map(score => {
				if (score >= 80) return '#10B981';
				if (score >= 50) return '#F59E0B';
				return '#EF4444';
			});

			this.chart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: {{ Js::from($labels) }},
					datasets: [{
						data: scores,
						borderColor: '#f25a15',
						backgroundColor: gradient,
						pointBackgroundColor: pointColors,
						pointBorderColor: pointColors,
						pointRadius: 5,
						pointHoverRadius: 7,
						tension: 0.3,
						fill: true,
						borderWidth: 2,
					}],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							callbacks: {
								label: (context) => 'Score: ' + context.parsed.y + ' / 100',
							},
						},
					},
					scales: {
						y: {
							min: 0,
							max: 100,
							ticks: { stepSize: 25 },
							grid: { color: '#F3F4F6' },
						},
						x: {
							grid: { display: false },
						},
					},
				},
			});
		},
	}"
	x-init="init()"
	class="{{ $height }}"
>
	<canvas x-ref="canvas"></canvas>
</div>
@endif
