<?php

declare(strict_types=1);

namespace Terminal42\TableLookupWizardBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('initializeSystem')]
readonly class AddAssetsListener
{
    public function __construct(
        private Packages $packages,
        private RequestStack $requestStack,
        private ScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request || !$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        $GLOBALS['TL_JAVASCRIPT'][] = $this->packages->getUrl('tablelookupwizard.js', 'terminal42_table_lookup_wizard');
        $GLOBALS['TL_CSS'][] = $this->packages->getUrl('tablelookupwizard.css', 'terminal42_table_lookup_wizard');
    }
}
