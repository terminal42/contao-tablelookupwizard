<?php

namespace Terminal42\ContaoTableLookupWizard\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Terminal42\ContaoTableLookupWizard\Terminal42ContaoTableLookupWizard;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(Terminal42ContaoTableLookupWizard::class)
                ->setLoadAfter([ContaoCoreBundle::class, 'haste'])
                ->setReplace(['tablelookupwizard'])
        ];
    }
}
