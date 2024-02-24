<?php

namespace Doefom\StatamicExport;

use Doefom\StatamicExport\Actions\Export;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon(): void
    {

        Export::register();

    }
}
