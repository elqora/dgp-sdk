# Agent guidance: DGP SDK

Read and follow `../AGENTS.md` before working in this repository.

## Role and transitional authority

This repository is the framework-neutral PHP backend toolkit for DGP handlers. It is authoritative for handler services, capabilities, runtime plans, rates, charges, deliveries, actions, and fulfillment only where the workspace contract and `dgp-spec` have not yet ratified the corresponding protocol contract.

Once Spec ratifies a contract, this SDK must conform. Divergence is a conformance defect, and the SDK must not preserve an incompatible legacy wire shape as a second authority.

## Dependencies and boundaries

- Implement ratified `dgp-spec` contracts in PHP and verify them against shared conformance fixtures.
- Preserve handler ownership of service catalogs, rate logic, final pricing, charges, and workflow progression.
- Preserve host ownership of persistence, routing, rendering, payments, and infrastructure ports.
- Do not depend on Core, Validation, Ordering, the Form Palette adapter, Workspace, Studio, React, or frontend rendering.
- Browser JavaScript expressions and advisory utility calculations are frontend contracts; this SDK is not required to execute them or trust them as authoritative pricing.
- Validate order inputs and determine authoritative rates, prices, charges, and fulfillment behavior on the backend.
- Canonical `meta` is an opaque host-defined JSON object. Do not require `{raw, derived}` as universal wire structure after the relevant contract is ratified.
- Use canonical `capabilities`; do not add frontend `flags`, root capability booleans, or `estimates`.

## Clean-break rule

DGP v1 conformance does not require legacy adapters, aliases, deprecated fields, compatibility modes, or support for old frontend definitions. Existing implementation details are evidence for unratified behavior, not automatic protocol contracts.

## References

- Spec authority: sibling `../dgp-spec`.
- This clone at `D:\Projects\GitHub\elqora\digital-goods-protocol\dgp-sdk` is the only local SDK authority for this workspace.
- Legacy frontend evidence: `D:\Projects\GitHub\digital-service-ui-builder`.
- Studio source evidence: `D:\Projects\GitHub\service-builder`; destination: sibling `../dgp-studio`.
- Siblings: `../dgp-core`, `../dgp-validation`, `../dgp-ordering`, `../dgp-ordering-form-palette`, and `../dgp-workspace`.

This repository remains AGPL-3.0-only.
