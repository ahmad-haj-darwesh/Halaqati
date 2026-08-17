<?php

namespace App\Filament\Resources\TestResultResource\Pages;

use App\Filament\Resources\TestResultResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء نتيجة اختبار في Filament.
 *
 * Arabic: تعتمد نموذج الإدخال المعرّف في `TestResultResource::form`.
 * EN: Test result creation page (uses the resource form schema).
 */
class CreateTestResult extends CreateRecord
{
    protected static string $resource = TestResultResource::class;
}
