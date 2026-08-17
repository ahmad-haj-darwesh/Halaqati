<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء تسجيل في Filament.
 *
 * Arabic: تعتمد نموذج الإدخال المعرّف في `EnrollmentResource::form`.
 * EN: Enrollment creation page (uses the resource form schema).
 */
class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;
}
