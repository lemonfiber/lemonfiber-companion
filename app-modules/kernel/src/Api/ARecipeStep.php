<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One call a plugin's recipe makes, in the order it makes them.
 *
 * Where it calls is the manifest's own name for the destination, never an
 * address. Its API is named only where the destination is a service of the
 * stack whose installed plugin declares the API it is reached in.
 */
final readonly class ARecipeStep
{
    private function __construct(
        private string $id,
        private string $method,
        private string $to,
        private string $path,
        private string $api,
    ) {}

    /** The call; `api` is empty where no plugin declares the API its destination is reached in. A blank word otherwise is refused. */
    public static function calling(string $id, string $method, string $to, string $path, string $api): self
    {
        foreach (['id' => $id, 'method' => $method, 'to' => $to, 'path' => $path] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($id, $method, $to, $path, trim($api));
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

    /** The API the destination is reached in, as its plugin declares it, or empty where none is declared. */
    public function api(): string
    {
        return $this->api;
    }
}
