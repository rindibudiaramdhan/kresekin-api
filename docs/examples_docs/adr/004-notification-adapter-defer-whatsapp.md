# ADR-004: Notification adapter interface; ship Email + Mattermost, defer WhatsApp

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

Notifications go through a channel-adapter interface. R1 ships two adapters: **Email** (AWS SES) and **Mattermost** (the chat tool Infokes uses daily). WhatsApp and Google Chat are deferred; adding them later means writing an adapter, not redesigning notification logic.

## Context

- Candidate channels were WhatsApp, Mattermost, Email, Google Chat. Shipping four channels in MVP is a trap.
- WhatsApp specifically: Meta Business API requires approval, per-message cost, and pre-approved templates; unofficial gateways violate ToS and break regularly.
- Email-only notifications die unread; one chat channel is mandatory for adoption.

## Consequences

- Notification logic (who gets notified, when, digest vs instant) is channel-agnostic from day 1.
- WhatsApp/Google Chat become small, additive work items if ever prioritized.
