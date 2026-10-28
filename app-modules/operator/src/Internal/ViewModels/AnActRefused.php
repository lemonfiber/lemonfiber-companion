<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What the stack would not do, in its words, with each service the yes left unapproved to take its privileged shape. */
final readonly class AnActRefused
{
    /**
     * @param string                   $said       the catalogue key for what the stack would not do
     * @param ARefusalAsShown          $words      the stack's refusal, in its words
     * @param list<AShapeTakenAsShown> $unapproved every service left unapproved to take its privileged shape, where the act agreed to was refused
     */
    public function __construct(public string $said, public ARefusalAsShown $words, public array $unapproved) {}
}
