<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function str_contains;

/**
 * C8 — `?->` in the domain is evidence of a null that should not exist.
 *
 * C2 says a module's published surface answers with a type rather than with
 * null, so inside the kernel and a capability there is nothing for `?->` to
 * guard against. Writing one is either dead defence or an admission that a null
 * arrived anyway — and the operator sees the second case as a screen that
 * renders with a value missing and says nothing about why.
 *
 * Adapters keep it. A foreign library's null is a real null, and the adapter is
 * the layer whose job is turning one into something the domain can name.
 *
 * @implements Rule<Expr>
 */
final class NoNullsafeInDomainRule implements Rule
{
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof NullsafeMethodCall && ! $node instanceof NullsafePropertyFetch) {
            return [];
        }

        if (! $this->isDomain($scope->getFile())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'C8 — nothing in the domain answers with null, so there is nothing here for '
                . '?-> to guard against. Either it is defending against a case that cannot '
                . 'happen, or a null got in where C2 says one cannot — and that second one '
                . 'reaches the operator as a screen missing a value with no explanation. An '
                . 'adapter is where a foreign null legitimately arrives, and turning it '
                . 'into something the domain can name is that adapter\'s job (C8, C2).',
            )
                ->identifier('lemonfiber.nullsafeInDomain')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * Whether this file is domain code.
     *
     * Judged by path rather than by the module manifest because a PHPStan rule
     * runs per file with no view of the repository; the manifests are what the
     * architecture tests read, and the two agree because a capability module's
     * files are the ones under a module that is not an adapter. The list here is
     * the kernel plus the five capability modules, which is the set A7 governs.
     */
    private function isDomain(string $file): bool
    {
        foreach (['/kernel/src/', '/connection/src/', '/stacks/src/', '/health/src/', '/backups/src/', '/updates/src/'] as $domain) {
            if (str_contains($file, $domain)) {
                return true;
            }
        }

        return false;
    }
}
