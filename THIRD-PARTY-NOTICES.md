# Third-party notices

KoreUi es MIT. Estos algoritmos se han **portado a PHP** desde proyectos con licencia
permisiva, que permite derivar obra conservando el aviso de copyright.

---

## d3-array — ISC

Copyright 2010-2023 Mike Bostock
https://github.com/d3/d3-array/blob/main/LICENSE

El algoritmo de "nice ticks" (`src/ticks.js`) está portado en `src/Charts/Ticks.php`.

Es lo que hace que un eje diga *1.000, 2.000, 3.000* en vez de *1.224, 2.448, 3.672*. Y no es
un detalle: es el fallo que tienen todas las librerías de gráficos de PHP con licencia
permisiva, que reparten el rango a lo bruto (`paso = rango / nº_líneas`).

---

## d3-shape — ISC

Copyright 2010-2022 Mike Bostock
https://github.com/d3/d3-shape/blob/main/LICENSE

La curva monótona (`src/curve/monotone.js`, que implementa Steffen 1990, *"A simple method for
monotonic interpolation in one dimension"*) está portada en `src/Charts/Path.php`.

Es la curva que **no inventa extremos**: garantiza que entre dos puntos la curva no se sale
del rango de esos dos puntos. Una spline cualquiera dibujaría un máximo donde no hay ningún
dato, y en un gráfico de negocio eso no es un problema estético.

---

## @floating-ui/dom — MIT

Copyright (c) 2021-present Floating UI contributors
https://github.com/floating-ui/floating-ui/blob/master/LICENSE

Dependencia npm (no portada). Posiciona los elementos flotantes: dropdowns, tooltips y el
tooltip de los gráficos.

---

## @alpinejs/collapse — MIT

Copyright (c) 2019-present Caleb Porzio and contributors
https://github.com/alpinejs/alpine/blob/main/LICENSE.md

Dependencia npm (no portada).
