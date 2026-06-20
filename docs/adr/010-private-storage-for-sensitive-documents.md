# ADR-010: Store sensitive documents privately

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Store sensitive identity documents on private storage and avoid exposing raw sensitive paths through public API responses.

In production on Laravel Cloud, private storage must be backed by durable object storage through Laravel filesystem/Flysystem. Sensitive documents must not depend on the application container's local filesystem for persistence.

## Context

- Agent registration collects identity document data and stores an `identity_document_path`.
- Product images are catalog assets, but identity documents are private verification material.
- The codebase uses Laravel filesystem/Flysystem and includes S3 support.
- Laravel Cloud application filesystems are ephemeral, so runtime uploads require durable storage.

## Consequences

- Upload validation must distinguish public catalog images from private identity documents.
- API responses should avoid leaking private disk paths.
- Future admin review screens should access documents through authorized, temporary, auditable mechanisms.
- Production storage configuration must come from Laravel Cloud environment variables or attached storage resources.
