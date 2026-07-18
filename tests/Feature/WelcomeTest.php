<?php

test('a página inicial apresenta a Sprint 1', function (): void {
    $this->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->get('/')
        ->assertOk()
        ->assertSee('Sprint 1')
        ->assertSee('Cada estamparia')
        ->assertHeader('X-Correlation-ID');
});

test('o endpoint de saúde responde', function (): void {
    $this->get('/up')->assertOk();
});
