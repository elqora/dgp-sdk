<?php

namespace Elqora\Dgp\Manifest;

enum Capability: string
{
    case CANCELLATION = 'cancellation';
    case SYNCHRONIZATION = 'synchronization';
    case CHARGES = 'charges';
    case UI_CONTRIBUTIONS = 'ui_contributions';
    case PRODUCT_DEFINITION_CATALOG = 'product_definition_catalog';
    case WEBHOOKS = 'webhooks';
    case PRIVATE_ASSETS = 'private_assets';
    case SERVICE_INSIGHTS = 'service_insights';
    case AUDITS = 'audits';
}
