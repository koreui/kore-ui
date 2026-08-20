<?php

it('dice qué campo del schema está mal en vez de tumbar la página', function () {
    // Un campo sin `key` reventaba con «Undefined array key "key"» apuntando a
    // una línea del paquete: un 500 en toda la página, sin decir cuál de los
    // campos es el que falta. Es un error de quien declara el schema, pero el
    // mensaje tiene que llevarle hasta él.
    // Blade envuelve lo que lance una vista en una ViewException, así que se
    // comprueba el mensaje y la causa.
    try {
        $this->blade('<x-kore::repeater label="P" name="p" :fields="[[\'label\' => \'Sin clave\']]" />');
        $this->fail('Se esperaba una excepción por el campo sin `key`.');
    } catch (\Throwable $e) {
        expect($e->getMessage())->toContain('el campo #0 de `fields` no declara `key`');

        // Blade anida una ViewException por cada vista de la pila: hay que
        // bajar hasta el fondo para llegar a la excepción de verdad.
        $causa = $e;
        while ($causa->getPrevious() !== null) {
            $causa = $causa->getPrevious();
        }
        expect($causa)->toBeInstanceOf(InvalidArgumentException::class);
    }
});

it('acepta un schema correcto', function () {
    $this->blade('<x-kore::repeater label="Contactos" name="c" :fields="[[\'key\' => \'nombre\', \'label\' => \'Nombre\']]" />')
        ->assertSee('Contactos')
        ->assertSee('Nombre');
});
