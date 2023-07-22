<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use SlevomatCodingStandard\Sniffs\ControlStructures\EarlyExitSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\DisallowGroupUseSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\FullyQualifiedGlobalFunctionsSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\ReferenceUsedNamesOnlySniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $ecsConfig->rules([
        NoUnusedImportsFixer::class,
        DeclareStrictTypesFixer::class,
        GlobalNamespaceImportFixer::class,
    ]);

    $ecsConfig->ruleWithConfiguration(EarlyExitSniff::class, [
        'ignoreStandaloneIfInScope' => true,
        'ignoreOneLineTrailingIf' => true,
    ]);

    $ecsConfig->sets([
         SetList::SPACES,
         SetList::ARRAY,
         SetList::DOCBLOCK,
         SetList::COMMENTS,
         SetList::PSR_12,
    ]);

    $ecsConfig->skip([
        OrderedImportsFixer::class,
        NotOperatorWithSuccessorSpaceFixer::class,
        PhpdocLineSpanFixer::class,
    ]);
};
