# Agent guidance: DGP SDK

Read and follow `../AGENTS.md` before working in this repository.

## Role and authority

This repository is the framework-neutral PHP backend toolkit, backend domain authority, and reference implementation for DGP handlers. It owns the semantics and improved terminology of handler services, capabilities, runtime plans, rates, charges, deliveries, actions, and fulfillment.

Spec owns the canonical plain shared and wire representation of reconciled decisions; it does not silently replace SDK-owned backend semantics. If Spec represents established SDK semantics incorrectly, Spec is defective. If this SDK serializes a ratified shared contract incorrectly, the SDK binding is defective. Intentional domain changes require coordinated SDK and Spec decisions.

Ratified means the versioned plain TypeScript contract, required JSON fixtures, rationale, and stable status are merged into `dgp-spec/main`; generated JSON Schemas must also be current once tooling exists. Released means that ratified Spec version is tagged and published. SDK conformance may target a ratified unreleased contract during coordinated work, but a stable SDK release requires the corresponding released Spec version.

## Dependencies and boundaries

- Represent ratified shared contracts in PHP and verify lossless hydration and serialization against Spec fixtures while preserving SDK-owned domain semantics.
- Preserve handler ownership of service catalogs, rate logic, final pricing, charges, and workflow progression.
- Preserve host ownership of persistence, routing, rendering, payments, and infrastructure ports.
- Do not depend on Core, Validation, Ordering, the Form Palette adapter, Workspace, Studio, React, or frontend rendering.
- Browser JavaScript expressions and advisory utility calculations are frontend contracts; this SDK is not required to execute them or trust them as authoritative pricing.
- Validate order inputs and determine authoritative rates, prices, charges, and fulfillment behavior on the backend.
- Canonical `meta` is an opaque host-defined JSON object. SDK helpers may distinguish raw and derived data internally, but must not make `{raw, derived}` universal wire structure unless that plain shape is deliberately ratified.
- Use canonical `capabilities`; do not add frontend `flags`, root capability booleans, or `estimates`.

## Clean-break rule

DGP v1 conformance does not require legacy adapters, aliases, deprecated fields, compatibility modes, or support for old frontend definitions. This representation clean break does not authorize the loss of proven backend behavior or incomplete implementation of ratified shared contracts.

## Migration and conformance completeness

- Preserve SDK-owned handler, service, capability, runtime, rate, charge, plan, delivery, action, and fulfillment semantics by default. Intentional redesign requires explicit recorded user approval and coordinated Spec work when shared representation changes.
- Implement complete structural conformance for every applicable ratified Spec contract. A hand-written validator covering only selected fields or fixtures is not sufficient.
- Consume and verify every applicable valid, invalid, round-trip, and semantic Spec fixture. Missing fixture coverage is **pending conformance**, not implicit acceptance or retirement.
- Keep backend validation of order inputs and authoritative pricing complete while accepting that browser-only expression execution remains outside this SDK.
- Do not call an SDK binding conformant or publish another stable release while applicable ratified fixtures, nested wire rules, or lossless round trips remain unimplemented.

## Change workflow and operations

- Start SDK-owned domain changes here and coordinate any shared wire impact with Spec. After ratification, align SDK serialization alongside corresponding Core work before dependent releases.
- Commit and release this repository independently.
- The current PHP toolchain is real: install with `composer install`; run tests with `composer test`, static analysis with `composer analyse`, contract and dependency drift checks with `composer check:contracts`, and the full completion check with `composer check`.
- Spec conformance fixtures under `tests/Fixtures/Contracts` are committed interoperability evidence. `composer check:contracts` must cover every applicable ratified fixture and compare it with a sibling `dgp-spec` checkout when one is present; a selected subset is not complete conformance.
- No generated PHP protocol binding exists. The SDK's DTOs remain hand-authored language bindings and their fixture round trips mechanically guard canonical-field drift.

## References

- Spec authority: sibling `../dgp-spec`.
- Shared-contract guide: sibling `../dgp-spec/CONTRACTS.md`.
- This clone at `D:\Projects\GitHub\elqora\digital-goods-protocol\dgp-sdk` is the only local SDK authority for this workspace.
- Legacy frontend evidence: `D:\Projects\GitHub\digital-service-ui-builder`.
- Studio source evidence: `D:\Projects\GitHub\service-builder`; destination: sibling `../dgp-studio`.
- Siblings: `../dgp-core`, `../dgp-validation`, `../dgp-ordering`, `../dgp-ordering-form-palette`, and `../dgp-workspace`.

This repository remains AGPL-3.0-only.
