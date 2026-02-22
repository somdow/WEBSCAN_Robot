# Backlog: Soft Delete & Data Retention System

**Status:** Planned (not started)
**Priority:** High
**Created:** 2026-02-22

## Goal

No data is ever permanently deleted. All destructive operations become soft deletes. Deactivation blocks user access but preserves all data (scans, projects, pages, results). Admins can view and manage soft-deleted records.

## Current State (Audit)

### Models — None Use SoftDeletes
- `User` — has `deactivated_at` (custom) but no `SoftDeletes` trait
- `Organization` — has `deactivated_at` (custom) but no `SoftDeletes` trait
- `Project`, `Scan`, `ScanPage`, `ScanModuleResult` — no soft delete at all
- `Plan`, `Coupon`, `AuditLog`, `Setting`, `TeamInvitation` — no soft delete
- `SubscriptionUsage`, `DiscoveredPage`, `Competitor` — no soft delete

### Hard Delete Paths (Must Be Replaced)

**Filament Admin Panel:**
- `OrganizationResource` — `DeleteAction` + `DeleteBulkAction` (hard deletes org, cascades to projects → scans → results/pages)
- `UserResource` — `DeleteAction` + `DeleteBulkAction` (hard deletes user)
- `PlanResource` — `DeleteAction` + `DeleteBulkAction` (hard deletes plan — dangerous, breaks FKs)
- `CouponResource` — `DeleteAction` + `DeleteBulkAction` (hard deletes coupon)

**Frontend (User-Facing):**
- `ProjectController@destroy` — `DELETE /projects/{project}` — hard deletes project (cascades to scans → results/pages)
- `CompetitorController@destroy` — `DELETE /projects/{project}/competitors/{competitor}` — hard deletes competitor
- `ProjectPageController@destroy` — `DELETE /projects/{project}/pages/{scanPage}` — hard deletes scan page + its module results
- `DiscoveryController@destroyAll` — `DELETE /projects/{project}/discovered-pages` — hard deletes all discovered pages for project

**Cascade Foreign Keys (Migration-Level):**
- `projects.organization_id` → `cascadeOnDelete` (org delete kills all projects)
- `scans.project_id` → `cascadeOnDelete` (project delete kills all scans)
- `scan_module_results.scan_id` → `cascadeOnDelete` (scan delete kills all results)
- `scan_pages.project_id` → `cascadeOnDelete` (project delete kills all pages)
- `competitors.project_id` → `cascadeOnDelete` (project delete kills all competitors)
- `discovered_pages.project_id` → `cascadeOnDelete` (project delete kills discovered pages)

## Implementation Plan

### Phase 1: Core Soft Delete Infrastructure

#### 1A. Migration — Add `deleted_at` Columns
Add `$table->softDeletes()` to these tables:
- `users`
- `organizations`
- `projects`
- `scans`
- `scan_pages`
- `scan_module_results`
- `competitors`
- `discovered_pages`
- `plans`
- `coupons`
- `team_invitations`
- `subscription_usages`

#### 1B. Add SoftDeletes Trait to Models
Add `use SoftDeletes;` to all models listed above. Ensure `deleted_at` is in `$casts` as `datetime`.

#### 1C. Replace Cascade Deletes with Cascade Soft Deletes
**Option A:** Use `dyrynda/laravel-cascade-soft-deletes` package — automatically soft-deletes children when parent is soft-deleted.
**Option B:** Manual approach — override `boot()` on parent models to listen for `deleting` event and soft-delete children.

Recommendation: **Option A** (package) for reliability, or manual if we want zero dependencies.

Cascade chains:
- Organization soft-delete → soft-delete Projects → soft-delete Scans, ScanPages, Competitors, DiscoveredPages → soft-delete ScanModuleResults
- User soft-delete → does NOT cascade (users belong to orgs via pivot; orgs remain intact)
- Project soft-delete → soft-delete Scans, ScanPages, Competitors, DiscoveredPages → soft-delete ScanModuleResults

#### 1D. Update FK Constraints
Change `cascadeOnDelete` to `nullOnDelete` or `restrictOnDelete` on all affected foreign keys. The soft-delete cascade (1C) handles child cleanup; DB-level cascades must not hard-delete.

### Phase 2: Replace Hard Delete Actions

#### 2A. Filament Admin Panel
Replace all `DeleteAction` and `DeleteBulkAction` with soft-delete equivalents:
- Filament's `DeleteAction` automatically uses `SoftDeletes` when the model has the trait — verify this works
- Add `ForceDeleteAction` and `RestoreAction` for admin use (see Phase 3)
- `PlanResource` — special case: soft-deleting a plan should NOT be allowed if organizations are using it. Add validation.

