<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء طالب في Filament.
 *
 * Arabic: تعتمد نموذج الإدخال المعرّف في `StudentResource::form`.
 * EN: Student creation page (uses the resource form schema).
 */
class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;
}
