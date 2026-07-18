<?php

use App\Models\AuditLog;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Correlation\CorrelationContext;
use App\Support\Correlation\CorrelationId;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registra auditoria com correlation id', function () {
    $context = app(CorrelationContext::class);
    $context->set(new CorrelationId('01JZ0000000000000000000000'));

    $log = app(AuditLogger::class)->record(new AuditEntryData(
        action: 'SPRINT_ZERO.TESTED',
        after: ['status' => 'ok'],
        source: 'TEST',
    ));

    expect($log)->toBeInstanceOf(AuditLog::class)
        ->and($log->correlation_id)->toBe('01JZ0000000000000000000000')
        ->and($log->after)->toBe(['status' => 'ok']);
});
