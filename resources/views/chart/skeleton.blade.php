@php
    // El frame lo abrió el constructor y hay que cerrarlo aquí sí o sí: los hijos
    // (`<x-kore::chart.line>` y compañía) se han renderizado igual y se han
    // registrado en él. Si no se cierra, el contexto queda abierto y el siguiente
    // gráfico de la página hereda las marcas de este.
    // `close()` devuelve el frame, que es de donde salen las filas: en una vista
    // de Blade `$data` no es el prop del componente sino la variable interna de
    // Laravel, y llega vacía.
    $frameCerrado = app(\KoreUi\Charts\ChartContext::class)->close();

    $config = config('kore-ui.chart', []);
    $alturaSilueta = \KoreUi\Shell\SidebarState::cssLength($height, $config['height'] ?? '16rem');

    // Un entero elige las barras; `skeleton` a secas usa las filas que ya haya, y
    // siete si no hay ninguna, que es el caso normal: la silueta se enseña
    // precisamente porque los datos no han llegado.
    $barras = is_int($skeleton) && $skeleton > 0
        ? $skeleton
        : (count($frameCerrado->data) ?: 7);
@endphp

<x-kore::skeleton.chart
    :bars="$barras"
    :height="$alturaSilueta"
    {{ $attributes->except(['data', 'x', 'height', 'aspect', 'title', 'ariaLabel', 'id', 'grid', 'window', 'orientation', 'skeleton']) }}
/>
