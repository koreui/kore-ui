@php
    $resolvedProps = $column->resolveProps($value, $row);
    $componentName = $column->getComponentName();
@endphp

@if($column->isBladeComponent())
    <x-dynamic-component :component="$componentName" :attributes="new \Illuminate\View\ComponentAttributeBag($resolvedProps)" />
@else
    @include($componentName, $resolvedProps)
@endif
