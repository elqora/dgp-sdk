<?php

namespace Elqora\Dgp\Manifest;

enum Capability: string
{
    case CANCELLATION = 'cancellation';
    case SYNCHRONIZATION = 'synchronization';
    case CHARGES = 'charges';
    case UI_CONTRIBUTIONS = 'ui_contributions';
    case SERVICE_SCHEMA_CATALOG = 'service_schema_catalog';
    case WEBHOOKS = 'webhooks';
    case PRIVATE_ASSETS = 'private_assets';
}
