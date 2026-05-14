<section class="hero">
	<h1>Audit your site.<br/>Find what is costing you traffic.</h1>
	<p>Spot SEO gaps, security risks, and trust signals competitors miss. Get AI-guided fixes and client-ready reports — in minutes, not days.</p>

	@if($registrationEnabled)
		<form class="hero-audit" method="GET" action="{{ route("register") }}">
			<input type="text" name="url" placeholder="example.com" required autocomplete="off" />
			<button type="submit" class="btn accent">Run audit →</button>
		</form>
		<div class="hero-beta">
			<span><span class="beta-pill">BETA</span>Heads up — we are in active beta. Expect new features shipping every week, and the occasional rough edge while we polish.</span>
		</div>
		<div class="hero-trust">
			<span>✓ No credit card</span>
			<span>✓ Setup in under 5 minutes</span>
			<span>✓ Built for agencies, consultants &amp; in-house teams</span>
		</div>
	@else
		<form class="hero-audit hero-audit-waitlist" id="waitlistForm" method="POST" action="{{ route("waitlist.store") }}">
			@csrf
			<input type="text" name="desired_url" placeholder="example.com" autocomplete="off" />
			<input type="email" name="email" placeholder="Enter your email" required autocomplete="email" />
			<button type="submit" class="btn accent">Notify me →</button>
		</form>
		<div class="hero-beta">
			<span><span class="beta-pill">BETA</span>We are in active beta and shipping new features every week. Drop your email — we will let you in when public signups reopen.</span>
		</div>
		<div class="waitlist-success" id="waitlistSuccess" hidden>
			<strong>You are on the list.</strong> We will email you the moment we reopen signups.
		</div>
	@endif

	<div class="chips">
		<div class="chip active">
			<div class="label">● Featured</div>
			<div class="title">Find every issue</div>
			<div class="desc">SEO · Health · Security · E-E-A-T</div>
		</div>
		<div class="chip">
			<div class="label">Outcome</div>
			<div class="title">Get AI fix plans</div>
			<div class="desc">Plain-English next steps, ranked by impact</div>
		</div>
		<div class="chip">
			<div class="label">Outcome</div>
			<div class="title">Deliver branded reports</div>
			<div class="desc">White-label PDFs your clients can act on</div>
		</div>
		<div class="chip">
			<div class="label">Outcome</div>
			<div class="title">Outpace competitors</div>
			<div class="desc">Side-by-side benchmarks + rescans</div>
		</div>
		<div class="chip">
			<div class="label">Outcome</div>
			<div class="title">Run 50+ clients</div>
			<div class="desc">Teams · projects · billing in one place</div>
		</div>
	</div>

	@include("landing.featured-module")
</section>
