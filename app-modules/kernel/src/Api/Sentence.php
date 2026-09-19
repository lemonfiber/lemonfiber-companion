<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing the core wrote to a member, in the words they read it in.
 *
 * The counterpart of {@see Remedy} on the member's side of the house, and the
 * same argument holds it: the sentence is carried rather than composed. What a
 * member is told about their own asking — whether it needs approval, what their
 * period has left, when it makes room again — is the core's answer, and an
 * application that assembled its own wording out of the parts beside it would
 * be a second voice able to disagree with the one that decides.
 *
 * **It carries no key.** Everything else this application shows a person is a
 * catalogue key resolved against the translator, because a sentence written
 * into this codebase would be English on a Dutch phone. These are not written
 * here: they arrive from the core, which knows the household's rules and the
 * language it keeps them in, so translating them again would be this app
 * rewording an answer it did not make.
 */
final readonly class Sentence
{
    private function __construct(private string $said) {}

    /**
     * The one place a string becomes a sentence.
     *
     * Blank is refused, for {@see Remedy::of()}'s reason: a blank line among
     * real ones reads as something nobody wrote, and a member counting what
     * they were told would count it.
     */
    public static function of(string $said): self
    {
        $trimmed = trim($said);

        if ($trimmed === '') {
            throw SentenceSaysNothing::toAMember();
        }

        return new self($trimmed);
    }

    public function shown(): string
    {
        return $this->said;
    }
}
