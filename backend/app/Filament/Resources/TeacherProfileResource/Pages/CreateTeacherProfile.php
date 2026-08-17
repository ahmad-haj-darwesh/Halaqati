<?php

namespace App\Filament\Resources\TeacherProfileResource\Pages;

use App\Filament\Resources\TeacherProfileResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء ملف معلّم في Filament.
 *
 * Arabic: تعتمد نموذج الإدخال المعرّف في `TeacherProfileResource::form`.
 * EN: TeacherProfile creation page (uses the resource form schema).
 */
class CreateTeacherProfile extends CreateRecord
{
    protected static string $resource = TeacherProfileResource::class;
}
