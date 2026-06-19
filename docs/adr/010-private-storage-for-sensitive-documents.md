# ADR-010: Store sensitive documents privately

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Store sensitive identity documents on private storage and avoid exposing raw sensitive paths through public API responses.

## Context

- Agent registration collects identity document data and stores an `identity_document_path`.
- Product images are catalog assets, but identity documents are private verification material.
- The codebase uses Laravel filesystem/Flysystem and includes S3 support.

## Consequences

- Upload validation must distinguish public catalog images from private identity documents.
- API responses should avoid leaking private disk paths.
- Future admin review screens should access documents through authorized, temporary, auditable mechanisms.
