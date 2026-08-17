<?php

namespace App\Filament\Resources\SupervisionRubricResource\Pages;

use App\Filament\Resources\SupervisionRubricResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء قالب زيارة في Filament.
 *
 * Arabic: تعتمد نموذج الإدخال المعرّف في `SupervisionRubricResource::form`.
 * EN: Supervision rubric creation page (uses the resource form schema).
 */
class CreateSupervisionRubric extends CreateRecord
{
    protected static string $resource = SupervisionRubricResource::class;
}
