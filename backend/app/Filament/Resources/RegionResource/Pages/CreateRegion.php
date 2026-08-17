<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء منطقة في Filament.
 *
 * Arabic: تعتمد مخطط النموذج المعرّف في `RegionResource::form`.
 * EN: Region creation page (uses the resource form schema).
 */
class CreateRegion extends CreateRecord
{
    protected static string $resource = RegionResource::class;
}
