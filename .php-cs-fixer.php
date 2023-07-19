<?php

return (new PhpCsFixer\Config())->setRules([
    '@PSR12' => true,
    'declare_strict_types' => true,
    'global_namespace_import' => true,
])
    ->setFinder(PhpCsFixer\Finder::create()->in(['src', 'tests']))
    ->setRiskyAllowed(true);