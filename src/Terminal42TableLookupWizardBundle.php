<?php

declare(strict_types=1);

namespace Terminal42\TableLookupWizardBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42TableLookupWizardBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
