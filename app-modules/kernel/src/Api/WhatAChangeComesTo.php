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
     * @param list<ServiceId>                 $stops
     * @param list<WhatAChangeLeavesAlone>    $keeps
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

    /** A change the stack found nothing about beyond its value, and said nothing of. */
    public static function nothing(): self
    {
        return new self([], [], [], [], [], null, null);
    }

    /** A change the stack says this of applying, with nothing found about it yet. */
    public static function meaning(string $means): self
    {
        $said = trim($means);

        if ($said === '') {
            throw AFindingSaysNothing::about('consequence');
        }

        return new self([], [], [], [], [], null, $said);
    }

    /** The same, and one more thing it newly asks for. */
    public function asking(WhatAChangeAsksFor $ask): self
    {
        return new self([...$this->asks, $ask], $this->stops, $this->keeps, $this->libraries, $this->interrupts, $this->edit, $this->means);
    }

    /** The same, and one more service it stops. */
    public function stopping(ServiceId $service): self
    {
        return new self($this->asks, [...$this->stops, $service], $this->keeps, $this->libraries, $this->interrupts, $this->edit, $this->means);
    }

    /** The same, and one more thing it leaves as it is. */
    public function leaving(WhatAChangeLeavesAlone $left): self
    {
        return new self($this->asks, $this->stops, [...$this->keeps, $left], $this->libraries, $this->interrupts, $this->edit, $this->means);
    }

    /** The same, and one more library path a move reaches. */
    public function reaching(ALibraryAMoveReaches $library): self
    {
        return new self($this->asks, $this->stops, $this->keeps, [...$this->libraries, $library], $this->interrupts, $this->edit, $this->means);
    }

    /** The same, and one more download it interrupts. */
    public function interrupting(ADownloadItWouldInterrupt $download): self
    {
        return new self($this->asks, $this->stops, $this->keeps, $this->libraries, [...$this->interrupts, $download], $this->edit, $this->means);
    }

    /** The same, with the edit the stack found in the file. */
    public function withTheEdit(AnEditFoundInTheFile $edit): self
    {
        return new self($this->asks, $this->stops, $this->keeps, $this->libraries, $this->interrupts, $edit, $this->means);
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
