<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentProfileSubmission;
use App\Policies\TeacherDailyRecordsPolicy;
use App\Support\PublicMediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * نقاط نهاية ملف الطالب (Student Profile) من منظور المعلم.
 *
 * Arabic: تعرض بيانات الملف وصلاحيات التعديل/الإرسال، وتدعم تحديث البيانات والصورة،
 * ثم إنشاء طلب مراجعة (Submission) عند الإرسال.
 * EN: Teacher-facing student profile endpoints (show/update/submit-for-review).
 */
class TeacherStudentProfileController extends Controller
{
    /**
     * عرض ملف الطالب وصلاحيات التعامل معه.
     *
     * Arabic: يتحقق من وصول المعلم للطالب عبر سياسة `TeacherDailyRecordsPolicy`،
     * ويعيد أيضاً حالة وجود طلب مراجعة قيد الانتظار.
     * EN: Shows student profile data with edit/submit capabilities and pending submission info.
     */
    public function show(Request $request, Student $student): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $policy = app(TeacherDailyRecordsPolicy::class);

        abort_unless($policy->teacherCanAccessStudent($user, $student), 403);

        $student->load(['pendingProfileSubmission', 'currentEnrollment.halaqah']);

        return response()->json([
            'student'            => $this->studentPayload($student),
            'can_edit'           => $policy->teacherCanEditStudentProfile($user, $student),
            'can_submit'         => $policy->teacherCanSubmitStudentProfile($user, $student),
            'pending_submission' => $student->pendingProfileSubmission,
        ]);
    }

    /**
     * تحديث بيانات ملف الطالب (مع دعم الصورة).
     *
     * Arabic: يقبل رفع الصورة كـ multipart (`photo`) أو كـ base64 (`photo_base64`)
     * لتغطية قيود Flutter Web. عند رفع صورة جديدة يتم حذف الصورة السابقة إن وجدت.
     * EN: Updates student profile fields and optionally replaces the stored photo.
     */
    public function update(Request $request, Student $student): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $policy = app(TeacherDailyRecordsPolicy::class);

        abort_unless($policy->teacherCanEditStudentProfile($user, $student), 403);

        $validated = $request->validate([
            'full_name'       => ['required', 'string', 'max:255'],
            'gender'          => ['required', Rule::in(['male', 'female'])],
            'birth_date'      => ['nullable', 'date'],
            'guardian_name'   => ['nullable', 'string', 'max:255'],
            'guardian_phone'  => ['nullable', 'string', 'max:50'],
            'national_id'     => ['nullable', 'string', 'max:50'],
            'notes'           => ['nullable', 'string'],
            'photo'           => ['nullable', 'image', 'max:5120'],
            /** لـ Flutter Web وغيره: JSON + PUT حيث لا يصل multipart كملف */
            'photo_base64'    => ['nullable', 'string', 'max:10000000'],
        ]);

        $newPhotoPath = null;

        if ($request->hasFile('photo')) {
            $newPhotoPath = $request->file('photo')->store('student-photos/'.$student->id, 'public');
        } elseif ($request->filled('photo_base64')) {
            $newPhotoPath = $this->storeStudentPhotoFromBase64($student, $request->string('photo_base64')->toString());
        }

        if ($newPhotoPath !== null) {
            if ($student->photo_path && Storage::disk('public')->exists($student->photo_path)) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $validated['photo_path'] = $newPhotoPath;
        }

        unset($validated['photo'], $validated['photo_base64']);

        $student->update($validated);

        $student->refresh();

        return response()->json([
            'message' => 'updated',
            'student' => $this->studentPayload($student->fresh()),
        ]);
    }

    /**
     * إرسال الملف للمراجعة (إنشاء Submission بالحالة pending).
     *
     * Arabic: يمنع إنشاء طلب جديد إذا كان هناك طلب قائم قيد المراجعة.
     * EN: Creates a pending submission, or returns 422 if one already exists.
     */
    public function submit(Request $request, Student $student): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $policy = app(TeacherDailyRecordsPolicy::class);

        abort_unless($policy->teacherCanSubmitStudentProfile($user, $student), 403);

        if ($student->pendingProfileSubmission()->exists()) {
            return response()->json(['message' => 'يوجد طلب قيد المراجعة'], 422);
        }

        DB::transaction(function () use ($student, $user) {
            StudentProfileSubmission::create([
                'student_id'        => $student->id,
                'teacher_user_id'   => $user->id,
                'status'            => StudentProfileSubmission::STATUS_PENDING,
                'full_name'         => $student->full_name,
                'gender'            => $student->gender,
                'birth_date'        => $student->birth_date,
                'guardian_name'     => $student->guardian_name,
                'guardian_phone'    => $student->guardian_phone,
                'national_id'       => $student->national_id,
                'notes'             => $student->notes,
                'photo_path'        => $student->photo_path,
            ]);
        });

        $student->load('pendingProfileSubmission');

        return response()->json([
            'message'            => 'submitted',
            'pending_submission' => $student->pendingProfileSubmission,
        ]);
    }

    /**
     * تخزين صورة الطالب القادمة بصيغة base64 وإرجاع مسارها النسبي.
     *
     * Arabic: يتحقق من البادئة (data URL) والحجم الأقصى ونوع MIME ثم يحفظها في
     * `public` disk.
     * EN: Validates and stores a base64 image, returning the relative path.
     *
     * @throws ValidationException عند خطأ في base64 أو النوع أو الحجم
     */
    private function storeStudentPhotoFromBase64(Student $student, string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('#^data:image/(jpeg|jpg|png|gif|webp);base64,#i', $raw)) {
            $raw = substr($raw, (int) strpos($raw, ',') + 1);
        }

        $binary = base64_decode($raw, true);
        if ($binary === false) {
            throw ValidationException::withMessages([
                'photo_base64' => ['صورة غير صالحة (تشفير base64).'],
            ]);
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'photo_base64' => ['حجم الصورة يجب ألا يتجاوز 5 ميغابايت.'],
            ]);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        if (! isset($allowed[$mime])) {
            throw ValidationException::withMessages([
                'photo_base64' => ['نوع الصورة غير مدعوم.'],
            ]);
        }

        $ext = $allowed[$mime];
        $relative = 'student-photos/'.$student->id.'/'.Str::uuid()->toString().'.'.$ext;
        Storage::disk('public')->put($relative, $binary);

        return $relative;
    }

    /**
     * بناء حمولة الطالب (payload) للواجهة.
     *
     * Arabic: يوحّد الحقول المُعادة للموبايل ويضيف `photo_url` المبني عبر `PublicMediaUrl`.
     * EN: Builds a consistent student payload for mobile consumers.
     *
     * @return array<string, mixed>
     */
    private function studentPayload(Student $student): array
    {
        $url = PublicMediaUrl::forStoragePath($student->photo_path);

        return [
            'id'                       => $student->id,
            'full_name'                => $student->full_name,
            'gender'                   => $student->gender,
            'birth_date'               => $student->birth_date?->toDateString(),
            'guardian_name'            => $student->guardian_name,
            'guardian_phone'           => $student->guardian_phone,
            'national_id'              => $student->national_id,
            'notes'                    => $student->notes,
            'is_active'                => $student->is_active,
            'photo_path'               => $student->photo_path,
            'photo_url'                => $url,
            'profile_locked'           => $student->profile_locked,
            'teacher_may_edit_profile' => $student->teacher_may_edit_profile,
        ];
    }
}
