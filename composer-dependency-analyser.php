<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // These are optional (suggested) dependencies used unconditionally in opt-in traits;
    // consumers who don't `use` the trait don't need the package. See the "suggest" section
    // in composer.json.
    ->ignoreErrorsOnPackages(
        ['yiisoft/arrays', 'yiisoft/event-dispatcher', 'yiisoft/factory'],
        [ErrorType::DEV_DEPENDENCY_IN_PROD],
    );
