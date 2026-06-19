# Kresekin API ADR

ADR atau Architecture Decision Record mencatat keputusan arsitektur/produk-teknis penting, alasan pengambilan keputusan, dan konsekuensinya.

| ADR | Keputusan |
| --- | --- |
| [001](001-build-custom-kresekin-api.md) | Build custom Kresekin API |
| [002](002-modular-monolith-laravel.md) | Use a Laravel modular monolith |
| [003](003-use-otp-session-token-auth.md) | Use OTP login with bearer session tokens |
| [004](004-role-based-access-with-ownership-scoping.md) | Use role-based access with ownership scoping |
| [005](005-manual-agent-review-first.md) | Keep agent verification review manual first |
| [006](006-finance-workflow-manual-first.md) | Keep finance payout workflow manual-first |
| [007](007-snapshot-transaction-data.md) | Snapshot transaction data at checkout |
| [008](008-server-side-financial-calculation.md) | Calculate money and commission server-side |
| [009](009-tech-stack.md) | Tech stack |
| [010](010-private-storage-for-sensitive-documents.md) | Store sensitive documents privately |
| [011](011-bps-region-as-external-boundary.md) | Keep Indonesia region lookup behind a service boundary |
| [012](012-configuration-driven-domain-values.md) | Use constants and configuration for domain values |
| [013](013-dashboard-aggregation-in-support-classes.md) | Keep dashboard aggregation in support classes |

## Kapan Membuat ADR Baru

Buat ADR baru ketika keputusan berdampak lintas fitur, sulit dibalik, atau mempengaruhi keamanan, data, deployment, integrasi, biaya, atau kontrak API. Perubahan kecil di controller atau bug fix biasa cukup dicatat di task/PR.
