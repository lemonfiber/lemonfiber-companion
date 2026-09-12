<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

/**
 * What a handset would answer, for a bridge that is not talking to one.
 *
 * Scripted into `nativephp/mobile`'s `FakeBridge` so the adapter above it runs
 * the real call — the method name from the manifest, the JSON out, the JSON
 * back — rather than a parallel path built to resemble it.
 *
 * A class rather than three closures over two `bool`s, for a reason worth
 * recording: closures capturing by reference start from literal `false` and
 * `true`, and the analyser reads the first branch as permanently dead. Holding
 * the state on an object says the same thing and is readable by both.
 *
 * What it answers is what `CaptureRule` decides, in Kotlin and in Swift, with
 * the same six cases each. That is what a contract is — the same promise kept by
 * both — and this states it once more in the language the application is written
 * in, rather than inventing a third rule.
 */
final class AHandsetsWindow
{
    private bool $concealed = false;

    private bool $inFront = true;

    /** A handset somebody is looking at, showing nothing guarded. */
    public static function inFront(): self
    {
        return new self();
    }

    /** The app leaves the foreground, which the native lifecycle observer sees. */
    public function backgrounded(): void
    {
        $this->inFront = false;
    }

    /**
     * What `Lemonfiber.Conceal` answers.
     *
     * @return array{protected: bool}
     */
    public function conceal(): array
    {
        $this->concealed = true;

        return $this->answer();
    }

    /**
     * What `Lemonfiber.Reveal` answers.
     *
     * @return array{protected: bool}
     */
    public function reveal(): array
    {
        $this->concealed = false;

        return $this->answer();
    }

    /**
     * What `Lemonfiber.IsProtected` answers.
     *
     * @return array{protected: bool}
     */
    public function answer(): array
    {
        return ['protected' => $this->concealed || ! $this->inFront];
    }
}
