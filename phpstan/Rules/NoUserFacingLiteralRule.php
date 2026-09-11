<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function preg_match;
use function str_contains;

/**
 * L1 — text a person reads comes from the translator.
 *
 * The application ships `en` and `nl`. A sentence written into a presenter, a
 * view model or a screen is English for everyone, and no parity check can see
 * it because it is not a key in either catalogue — it is not in the catalogue
 * at all.
 *
 * The line this draws is between text a person reads and text a developer
 * reads. A refusal on screen, an empty state, a notification body: translated.
 * An exception message, a log line, the message in this rule: not translated,
 * built with `sprintf`, and read by someone who chose to work in English. The
 * rule needs no list to tell them apart, because it only looks in the three
 * places where a string is on its way to a screen — a presenter, a view model
 * and a screen do not throw and do not log.
 *
 * A translation key is left alone without an exemption for `__()`: keys look
 * like `health.unreachable` and sentences have spaces in them, so what the rule
 * flags is prose by construction. EDGE utility classes are all lowercase, which
 * is why a capital letter is required too.
 *
 * @implements Rule<String_>
 */
final class NoUserFacingLiteralRule implements Rule
{
    /** Directories a string passes through on its way to a screen. */
    private const array RENDERING_PATHS = ['/Presenters/', '/ViewModels/', '/Screens/'];

    public function getNodeType(): string
    {
        return String_::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->rendersToAScreen($scope->getFile())) {
            return [];
        }

        if (! $this->readsAsProse($node->value)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'L1 — text a person reads comes from the translator. This application '
                . 'ships en and nl, so a sentence written here is English on a Dutch '
                . "device and no parity check can see it, because it is in neither\n"
                . 'catalogue. Put it in lang/<locale>/<module>.php and call '
                . "__('module.key') — with a replacement array where a value goes in, "
                . 'never a concatenation. Text a developer reads — an exception message, '
                . 'a log line — is not translated, and does not belong in a presenter '
                . 'either (L1, H5).',
            )
                ->identifier('lemonfiber.userFacingLiteral')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function rendersToAScreen(string $file): bool
    {
        foreach (self::RENDERING_PATHS as $path) {
            if (str_contains($file, $path)) {
                return true;
            }
        }

        return false;
    }

    /** Two words and a capital: a sentence, rather than a key or a class list. */
    private function readsAsProse(string $value): bool
    {
        return preg_match('/\p{Lu}/u', $value) === 1
            && preg_match('/\p{L}{2,}\s\p{L}{2,}/u', $value) === 1;
    }
}
