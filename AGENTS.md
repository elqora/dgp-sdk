# Agent guidance: DGP SDK

Read and follow `../AGENTS.md` before working in this repository.

## Role

This repository is the framework-neutral backend protocol and PHP toolkit for DGP handlers. It remains the current authority for handler services, capabilities, runtime plans, charges, deliveries, actions, and fulfillment while `dgp-spec` is established.

## Boundaries

- Preserve handler ownership of service catalogs, pricing logic, charges, and workflow progression.
- Preserve host ownership of persistence, routing, rendering, payments, and infrastructure ports.
- Do not import frontend rendering, ordering UI, Studio, or editorial concerns.
- Browser JavaScript expressions used for quantity and customer-field evaluation are frontend contracts; this SDK is not required to execute them.
- Keep service `meta` host-dependent; do not require a universal frontend interpretation.
- Coordinate wire-contract changes with sibling `dgp-spec` and shared conformance fixtures.

## References

- This sibling clone at `D:\Projects\GitHub\elqora\digital-goods-protocol\dgp-sdk` is the only local SDK authority for this workspace.
- Legacy frontend migration source: `D:\Projects\GitHub\digital-service-ui-builder`.
- Current Studio reference: `D:\Projects\GitHub\service-builder`.
- Sibling repositories: `../dgp-spec`, `../dgp-core`, `../dgp-validation`, `../dgp-ordering`, and `../dgp-workspace`.
- Existing SDK documentation in this repository remains authoritative for current runtime behavior.
