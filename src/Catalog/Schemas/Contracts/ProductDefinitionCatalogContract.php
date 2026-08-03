<?php

namespace Elqora\Dgp\Catalog\Schemas\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Catalog\Schemas\ProductDefinitionQuery;

interface ProductDefinitionCatalogContract
{
     /**
      * Get the product definitions matching the query.
      *
      * @param ProductDefinitionQuery $query
      * @return Result<list<\Elqora\Dgp\Catalog\Schemas\ProductDefinition>>
      */
     public function definitions(ProductDefinitionQuery $query): Result;
}
