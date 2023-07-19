<?php

return (new PhpCsFixer\Config())->setRules([
    '@PSR12' => true,
    '@Symfony' => true,
    'declare_strict_types' => true,
])
    ->setFinder(PhpCsFixer\Finder::create()->in(['src', 'tests']))
    ->setRiskyAllowed(true)
    ;