#### 2B. Frontend Controllers
- `ProjectController@destroy` → calls `$project->delete()` which becomes soft-delete automatically
- `CompetitorController@destroy` → same
- `ProjectPageController@destroy` → same
- `DiscoveryController@destroyAll` → change to iterate and soft-delete instead of `->delete()` mass query
- Update user-facing messaging: "deleted" → "moved to trash" or simply remove from view

#### 2C. User Account Deletion
- When user deletes their own account: soft-delete User record, keep `deactivated_at` as the access-blocking mechanism
- Organization remains intact with its data
- User can potentially be restored by admin

### Phase 3: Admin Soft-Delete Management UI

#### 3A. Filament "Trash" Views
For each resource that supports soft-delete, add:
- **Trash filter** on the table: toggle to show only soft-deleted records (`TrashedFilter`)
- **Restore action**: one-click restore (un-soft-delete) with cascade restore of children
- **Force Delete action**: permanent deletion (admin-only, requires confirmation + reason)

Filament v5 has built-in support via:
```php
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
```

#### 3B. Dedicated "Trash / Archived Records" Admin Page (Optional)
A unified admin page showing all soft-deleted records across all models:
- Tabbed interface: Users | Organizations | Projects | Scans | Pages
- Each tab shows soft-deleted records with restore/force-delete actions
- Searchable, sortable, filterable by deletion date

#### 3C. Audit Logging
Log all soft-delete and restore operations to `AuditLog`:
- `*.soft_deleted` — who deleted, when, what
- `*.restored` — who restored, when, what
- `*.force_deleted` — who permanently deleted, when, what, reason

### Phase 4: Access Control & Edge Cases

#### 4A. Query Scoping
With `SoftDeletes`, Eloquent automatically excludes soft-deleted records from queries. Verify:
- Dashboard counts exclude soft-deleted records
- Plan limit checks exclude soft-deleted projects/scans
- User's project list excludes soft-deleted projects
- Subscription usage calculations exclude soft-deleted records

#### 4B. Deactivation vs Soft Delete Clarification
- `deactivated_at` (User/Organization) = account suspended, data visible to admin, user blocked from app
- `deleted_at` (SoftDeletes) = record "deleted", hidden from all normal queries, visible only in trash views
- Both can coexist: a deactivated org is still queryable; a soft-deleted org requires `withTrashed()`

#### 4C. Stripe/Billing Considerations
- Soft-deleting an Organization should NOT cancel their Stripe subscription automatically
- Admin must manually cancel subscription before or after soft-delete
- Restoring an org does NOT auto-resume subscription

#### 4D. Unique Constraints
- `organizations.slug` has unique constraint — soft-deleted org's slug still occupies the unique space
- Solution: either scope unique constraint to non-deleted (`whereNull('deleted_at')`) or append suffix on soft-delete
- Same applies to `users.email` if unique

## Files to Modify

| File | Change |
|------|--------|
| New migration | Add `deleted_at` to 12 tables, change FK cascades |
| All 12+ models | Add `SoftDeletes` trait |
| `OrganizationResource.php` | Add TrashedFilter, RestoreAction, ForceDeleteAction |
| `UserResource.php` | Add TrashedFilter, RestoreAction, ForceDeleteAction |
| `PlanResource.php` | Add TrashedFilter, RestoreAction, ForceDeleteAction + validation |
| `CouponResource.php` | Add TrashedFilter, RestoreAction, ForceDeleteAction |
| `ProjectController.php` | Verify soft-delete works, update flash messages |
| `CompetitorController.php` | Verify soft-delete works |
| `ProjectPageController.php` | Verify soft-delete works |
| `DiscoveryController.php` | Change mass delete to soft-delete |
| `ScanResource.php` | Add TrashedFilter for admin scan management |
| New: Trash admin page (optional) | Unified view of all soft-deleted records |

## Estimated Effort
- Phase 1 (infrastructure): Medium — migration + model traits + cascade setup
- Phase 2 (replace hard deletes): Small — mostly automatic with SoftDeletes trait
- Phase 3 (admin UI): Medium — Filament trash filters + restore/force-delete actions
- Phase 4 (edge cases): Small-Medium — query scoping verification + unique constraints

## Dependencies
- None — can be implemented independently
- Optional: `dyrynda/laravel-cascade-soft-deletes` package

## Risks
- Unique constraint conflicts with soft-deleted records (slug, email)
- Cascade soft-delete depth (org → project → scan → results) needs thorough testing
- Existing data has no `deleted_at` column — migration is additive (all NULL = not deleted)
- Performance: `withTrashed()` queries on large tables may need index on `deleted_at`
