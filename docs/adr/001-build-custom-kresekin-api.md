# ADR-001: Build custom Kresekin API

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Build and maintain a custom Laravel backend for Kresek.in instead of adopting a generic marketplace, POS, CRM, or finance platform as the core system.

## Context

- Kresek.in combines buyer checkout, seller/UMKM operations, agent-managed UMKM performance, commission withdrawal, and finance disbursement workflow.
- The current codebase already models these domains directly through Laravel routes, controllers, models, migrations, seeders, and feature tests.
- A generic SaaS product would still require custom glue for agent commission, housing area coverage, OTP login, local UMKM catalog, and finance-facing disbursement states.
- API compatibility matters because web, mobile, and internal dashboards may consume the same endpoints.

## Consequences

- Business rules can be expressed directly in the API and database schema.
- The team owns maintenance, security, API compatibility, and production operations.
- Requirement docs and ADRs become part of the control system for avoiding uncontrolled scope growth.
