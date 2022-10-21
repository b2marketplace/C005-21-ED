<?php

namespace App\Services\AmazonCatalog\Exceptions;

class AmazonCatalogThrottledException extends AmazonCatalogException
{
    // Exception for throttling (HTTP 429)
}
