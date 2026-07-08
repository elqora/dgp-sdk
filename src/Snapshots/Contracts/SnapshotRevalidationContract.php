<?php

namespace Elqora\Dgp\Snapshots\Contracts;

use Elqora\Dgp\Errors\Result;

interface SnapshotRevalidationContract
{
     /**
      * Revalidate a snapshot against authoritative published ServiceProps revision,
      * current service state, and host policies.
      *
      * @param mixed $request
      * @return Result<null>
      */
     public function revalidate(mixed $request): Result;
}
