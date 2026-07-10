<?php

namespace Elqora\Dgp\Contracts;

use Elqora\Dgp\Manifest\Contracts\ManifestContract;
use Elqora\Dgp\Manifest\Contracts\ConfigSchemaContract;
use Elqora\Dgp\Health\Contracts\HealthContract;
use Elqora\Dgp\Balance\Contracts\BalanceContract;
use Elqora\Dgp\Catalog\Services\Contracts\ServicesContract;
use Elqora\Dgp\Runtime\Contracts\RuntimeContract;
use Elqora\Dgp\Deliveries\Contracts\OrderDeliveriesContract;
use Elqora\Dgp\Management\Contracts\OrderManagementContract;
use Elqora\Dgp\Actions\Contracts\GenericActionContract;
use Elqora\Dgp\Bulk\Contracts\BulkActionContract;

interface DgpDriverContract extends
    ManifestContract,
    ConfigSchemaContract,
    HealthContract,
    BalanceContract,
    ServicesContract,
    RuntimeContract,
    OrderDeliveriesContract,
    OrderManagementContract,
    GenericActionContract,
    BulkActionContract
{
}
