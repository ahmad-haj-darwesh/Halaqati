<?php

namespace App\Filament\Resources\StudentProfileSubmissionResource\Pages;

use App\Filament\Resources\StudentProfileSubmissionResource;
use Filament\Resources\Pages\ListRecords;

/**
 * صفحة قائمة طلبات اعتماد/مراجعة ملفات الطلاب في Filament.
 *
 * Arabic: تعرض سجلات `StudentProfileSubmission` وفق إعدادات الجدول والفلاتر المعرفة في المورد.
 * EN: List page for student profile submissions (uses the resource table configuration).
 */
class ListStudentProfileSubmissions extends ListRecords
{
    protected static string $resource = StudentProfileSubmissionResource::class;
}
