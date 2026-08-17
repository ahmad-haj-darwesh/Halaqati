<?php

namespace App\Filament\Resources\TestResource\Pages;

use App\Filament\Resources\TestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

/**
 * صفحة إنشاء اختبار في Filament.
 *
 * Arabic: تضيف `created_by_user_id` تلقائياً لأن الحقل مطلوب في قاعدة البيانات
 * لكنه غير ظاهر في نموذج الإدخال.
 * EN: Test creation page that sets created_by_user_id automatically.
 */
class CreateTest extends CreateRecord
{
    protected static string $resource = TestResource::class;

    /**
     * عمود created_by_user_id مطلوب في الجدول ولا يظهر في النموذج.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();

        return $data;
    }
}
