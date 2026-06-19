# ADR-006: Change Enablement ships after CMDB

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

Release order is: CMDB first, then Change Enablement (both in R3, CMDB built first). Change records reference CIs and use CI relationships for impact assessment.

## Context

- Change approval without CI data is a glorified approval form: approvers cannot see what a change affects.
- Conversely, a CMDB whose data no process consumes goes stale within months. Change Enablement (and incident linkage) are the forcing functions that keep CI data alive.

## Consequences

- R3 internal ordering: CI classes, relationships, and initial population precede the change workflow.
- Change impact assessment can show "what depends on this CI" from the first change record onward.
