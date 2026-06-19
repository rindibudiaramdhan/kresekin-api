# ADR-003: Use OTP login with bearer session tokens

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Use OTP-based authentication and bearer session tokens for API users. Session tokens are stored server-side as hashes and enforced through the `session.token` middleware.

## Context

- The active API supports role-specific register, login, resend OTP, verify OTP, refresh session, logout, and device registration endpoints.
- User roles include buyer, seller, agent, and finance.
- Password login is not required for the API MVP, and agent registration already depends on OTP verification.
- Bearer token authentication is easier for mobile/API clients than cookie-based web sessions.

## Consequences

- OTP expiry, resend limit, attempt limit, and sensitive logging controls are security-critical.
- Token revocation is possible because sessions are stored server-side.
- Client applications must treat bearer tokens as credentials and store them carefully.
- Any future password or SSO flow must integrate with the same session boundary or define a new ADR.
