<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_all;
use function array_key_exists;
use function array_keys;

use Modules\Kernel\Api\ASettingChange;

/**
 * The setting open for changing, by its name, whether it is being put back to its default rather than given a value, and the settings moved with it in the same change.
 *
 * Each setting moved alongside is held with the value chosen for it, blank
 * until one is; one being put back is held blank.
 */
final readonly class ASettingBeingChanged
{
    /** @param array<string, string> $alongside each setting moved in the same change, by its name, with the value chosen for it */
    public function __construct(public string $named, public bool $toDefault, public array $alongside = []) {}

    /** The same change, moving this setting too, with nothing chosen for it yet. */
    public function alsoMoving(string $setting): self
    {
        return new self($this->named, $this->toDefault, [...$this->alongside, $setting => '']);
    }

    /** The same change, giving a setting moved alongside this value. */
    public function choosing(string $setting, string $value): self
    {
        return new self($this->named, $this->toDefault, [...$this->alongside, $setting => $value]);
    }

    /** The same change, leaving a setting moved alongside out of it. */
    public function leaving(string $setting): self
    {
        $alongside = $this->alongside;
        unset($alongside[$setting]);

        return new self($this->named, $this->toDefault, $alongside);
    }

    /** Whether the change moves this setting, as the one opened or alongside it. */
    public function moves(string $setting): bool
    {
        return $setting === $this->named || array_key_exists($setting, $this->alongside);
    }

    /** The change asked for: the one opened, given what is typed or put back, and each moved alongside; none while a setting alongside has no value chosen. */
    public function asked(string $typed): ?ASettingChange
    {
        if ($this->toDefault) {
            $change = ASettingChange::backToDefault($this->named);

            foreach (array_keys($this->alongside) as $setting) {
                $change = $change->andAlso(ASettingChange::backToDefault($setting));
            }

            return $change;
        }

        if (! array_all($this->alongside, static fn(string $value): bool => $value !== '')) {
            return null;
        }

        $change = ASettingChange::toHold($this->named, $typed);

        foreach ($this->alongside as $setting => $value) {
            $change = $change->andAlso(ASettingChange::toHold($setting, $value));
        }

        return $change;
    }
}
