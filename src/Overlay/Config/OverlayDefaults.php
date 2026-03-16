<?php

namespace KoreUi\Overlay\Config;

class OverlayDefaults
{
    /**
     * Responsive max-width classes per size.
     *
     * @return array<string, string>
     */
    public static function sizeClasses(): array
    {
        return [
            'sm' => 'sm:max-w-sm',
            'md' => 'sm:max-w-md',
            'lg' => 'sm:max-w-md md:max-w-lg',
            'xl' => 'sm:max-w-md md:max-w-xl',
            '2xl' => 'sm:max-w-md md:max-w-xl lg:max-w-2xl',
            '3xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl',
            '4xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-4xl',
            '5xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-5xl',
            '6xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-5xl 2xl:max-w-6xl',
            '7xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-5xl 2xl:max-w-7xl',
            'full' => 'max-w-full',
        ];
    }

    /**
     * Get the CSS class string for a given size.
     */
    public static function sizeClass(string $size): string
    {
        return static::sizeClasses()[$size] ?? static::sizeClasses()['2xl'];
    }

    /**
     * Default position per overlay type.
     *
     * @return array<string, string>
     */
    public static function typePositions(): array
    {
        return [
            'modal' => 'center',
            'drawer' => 'right',
            'confirm' => 'center',
            'bottom-sheet' => 'bottom',
            'fullscreen' => 'center',
        ];
    }

    /**
     * Animation classes per overlay type.
     * Each entry has 'enter' and 'leave' transition classes.
     *
     * @return array<string, array{enter: array<string, string>, leave: array<string, string>}>
     */
    public static function typeAnimations(): array
    {
        return [
            'modal' => [
                'enter' => [
                    'from' => 'opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95',
                    'to' => 'opacity-100 translate-y-0 sm:scale-100',
                    'duration' => 'ease-out duration-300',
                ],
                'leave' => [
                    'from' => 'opacity-100 translate-y-0 sm:scale-100',
                    'to' => 'opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95',
                    'duration' => 'ease-in duration-200',
                ],
            ],
            'drawer' => [
                'enter' => [
                    'from' => 'opacity-0 translate-x-full',
                    'to' => 'opacity-100 translate-x-0',
                    'duration' => 'ease-out duration-300',
                ],
                'leave' => [
                    'from' => 'opacity-100 translate-x-0',
                    'to' => 'opacity-0 translate-x-full',
                    'duration' => 'ease-in duration-200',
                ],
            ],
            'confirm' => [
                'enter' => [
                    'from' => 'opacity-0 scale-95',
                    'to' => 'opacity-100 scale-100',
                    'duration' => 'ease-out duration-200',
                ],
                'leave' => [
                    'from' => 'opacity-100 scale-100',
                    'to' => 'opacity-0 scale-95',
                    'duration' => 'ease-in duration-150',
                ],
            ],
            'bottom-sheet' => [
                'enter' => [
                    'from' => 'opacity-0 translate-y-full',
                    'to' => 'opacity-100 translate-y-0',
                    'duration' => 'ease-out duration-300',
                ],
                'leave' => [
                    'from' => 'opacity-100 translate-y-0',
                    'to' => 'opacity-0 translate-y-full',
                    'duration' => 'ease-in duration-200',
                ],
            ],
            'fullscreen' => [
                'enter' => [
                    'from' => 'opacity-0',
                    'to' => 'opacity-100',
                    'duration' => 'ease-out duration-200',
                ],
                'leave' => [
                    'from' => 'opacity-100',
                    'to' => 'opacity-0',
                    'duration' => 'ease-in duration-150',
                ],
            ],
        ];
    }

    /**
     * Get animation classes for a given overlay type, position-aware.
     *
     * @return array{enter: array<string, string>, leave: array<string, string>}
     */
    public static function animation(string $type, string $position = 'center'): array
    {
        $anim = static::typeAnimations()[$type] ?? static::typeAnimations()['modal'];

        // Left drawer slides from the left
        if ($type === 'drawer' && $position === 'left') {
            $anim['enter']['from'] = 'opacity-0 -translate-x-full';
            $anim['leave']['to'] = 'opacity-0 -translate-x-full';
        }

        return $anim;
    }

    /**
     * CSS classes for the overlay panel container per type.
     *
     * @return array<string, string>
     */
    public static function typeContainerClasses(): array
    {
        return [
            'modal' => 'rounded-kore-lg max-h-[90dvh] overflow-y-auto',
            'drawer' => 'h-full overflow-y-auto',
            'confirm' => 'rounded-kore-lg',
            'bottom-sheet' => 'rounded-t-kore-xl max-h-[80dvh] overflow-y-auto',
            'fullscreen' => 'min-h-screen overflow-y-auto',
        ];
    }

    /**
     * Get the container class for a given overlay type.
     */
    public static function containerClass(string $type): string
    {
        return static::typeContainerClasses()[$type] ?? static::typeContainerClasses()['modal'];
    }
}
