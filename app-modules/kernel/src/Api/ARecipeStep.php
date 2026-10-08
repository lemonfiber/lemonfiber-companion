<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One call a plugin's recipe makes, in the order it makes them.
 *
 * Where it calls is the manifest's own name for the destination, never an
 * address. An adapter is always lemonfiber's, and one is named only where the
 * destination is a service an adapter of lemonfiber's speaks to.
 */
final readonly class ARecipeStep
{
    private function __construct(
        private string $id,
        private string $method,
        private string $to,
        private string $path,
        private string $adapter,
    ) {}

    /** The call; `adapter` is empty where no adapter of lemonfiber's reaches the destination. A blank word otherwise is refused. */
    public static function calling(string $id, string $method, string $to, string $path, string $adapter): self
    {
        foreach (['id' => $id, 'method' => $method, 'to' => $to, 'path' => $path] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($id, $method, $to, $path, trim($adapter));
    }

    /** The step's id within its recipe. */
    public function id(): string
    {
        return $this->id;
    }

    /** The method it calls with. */
    public function method(): string
    {
        return $this->method;
    }

    /** Where it calls, by the manifest's name. */
    public function to(): string
    {
        return $this->to;
    }

    /** The path it calls. */
    public function path(): string
    {
        return $this->path;
    }

    /** Which of lemonfiber's adapters reaches the destination, or empty where none does. */
    public function adapter(): string
    {
        return $this->adapter;
    }
}
