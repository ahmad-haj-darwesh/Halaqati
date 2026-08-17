<?php

namespace App\Filament\Resources\CenterResource\Pages;

use App\Filament\Resources\CenterResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء مركز في Filament.
 *
 * Arabic: تعتمد مخطط النموذج المعرّف في `CenterResource::form`.
 * EN: Center creation page (uses the resource form schema).
 */
class CreateCenter extends CreateRecord
{
    protected static string $resource = CenterResource::class;
}
