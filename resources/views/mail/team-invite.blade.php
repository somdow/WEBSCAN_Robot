<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Team Invitation</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px;">
		<tr>
			<td align="center">
				<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
					{{-- Header --}}
					<tr>
						<td style="padding: 32px 40px 0;">
							<div style="display: inline-block; width: 32px; height: 32px; background-color: #4F46E5; border-radius: 8px; text-align: center; line-height: 32px; color: #ffffff; font-weight: bold; font-size: 14px;">{{ strtoupper(substr(config("app.name", "W"), 0, 1)) }}</div>
						</td>
					</tr>

					{{-- Body --}}
					<tr>
						<td style="padding: 24px 40px 32px;">
							<h1 style="margin: 0 0 16px; font-size: 20px; font-weight: 600; color: #111827;">You're invited to join a team</h1>

							<p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #4b5563;">
								<strong>{{ $inviterName }}</strong> has invited you to join
								<strong>{{ $organizationName }}</strong> on {{ config("app.name", "Hello SEO Analyzer") }}.
							</p>

							{{-- CTA Button --}}
							<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 0 24px;">
								<tr>
									<td style="background-color: #4F46E5; border-radius: 6px;">
										<a href="{{ $acceptUrl }}" style="display: inline-block; padding: 12px 28px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none;">
											Accept Invitation
										</a>
									</td>
								</tr>
							</table>

							<p style="margin: 0 0 8px; font-size: 13px; color: #9ca3af;">
								This invitation expires on {{ $expiresAt }}.
							</p>

							<p style="margin: 0; font-size: 13px; color: #9ca3af;">
								If you didn't expect this invitation, you can safely ignore this email.
							</p>
						</td>
					</tr>

					{{-- Footer --}}
					<tr>
						<td style="padding: 16px 40px; border-top: 1px solid #f3f4f6;">
							<p style="margin: 0; font-size: 12px; color: #9ca3af;">
								{{ config("app.name", "Hello SEO Analyzer") }}
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
