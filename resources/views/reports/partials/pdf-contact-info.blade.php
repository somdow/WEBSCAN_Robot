{{--
	Reusable contact info block for PDF reports.

	Required: $author (User model), $variant ("cover" or "outro")
--}}
@if($author)
@php
	$isCover = $variant === "cover";
	$wrapperClass = $isCover ? "contact-section" : "outro-contact";
	$nameClass = $isCover ? "contact-name" : "outro-contact-name";
	$detailClass = $isCover ? "contact-detail" : "outro-contact-detail";
@endphp
<div class="{{ $wrapperClass }}">
	@if($isCover)
		<div class="contact-label">Prepared By</div>
	@endif
	<div class="{{ $nameClass }}">{{ $author->name }}</div>
	@if($author->email)
		<div class="{{ $detailClass }}">Email: {{ $author->email }}</div>
	@endif
	@if($author->phone)
		<div class="{{ $detailClass }}">Phone: {{ $author->phone }}</div>
	@endif
</div>
@endif
