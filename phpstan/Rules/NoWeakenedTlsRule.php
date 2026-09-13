<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function in_array;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function strtolower;

/**
 * S3 — certificate verification is never turned off.
 *
 * ADR-0018 pins a stack's certificate by a fingerprint taken from the pairing
 * material, which is what makes a self-hosted stack on a home network safe to
 * talk to without a public certificate authority. A global verify-off switch
 * defeats that silently: the pin is still there, still compared, and no longer
 * reached, because nothing got as far as checking it.
 *
 * The switch has several spellings and they all look like configuration. Guzzle
 * takes `'verify' => false`, a stream context takes `'verify_peer' => false` and
 * `'allow_self_signed' => true`, and curl takes `CURLOPT_SSL_VERIFYPEER => 0`.
 * Each is one line in an options array that a reader skims past, which is why
 * this is a rule rather than a review note.
 *
 * @implements Rule<ArrayItem>
 */
final class NoWeakenedTlsRule implements Rule
{
    /**
     * Options that switch verification off when they are false.
     *
     * The spellings come from `Settings::ABOUT_VERIFICATION`, which is the
     * other half of this requirement — it reads configuration key names, this
     * reads call options — and knew five this did not: `verify_ssl`,
     * `verify_tls`, `verify_cert`, `ssl_verify` and `tls_verify`. Every one is
     * an option key a real client accepts, so each was a line that turned
     * verification off with nothing looking at it.
     *
     * Neither file had named the other, which is how two lists about one
     * security property drift. `NothingTurnsVerificationOffTest` holds the
     * vocabulary now and reports any spelling no gate refuses.
     */
    private const array OFF_WHEN_FALSE = [
        'verify', 'verify_peer', 'verify_peer_name', 'verify_host',
        'verify_ssl', 'verify_tls', 'verify_cert', 'ssl_verify', 'tls_verify',
        'verify_expiry',
        'curlopt_ssl_verifypeer', 'curlopt_ssl_verifyhost', 'ssl_verifypeer', 'ssl_verifyhost',
    ];

    /**
     * Options that accept a weaker chain when they are true.
     *
     * `skip_verify` and `no_verify` read the other way round from everything
     * above — the switch is on when the option is true — which is exactly why a
     * reader skims past one. Same list, opposite polarity, and getting the
     * polarity wrong would make this rule refuse the safe spelling and pass the
     * dangerous one.
     *
     * Which is what happened. `verify_expiry` sat here, and it reads like every
     * name in the other list: `verify_expiry => true` asks for the expiry to be
     * checked and `=> false` waives it. So this refused the spelling that is
     * safe and passed the spelling that is not — the failure the paragraph
     * above describes, in the list the paragraph is attached to. It has moved.
     */
    private const array OFF_WHEN_TRUE = [
        'allow_self_signed', 'insecure', 'skip_verify', 'no_verify',
    ];

    public function getNodeType(): string
    {
        return ArrayItem::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $key = $this->keyOf($node);

        if ($key === null) {
            return [];
        }

        $weakens = (in_array($key, self::OFF_WHEN_FALSE, strict: true) && $this->isFalsy($node->value, $scope))
            || (in_array($key, self::OFF_WHEN_TRUE, strict: true) && $this->isTruthy($node->value, $scope));

        if (! $weakens) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'S3 — this turns certificate verification off, and the pairing pin is '
                . 'what stands behind it. A stack is trusted by a fingerprint taken from '
                . 'the material an operator scanned, not by a public authority, so %s '
                . 'does not relax a check — it removes the only one there is, and the pin '
                . 'goes on being compared by code nothing reaches. If a certificate is '
                . 'being refused, the fingerprint is the thing to look at (S3, '
                . 'ADR-0018).',
                $key,
            ))
                ->identifier('lemonfiber.weakenedTls')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /** The option's name, lowercased, whether written as a string or a constant. */
    private function keyOf(ArrayItem $item): ?string
    {
        if ($item->key instanceof String_) {
            return strtolower($item->key->value);
        }

        return $item->key instanceof ConstFetch ? strtolower($item->key->name->toString()) : null;
    }

    /**
     * Whether every value this expression can have is falsy.
     *
     * Asked of the analyser rather than of the literal. Reading the node
     * directly saw `false` and `0` and nothing else: `'0'`, `''` and `null` are
     * each falsy to every client here and each passed, as did `$off = false`
     * one line above the array and a class constant holding the same. None of
     * those is a contrived spelling — a verification flag read from
     * configuration arrives as a string, and `'0'` is what a `.env` holds.
     *
     * `yes()` rather than `maybe()`, so a value the analyser cannot pin down is
     * not reported. A `bool` of unknown value is the ordinary case for a flag
     * that is genuinely configurable, and refusing it would make this rule the
     * one somebody switches off.
     */
    private function isFalsy(Expr $value, Scope $scope): bool
    {
        return $scope->getType($value)->toBoolean()->isFalse()->yes();
    }

    /** Whether every value this expression can have is truthy. */
    private function isTruthy(Expr $value, Scope $scope): bool
    {
        return $scope->getType($value)->toBoolean()->isTrue()->yes();
    }
}
