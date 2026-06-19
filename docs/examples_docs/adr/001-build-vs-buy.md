# ADR-001: Build custom ITSM instead of buying

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

Build an in-house ITSM platform rather than licensing a commercial product or adopting an open-source one.

## Context

- Zoho Desk (current) has poor usability and is not structured around ITIL practices.
- Jira Service Management fits functionally (and serves as our UX reference) but per-agent licensing is not viable at Indonesian purchasing power for ~120 agent-role users.
- Open-source options (GLPI, iTop, Znuny) do not match the desired UX or our tech stack.
- Builder is an AI agent, which significantly lowers build cost; maintenance stays in-house on a stack the team already runs.

## Consequences

- Zero license cost; full fit to Infokes processes; ISO 27001 audit requirements designed in from the start.
- Maintenance is forever: mitigated by using the existing boring stack (see ADR-009).
- Jira SM remains the UX benchmark when designing screens and flows.
