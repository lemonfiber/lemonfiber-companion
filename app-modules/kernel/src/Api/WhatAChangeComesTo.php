<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a proposed change comes to on the machine beyond the value it changes, as the stack found it.
 */
final readonly class WhatAChangeComesTo
{
    /**
     * @param list<WhatAChangeAsksFor>        $asks
     * @param list<string>                    $stops
     * @param list<string>                    $keeps
     * @param list<ALibraryAMoveReaches>      $libraries
     * @param list<ADownloadItWouldInterrupt> $interrupts
     */
    private function __construct(
        public array $asks,
        public array $stops,
        public array $keeps,
        public array $libraries,
        public array $interrupts,
        private ?AnEditFoundInTheFile $edit,
        private ?string $means,
    ) {}

    /**
     * Everything the stack found, with no edit in the file and no sentence on what it means.
     *
     * @param list<WhatAChangeAsksFor>        $asks
     * @param list<string>                    $stops
     * @param list<string>                    $keeps
     * @param list<ALibraryAMoveReaches>      $libraries
     * @param list<ADownloadItWouldInterrupt> $interrupts
     */
    public static function found(array $asks, array $stops, array $keeps, array $libraries, array $interrupts): self
    {
        return new self($asks, $stops, $keeps, $libraries, $interrupts, null, null);
    }

    /** A change the stack found nothing about beyond its value. */
    public static function nothing(): self
    {
        return self::found([], [], [], [], []);
    }

    /** The same findings, with the edit the stack found in the file. */
    public function withTheEdit(AnEditFoundInTheFile $edit): self
    {
        return new self($this->asks, $this->stops, $this->keeps, $this->libraries, $this->interrupts, $edit, $this->means);
    }

    /** The same findings, with the stack's sentence on what applying the change means. */
    public function meaning(string $means): self
    {
        $said = trim($means);

        if ($said === '') {
            throw AFindingSaysNothing::about('consequence');
        }

        return new self($this->asks, $this->stops, $this->keeps, $this->libraries, $this->interrupts, $this->edit, $said);
    }

    /**
     * Say the edit found in the file, or that none was.
     *
     * @template TFound of object
     * @template TNone of object
     *
     * @param  Closure(AnEditFoundInTheFile): TFound  $found
     * @param  Closure(): TNone  $none
     * @return TFound|TNone
     */
    public function theEdit(Closure $found, Closure $none): object
    {
        return $this->edit instanceof AnEditFoundInTheFile ? $found($this->edit) : $none();
    }

    /**
     * Say what the stack says applying the change means, or that it said nothing.
     *
     * @template TSaid of object
     * @template TNothing of object
     *
     * @param  Closure(string): TSaid  $said
     * @param  Closure(): TNothing  $nothing
     * @return TSaid|TNothing
     */
    public function whatItMeans(Closure $said, Closure $nothing): object
    {
        return $this->means === null ? $nothing() : $said($this->means);
    }
}
