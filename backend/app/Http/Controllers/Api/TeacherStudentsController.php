<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Support\PublicMediaUrl;

/**
 * نقاط نهاية قائمة الطلاب للمعلم (Teacher Students).
 *
 * Arabic: تعيد قائمة الطلاب المرتبطين بحلقة المعلم الحالية، مع معلومات تسجيلهم
 * وصورة الطالب عند توفرها.
 * EN: Provides teacher-specific student listing and details endpoints.
 */
class TeacherStudentsController extends Controller
{
    /**
     * قائمة طلاب حلقة المعلم الحالية.
     *
     * Arabic: إذا لم يكن للمعلم حلقة مرتبطة، يعيد قائمة فارغة.
     * EN: Returns the current halaqah's students; empty when no halaqah is assigned.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $halaqahId = $user->teacherProfile?->halaqah_id;
        if (! $halaqahId) {
            return response()->json(['data' => []]);
        }

        $students = Student::query()
            ->with(['currentEnrollment' => fn ($q) => $q->with('halaqah')])
            ->whereHas('currentEnrollment', function (Builder $q) use ($halaqahId) {
                $q->where('halaqah_id', $halaqahId);
            })
            ->orderBy('full_name')
            ->get()
            ->map(function (Student $s) {
                $en = $s->currentEnrollment;

                $photoUrl = PublicMediaUrl::forStoragePath($s->photo_path);

                return [
                    'id' => $s->id,
                    'full_name' => $s->full_name,
                    'is_active' => $s->is_active,
                    'photo_url' => $photoUrl,
                    'enrollment' => $en ? [
                        'id' => $en->id,
                        'status' => $en->status,
                        'enrolled_at' => $en->enrolled_at?->toDateString(),
                        'halaqah_id' => $en->halaqah_id,
                    ] : null,
                ];
            });

        return response()->json(['data' => $students]);
    }

    /**
     * تفاصيل طالب واحد ضمن حلقة المعلم.
     *
     * Arabic: يتحقق أن الطالب مسجّل في حلقة المعلم الحالية قبل إرجاع البيانات.
     * EN: Returns a single student's details if they belong to the teacher's halaqah.
     */
    public function show(Request $request, Student $student): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $halaqahId = $user->teacherProfile?->halaqah_id;

        if (! $halaqahId) {
            abort(404);
        }

        $inTeacherHalaqah = $student->currentEnrollment()
            ->where('halaqah_id', $halaqahId)
            ->exists();

        abort_unless($inTeacherHalaqah, 403);

        $student->load(['currentEnrollment.halaqah.center.region']);

        return response()->json([
            'id' => $student->id,
            'full_name' => $student->full_name,
            'gender' => $student->gender,
            'birth_date' => $student->birth_date?->toDateString(),
            'guardian_name' => $student->guardian_name,
            'guardian_phone' => $student->guardian_phone,
            'is_active' => $student->is_active,
            'current_enrollment' => $student->currentEnrollment ? [
                'status' => $student->currentEnrollment->status,
                'enrolled_at' => $student->currentEnrollment->enrolled_at?->toDateString(),
                'halaqah' => [
                    'id' => $student->currentEnrollment->halaqah->id,
                    'name' => $student->currentEnrollment->halaqah->name,
                ],
            ] : null,
        ]);
    }
}

