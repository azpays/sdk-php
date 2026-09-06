<?php

declare(strict_types=1);

namespace AzPays\Services;

use AzPays\Http\Transport;

abstract class AbstractService
{
    protected Transport $transport;

    public function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }
}
