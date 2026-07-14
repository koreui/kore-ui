<?php

namespace KoreUi\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use KoreUi\Charts\ChartContext;
use KoreUi\Charts\ChartFrame;

/**
 * `<x-kore::chart>` — el único componente de CLASE del paquete, y por un motivo muy concreto.
 *
 * Las marcas hijas (`<x-kore::chart.line>`, `.bar`…) no dibujan nada: se registran en un
 * contexto, y el gráfico dibuja cuando ya las tiene todas. Para eso hace falta un gancho
 * que corra ANTES de que Blade renderice el slot — y en Blade solo existe uno: **el
 * constructor de un componente de clase**. El compilador emite, en este orden:
 *
 *     $component = Chart::resolve([...]);              // ← el constructor: abrimos el frame
 *     $__env->startComponent($component->resolveView(), $component->data());
 *     // ... aquí se renderizan los hijos, que se registran en el frame ...
 *     echo $__env->renderComponent();                  // ← ahora sí se ejecuta la vista
 *
 * ⚠️⚠️ **TRAMPA MORTAL — LEER ANTES DE TOCAR ESTA CLASE** ⚠️⚠️
 *
 * `startComponent()` recibe `$component->resolveView()`, y **`resolveView()` llama a
 * `render()`**. Es decir: **`render()` SE EJECUTA ANTES QUE LOS HIJOS.**
 *
 * Si alguien mete aquí el cálculo del dominio, de las escalas o de los paths, el gráfico
 * saldrá **vacío** — porque en ese momento todavía no hay ni una marca registrada — y no
 * habrá ningún error que lo explique. La geometría vive en el `@php` de la vista Blade, que
 * sí corre después. **Esta clase solo abre el frame y genera el id.**
 */
class Chart extends Component
{
    public string $chartId;

    public ChartFrame $frame;

    /**
     * @param  array<int, mixed>  $data  filas: arrays, objetos o modelos
     * @param  string|null  $x  la clave del canal X. Si es null, se usa el índice de la fila
     * @param  string|null  $height  longitud CSS. Con `aspect` puesto, se ignora
     * @param  string|null  $aspect  proporción CSS ("16/9"). Alternativa a `height`
     */
    public function __construct(
        public array $data = [],
        public ?string $x = null,
        public ?string $height = null,
        public ?string $aspect = null,
        public ?string $title = null,
        public ?string $ariaLabel = null,
        public ?string $id = null,
    ) {
        $context = app(ChartContext::class);

        // Determinista a propósito: con uniqid() el id cambiaría en cada render, el morph de
        // Livewire vería otro nodo y lo reemplazaría entero — el degradado del área
        // parpadearía en cada actualización.
        $this->chartId = $id ?: $context->nextId();

        $this->frame = $context->open(new ChartFrame($this->chartId, $data, $x));
    }

    public function render(): View
    {
        // ⚠️ NO CALCULAR NADA AQUÍ. Ver la trampa mortal de arriba: este método corre antes
        // que los hijos, así que $this->frame todavía está vacío.
        return view('kore::chart.chart');
    }
}
