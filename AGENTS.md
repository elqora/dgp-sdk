# Agent guidance: DGP SDK

Read and follow `../AGENTS.md` before working in this repository.

## Role

This repository is the framework-neutral backend protocol and PHP toolkit for DGP handlers. It remains the current authority for handler services, capabilities, runtime plans, charges, deliveries, actions, and fulfillment while `dgp-spec` is established.

## Boundaries

- Preserve handler ownership of service catalogs, pricing logic, charges, and workflow progression.
- Preserve host ownership of persistence, routing, rendering, payments, and infrastructure ports.
- Do not import frontend rendering or editorial concerns.
- Keep service `meta` host-dependent; do not require a universal frontend interpretation.
- Coordinate wire-contract changes with sibling `dgp-spec` and shared conformance fixtures.

## References

- Legacy frontend migration source: `D:\Projects\GitHub\digital-service-ui-builder`.
- Sibling repositories: `dgp-spec`, `dgp-core`, `dgp-validation`, `dgp-ordering`, and `dgp-workspace`.
- Existing SDK documentation in this repository remains authoritative for current runtime behavior.
