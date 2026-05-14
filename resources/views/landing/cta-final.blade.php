<section class="final-cta-wrap">
	<div class="final-cta">
		<h2>Stop guessing.<br/>Start auditing.</h2>
		<div class="ctas">
			@if($registrationEnabled)
				<a class="btn accent" href="{{ route("register") }}">Start free scan</a>
			@else
				<a class="btn accent" href="#waitlistForm">Get notified when signups reopen</a>
			@endif
		</div>
	</div>
</section>
