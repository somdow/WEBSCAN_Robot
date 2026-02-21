<x-app-layout>
	<x-slot name="header">
		<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Team</h1>
		<p class="mt-1 text-sm text-text-secondary">Manage your team members and invitations.</p>
		<x-breadcrumb :items="array(
			array('label' => 'Home', 'url' => route('dashboard')),
			array('label' => 'Team'),
		)" />
	</x-slot>

	<div class="space-y-6">

		{{-- Invite New Member (Owner Only) --}}
		@if($isOwner)
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				<div class="flex items-center justify-between">
					<div>
						<h2 class="text-lg font-semibold text-text-primary">Invite a Team Member</h2>
						<p class="mt-1 text-sm text-text-secondary">
							Send an email invitation to join your team.
							<span class="text-text-tertiary">({{ $currentCount }} / {{ $maxUsers }} seats used)</span>
						</p>
					</div>
				</div>

				<form method="POST" action="{{ route("team.invite") }}" class="mt-4 flex items-end gap-3">
					@csrf
					<div class="flex-1">
						<label for="email" class="block text-sm font-medium text-text-secondary">Email address</label>
						<input
							type="email"
							name="email"
							id="email"
							required
							placeholder="colleague@example.com"
							value="{{ old("email") }}"
							class="mt-1 block w-full rounded-md border border-border bg-white px-3 py-2 text-sm text-text-primary shadow-sm placeholder:text-text-tertiary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
						>
						@error("email")
							<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
						@enderror
					</div>
					<button
						type="submit"
						class="inline-flex items-center gap-2 rounded-md bg-accent px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent/90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2"
						@if($currentCount >= $maxUsers) disabled title="Team member limit reached" @endif
					>
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
						</svg>
						Send Invite
					</button>
				</form>

				@if($currentCount >= $maxUsers)
					<p class="mt-3 text-sm text-amber-600">
						You've reached your plan's team member limit. <a href="{{ route("billing.index") }}" class="font-medium underline">Upgrade your plan</a> to add more members.
					</p>
				@endif
			</div>
		@endif

		{{-- Current Members --}}
		<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
			<h2 class="text-lg font-semibold text-text-primary">Team Members</h2>
			<p class="mt-1 text-sm text-text-secondary">People who have access to this organization's projects and scans.</p>

			<div class="mt-4 overflow-hidden rounded-lg border border-border">
				<table class="w-full table-auto divide-y divide-border">
					<thead class="bg-gray-50">
						<tr>
							<th scope="col" class="w-1/2 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-text-tertiary">Member</th>
							<th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-text-tertiary">Role</th>
							<th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-text-tertiary">Joined</th>
							@if($isOwner)
								<th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-text-tertiary">Actions</th>
							@endif
						</tr>
					</thead>
					<tbody class="divide-y divide-border bg-white">
						@foreach($members as $member)
							<tr>
								<td class="whitespace-nowrap px-4 py-3">
									<div class="flex items-center gap-3">
										<div class="flex h-8 w-8 items-center justify-center rounded-full bg-accent/10 text-sm font-semibold text-accent">
											{{ strtoupper(substr($member->name ?? $member->email, 0, 1)) }}
										</div>
										<div>
											<div class="text-sm font-medium text-text-primary">{{ $member->name ?? "Unnamed" }}</div>
											<div class="text-xs text-text-tertiary">{{ $member->email }}</div>
										</div>
									</div>
								</td>
								<td class="whitespace-nowrap px-4 py-3">
									@if($member->pivot->role === $ownerRole)
										<span class="inline-flex items-center rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-medium text-accent">Owner</span>
									@else
										<span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Member</span>
									@endif
								</td>
								<td class="whitespace-nowrap px-4 py-3 text-sm text-text-secondary">
									{{ $member->pivot->created_at ? \Carbon\Carbon::parse($member->pivot->created_at)->format("M j, Y") : "—" }}
								</td>
								@if($isOwner)
									<td class="whitespace-nowrap px-4 py-3 text-right">
										@if($member->pivot->role !== $ownerRole)
											<form method="POST" action="{{ route("team.remove-member", $member) }}" class="inline" onsubmit="return confirm(@js("Remove " . ($member->name ?? $member->email) . " from the team?"))">
												@csrf
												@method("DELETE")
												<button type="submit" class="text-sm font-medium text-red-600 transition hover:text-red-700">
													Remove
												</button>
											</form>
										@else
											<span class="text-sm text-text-tertiary">—</span>
										@endif
									</td>
								@endif
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>

		{{-- Pending Invitations (Owner Only) --}}
		@if($isOwner && $pendingInvitations->isNotEmpty())
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				<h2 class="text-lg font-semibold text-text-primary">Pending Invitations</h2>
				<p class="mt-1 text-sm text-text-secondary">Invitations that haven't been accepted yet.</p>

				<div class="mt-4 overflow-hidden rounded-lg border border-border">
					<table class="w-full table-auto divide-y divide-border">
						<thead class="bg-gray-50">
							<tr>
								<th scope="col" class="w-1/3 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-text-tertiary">Email</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-text-tertiary">Invited By</th>
								<th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-text-tertiary">Expires</th>
								<th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-text-tertiary">Actions</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-border bg-white">
							@foreach($pendingInvitations as $invitation)
								<tr>
									<td class="whitespace-nowrap px-4 py-3">
										<div class="flex items-center gap-3">
											<div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-600">
												<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
												</svg>
											</div>
											<span class="text-sm font-medium text-text-primary">{{ $invitation->email }}</span>
										</div>
									</td>
									<td class="whitespace-nowrap px-4 py-3 text-sm text-text-secondary">
										{{ $invitation->inviter->name ?? "Unknown" }}
									</td>
									<td class="whitespace-nowrap px-4 py-3 text-sm text-text-secondary">
										{{ $invitation->expires_at->format("M j, Y") }}
									</td>
									<td class="whitespace-nowrap px-4 py-3 text-right">
										<div class="flex items-center justify-end gap-3">
											<form method="POST" action="{{ route("team.resend-invitation", $invitation) }}">
												@csrf
												<button type="submit" class="text-sm font-medium text-accent transition hover:text-accent/80">
													Resend
												</button>
											</form>
											<form method="POST" action="{{ route("team.cancel-invitation", $invitation) }}" onsubmit="return confirm(@js("Cancel invitation to " . $invitation->email . "?"))">
												@csrf
												@method("DELETE")
												<button type="submit" class="text-sm font-medium text-red-600 transition hover:text-red-700">
													Cancel
												</button>
											</form>
										</div>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		@endif

	</div>
</x-app-layout>
