<?php

namespace Elqora\Dgp\Catalog\Services\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Catalog\Services\ServiceQuery;

interface ServicesContract
{
     /**
      * Get the list of services matching the query.
      *
      * @param ServiceQuery $query
      * @return Result<list<\Elqora\Dgp\Catalog\Services\HandlerService>>
      */
     public function services(ServiceQuery $query): Result;
}
