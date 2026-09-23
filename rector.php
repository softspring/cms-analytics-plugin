<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Symfony73\Rector\Class_\GetFiltersAndFunctionsToAsTwigAttributeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withComposerBased(symfony: true)
    // The package supports Twig 3.4 through cms-bundle; Twig attributes need a newer Twig release.
    ->withSkip([
        GetFiltersAndFunctionsToAsTwigAttributeRector::class,
    ]);
