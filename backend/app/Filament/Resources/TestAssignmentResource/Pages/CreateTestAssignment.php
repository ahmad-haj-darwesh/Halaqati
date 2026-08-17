<?php

namespace App\Filament\Resources\TestAssignmentResource\Pages;

use App\Filament\Resources\TestAssignmentResource;
use App\Models\TestAssignment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * صفحة إنشاء تعيينات اختبار في Filament.
 *
 * Arabic: تدعم إنشاء عدة تعيينات دفعة واحدة عبر اختيار أكثر من طالب (`student_ids`).
 * تتحقق من عدم وجود تعيين مسبق لنفس (الاختبار + الطالب)، ثم تنشئ السجلات داخل Transaction
 * وتعيد أول سجل فقط (بحسب متطلبات Filament) مع رسالة نجاح مناسبة.
 *
 * EN: Create page that supports multi-student assignment creation in a transaction.
 */
class CreateTestAssignment extends CreateRecord
{
    protected static string $resource = TestAssignmentResource::class;

    protected int $createdAssignmentsCount = 0;

    /**
     * إنشاء السجل/السجلات فعلياً.
     *
     * Arabic: ينشئ تعييناً منفصلاً لكل طالب مختار ويحدّث `assigned_by_user_id`.
     * EN: Creates one assignment per selected student; returns the first created model.
     *
     * @param  array<string,mixed>  $data
     * @return Model
     *
     * @throws ValidationException عندما لا يتم اختيار طلاب أو عند وجود تعيين مسبق.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $studentIds = $data['student_ids'] ?? [];
        unset($data['student_ids']);

        if (! is_array($studentIds)) {
            $studentIds = [];
        }
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));

        if (count($studentIds) === 0) {
            throw ValidationException::withMessages([
                'student_ids' => ['اختر طالباً واحداً على الأقل.'],
            ]);
        }

        $testId = (int) $data['test_id'];
        foreach ($studentIds as $sid) {
            $exists = TestAssignment::query()
                ->where('test_id', $testId)
                ->where('student_id', $sid)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'student_ids' => ['يوجد تعيين مسبق لأحد الطلاب المختارين لهذا الاختبار.'],
                ]);
            }
        }

        $data['assigned_by_user_id'] = auth()->id();

        return DB::transaction(function () use ($data, $studentIds) {
            $first = null;
            foreach ($studentIds as $sid) {
                $assignment = TestAssignment::create(array_merge($data, ['student_id' => $sid]));
                if ($first === null) {
                    $first = $assignment;
                }
            }
            $this->createdAssignmentsCount = count($studentIds);

            return $first;
        });
    }

    /**
     * إعادة التوجيه بعد الإنشاء.
     * EN: Redirect URL after creation.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * عنوان إشعار النجاح بعد الإنشاء.
     * EN: Created notification title.
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        if ($this->createdAssignmentsCount > 1) {
            return "تم إنشاء {$this->createdAssignmentsCount} تعيينات.";
        }

        return 'تم إنشاء التعيين.';
    }
}
