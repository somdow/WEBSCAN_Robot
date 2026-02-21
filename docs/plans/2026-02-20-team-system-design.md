# Team System Design

## Overview
Team management allowing organization owners to invite members via email magic links. Members can do everything except billing and team management.

## Roles
- **Owner**: Account creator. Full access including billing, team management, org settings.
- **Member**: Everyone else. Can create projects, run scans, add pages, view all results.

## Invitation Flow
1. Owner enters email on Team page → InvitationService creates signed token, sends email
2. Recipient clicks magic link → if logged in, added to org → if not, redirected to register with invite token preserved → after register, auto-added
3. Pending invitations shown in team list with resend/cancel options

## Permissions Matrix
| Action | Owner | Member |
|--------|-------|--------|
| Create/manage projects | Yes | Yes |
| Run scans, add pages | Yes | Yes |
| View all scan results | Yes | Yes |
| Invite/remove team members | Yes | No |
| Billing & plan management | Yes | No |
| Organization settings | Yes | No |

## UI
- Dedicated "Team" sidebar item (below Projects, above Settings)
- Team page: member list table + invite form
- Each member row: name, email, role badge, joined date, remove button (Owner only)
- Pending invitations section with status, resend, cancel

## Architecture

### Already Built
- Organization model with users() relationship
- organization_user pivot table with role column
- OrganizationRole enum
- BillingService.canAddMember() enforces Plan.max_users
- EnforcePlanLimits middleware

### New Components
- **Migration**: `team_invitations` table (token, email, org_id, invited_by, expires_at, accepted_at)
- **Model**: `TeamInvitation`
- **Service**: `InvitationService` — create, send, validate, accept, cancel, resend
- **Controllers**: `TeamController` (list/invite/remove), `TeamInvitationController` (accept link)
- **Mailable**: `TeamInviteMail`
- **Views**: `team/index.blade.php`
- **Middleware**: `EnsureOrganizationOwner` for team/billing routes
- **Sidebar**: Add Team nav item
