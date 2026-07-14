@props([
    'slider' => true,   // el mini-gráfico de contexto de debajo
    'show' => true,
])
@php
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('zoom');

    // El nombre de la propiedad Livewire donde vive la ventana. El zoom NO guarda su estado en
    // Alpine, y eso es deliberado: viviendo en el componente Livewire sobrevive al morph sin
    // ningún hook, se comparte por URL con #[Url] y se testea con Livewire::test().
    $model = $attributes->whereStartsWith('wire:model')->first();

    if (filter_var($show, FILTER_VALIDATE_BOOL)) {
        if (! $model) {
            throw new InvalidArgumentException(
                'koreUi: <x-kore::chart.zoom> necesita un wire:model — el zoom guarda su estado en el '
                .'componente Livewire, no en el cliente. Declara `public ?array $ventana = null;` y pásasela '
                .'también al gráfico: <x-kore::chart :window="$ventana"> … <x-kore::chart.zoom wire:model.live="ventana" />.'
            );
        }

        $frame->zoom = [
            'model' => $model,
            'slider' => filter_var($slider, FILTER_VALIDATE_BOOL),
        ];
    }
@endphp
