<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One placeholder a starting file names a secret by, as the plugin ships it: the secret's id and the form its value is written in, never the value. */
final readonly class APlaceholder
{
    private function __construct(private string $secret, private APlaceholderForm $form) {}

    /** The placeholder; a blank secret is refused. */
    public static function naming(string $secret, APlaceholderForm $form): self
    {
        if (trim($secret) === '') {
            throw PluginSaysNothing::about('secret');
        }

        return new self($secret, $form);
    }

    /** The secret it names, by its id. */
    public function secret(): string
    {
        return $this->secret;
    }

    /** The form its value is written in. */
    public function form(): APlaceholderForm
    {
        return $this->form;
    }
}
