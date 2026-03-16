<?php

namespace KoreUi\Core\Support;

use InvalidArgumentException;
use KoreUi\Core\Contracts\Overlayable;

class ComponentResolver
{
    /**
     * Resolve a Livewire component class from its name and validate the interface.
     *
     * @throws InvalidArgumentException
     */
    public static function resolve(string $component): string
    {
        try {
            $class = app('livewire.factory')->resolveComponentClass($component);
        } catch (\Throwable) {
            throw new InvalidArgumentException(
                "Unable to resolve Livewire component [{$component}]."
            );
        }

        static::validate($class);

        return $class;
    }

    /**
     * Validate that a class implements the Overlayable interface.
     *
     * @throws InvalidArgumentException
     */
    public static function validate(string $class): void
    {
        if (! is_subclass_of($class, Overlayable::class)) {
            throw new InvalidArgumentException(
                "Component [{$class}] must implement [".Overlayable::class.'].'
            );
        }
    }
}
