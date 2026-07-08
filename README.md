# DGP SDK

DGP SDK is a framework-neutral plugin protocol for digital-product systems. Implementations expose capabilities, order kinds, schemas, workflow behavior, deliveries, progress, next actions, management data, and provider integration through stable contracts.

DGP is designed as a framework-neutral core with minimal framework-neutral dependencies.

## Configuration Schema & Validation

DGP integrates `elqora/config-kit` for portable configuration schemas, configuration values, validation results, public projections, and log redaction:
- **ConfigSchemaContract** extends `Elqora\ConfigKit\Contracts\ProvidesConfigSchema`.
- DGP handlers declare and expose their configuration fields using Config Kit's `ConfigSchema` and `UiConfigSchema` structures.
- Configuration values are passed to handlers using `ConfigBag`, preserving distinct scopes for ordinary options, secrets, and sandbox/live modes.
- The host remains responsible for configuration persistence and application-specific configuration management.
