<?php declare(strict_types=1);

namespace QueryBuilderBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class QueryBuilderBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    /**
     * @return string
     */
    protected function getComposerPackageName(): string
    {
        return 'carbdrox/pimcore-querybuilder-bundle';
    }
}
