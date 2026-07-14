<?php

namespace KoreUi\Charts\Exceptions;

use RuntimeException;

/**
 * Una marca fuera de su gráfico.
 *
 * Lanzar es lo correcto: la alternativa es que la marca se quede colgada en el contexto y
 * se la coma el siguiente gráfico de la página. Eso produce una serie fantasma con un color
 * robado, sin ningún error, y es el bug que Filament lleva años sin poder reproducir.
 */
class OrphanMarkException extends RuntimeException {}
