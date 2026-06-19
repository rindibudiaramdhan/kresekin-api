# ADR-007: Google SSO with JIT provisioning; manual role assignment

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

Authentication is Google Workspace SSO (OIDC) only — no local passwords. Users are provisioned just-in-time: first successful login creates the user with the default role `Requester`. Admins assign and revoke elevated roles and assignment-group memberships manually through the admin UI.

## Context

- Infokes runs on Google Workspace; SSO is mandatory (ISO 27001 access-control posture).
- Automatic Google Group → role mapping was considered but adds sync complexity and a second source of truth for permissions. At ~300 users with ~120 agent-role users, manual role administration is manageable.

## Consequences

- No user-import step at launch; the directory populates itself on first login.
- Deactivation: disabling the Google account blocks login; admins can additionally deactivate users in-app. Historical records always retain the user reference — users are never deleted.
- Google Group → role auto-sync remains a future consideration; all role changes are audited either way.
