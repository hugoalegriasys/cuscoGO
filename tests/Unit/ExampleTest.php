<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Given: no se necesita ningún estado previo para la prueba.
     * When: se evalúa la aserción sobre el valor true.
     * Then: se confirma que la condición es verdadera y la prueba pasa.
     */
    public function test_that_true_is_true(): void
    {
        // Given: no hay estado previo que preparar.
        // When: se evalúa la aserción con el valor true.
        // Then: se verifica que la condición es verdadera.
        $this->assertTrue(true);
    }
}
