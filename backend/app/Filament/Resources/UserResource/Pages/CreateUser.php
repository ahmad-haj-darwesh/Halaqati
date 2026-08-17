<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء مستخدم في Filament.
 *
 * Arabic: تعتمد نموذج الإدخال المعرّف في `UserResource::form`.
 * EN: User creation page (uses the resource form schema).
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
