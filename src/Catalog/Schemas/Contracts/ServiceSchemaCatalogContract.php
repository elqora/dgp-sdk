<?php

namespace Elqora\Dgp\Catalog\Schemas\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Catalog\Schemas\ServiceSchemaQuery;

interface ServiceSchemaCatalogContract
{
     /**
      * Get the service schemas matching the query.
      *
      * @param ServiceSchemaQuery $query
      * @return Result<list<\Elqora\Dgp\Catalog\Schemas\ServiceSchemaDefinition>>
      */
     public function schemas(ServiceSchemaQuery $query): Result;
}
