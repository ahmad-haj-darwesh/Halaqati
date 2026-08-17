<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Models\TestResult;
use App\Policies\TestAssignmentPolicy;
use App\Policies\TestPolicy;
use App\Policies\TestResultPolicy;
use App\Support\ExaminerScore;
use App\Support\OfflineSync;
use App\Support\PublicMediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * نقاط نهاية المختبر (Examiner) لتطبيق الموبايل.
 *
 * Arabic: تشمل معلومات الحساب، الاختبارات ضمن النطاق، التعيينات، النتائج،
 * تفاصيل الطالب للاختبار، حفظ نتيجة الاختبار، وملخص يومي.
 * EN: Examiner mobile API endpoints (tests/assignments/results/student detail/result submission/summary).
 */
class ExaminerApiController extends Controller
{
    /**
     * بيانات "me" للمختبر.
     *
     * Arabic: يعيد المراكز التي يديرها المستخدم لتحديد نطاق ما يمكن اختباره.
     * EN: Returns examiner identity and managed centers.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $centerIds = $user->managedCenters()->pluck('id');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'Examiner',
            'managed_centers' => $centerIds->map(fn (int $id) => [
                'id' => $id,
            ])->values()->all(),
            'managed_centers_count' => $centerIds->count(),
        ]);
    }

    /**
     * قائمة الاختبارات المتاحة ضمن نطاق المختبر.
     *
     * Arabic: يعتمد على `TestPolicy` لتقييد الاستعلام.
     * EN: Lists tests accessible to the examiner (policy-scoped).
     */
    public function tests(Request $request, TestPolicy $policy): JsonResponse
    {
        $user = $request->user();
        $query = Test::query()->with(['scopeHalaqah', 'scopeCenter', 'scopeRegion'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id');

        $query = $policy->scopeQueryForUser($user, $query);

        $rows = $query->limit(200)->get();

        return response()->json([
            'data' => $rows->map(fn (Test $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'type' => $t->type,
                'description' => $t->description,
                'scheduled_at' => $t->scheduled_at?->toIso8601String(),
                'is_published' => $t->is_published,
                'scope_halaqah_name' => $t->scopeHalaqah?->name,
                'scope_center_name' => $t->scopeCenter?->name,
                'scope_region_name' => $t->scopeRegion?->name,
            ])->values()->all(),
        ]);
    }

    /**
     * قائمة تعيينات الاختبارات ضمن نطاق المختبر.
     *
     * Arabic: يعيد عناصر مبسطة للاستخدام في قوائم الموبايل، مع مؤشر `has_result`.
     * EN: Lists assignments scoped to examiner with a has_result flag.
     */
    public function testAssignments(Request $request, TestAssignmentPolicy $policy): JsonResponse
    {
        $user = $request->user();
        $query = TestAssignment::query()
            ->with(['test:id,title', 'student:id,full_name', 'halaqah.center', 'result:id,test_assignment_id'])
            ->orderByDesc('id');

        $query = $policy->scopeQueryForUser($user, $query);

        $rows = $query->limit(300)->get();

        return response()->json([
            'data' => $rows->map(fn (TestAssignment $a) => [
                'id' => $a->id,
                'test_title' => $a->test?->title,
                'student_name' => $a->student?->full_name,
                'halaqah_name' => $a->halaqah?->name,
                'center_name' => $a->halaqah?->center?->name,
                'status' => $a->status,
                'has_result' => $a->result !== null,
            ])->values()->all(),
        ]);
    }

    /**
     * تعيينات اختبار محدد (للمختبر) — لا يعدّل استجابة القائمة العامة.
     *
     * Arabic: يستخدم عند بدء اختبار جديد لعرض قائمة الطلاب المعيّنين لهذا الاختبار
     * مع نتيجة مختصرة إن كانت موجودة.
     * EN: Lists assignments for a specific test (used by the stepper flow).
     */
    public function assignmentsForTest(Request $request, Test $test, TestPolicy $testPolicy, TestAssignmentPolicy $assignmentPolicy): JsonResponse
    {
        $user = $request->user();
        if (! $testPolicy->view($user, $test)) {
            abort(403, 'ليس لديك صلاحية عرض تعيينات هذا الاختبار');
        }

        $query = TestAssignment::query()
            ->where('test_id', $test->id)
            ->with(['student:id,full_name', 'halaqah.center', 'result:id,test_assignment_id,total_score,level']);

        $query = $assignmentPolicy->scopeQueryForUser($user, $query);

        $rows = $query->orderByDesc('id')->limit(500)->get();

        return response()->json([
            'data' => $rows->map(fn (TestAssignment $a) => [
                'id' => $a->id,
                'test_id' => $a->test_id,
                'student_id' => $a->student_id,
                'test_title' => $test->title,
                'student_name' => $a->student?->full_name,
                'halaqah_name' => $a->halaqah?->name,
                'center_name' => $a->halaqah?->center?->name,
                'status' => $a->status,
                'has_result' => $a->result !== null,
                'total_score' => $a->result?->total_score,
                'level' => $a->result?->level,
            ])->values()->all(),
        ]);
    }

    /**
     * قائمة نتائج الاختبارات ضمن نطاق المختبر.
     * EN: Lists test results accessible to the examiner (policy-scoped).
     */
    public function testResults(Request $request, TestResultPolicy $policy): JsonResponse
    {
        $user = $request->user();
        $query = TestResult::query()
            ->with(['assignment.test:id,title', 'assignment.student:id,full_name', 'assignment.halaqah.center'])
            ->orderByDesc('tested_at')
            ->orderByDesc('id');

        $query = $policy->scopeQueryForUser($user, $query);

        $rows = $query->limit(300)->get();

        return response()->json([
            'data' => $rows->map(fn (TestResult $r) => [
                'id' => $r->id,
                'test_title' => $r->assignment?->test?->title,
                'student_name' => $r->assignment?->student?->full_name,
                'halaqah_name' => $r->assignment?->halaqah?->name,
                'center_name' => $r->assignment?->halaqah?->center?->name,
                'level' => $r->level,
                'total_score' => $r->total_score,
                'tested_at' => $r->tested_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    /**
     * إحصاءات لوحة المختبر.
     *
     * Arabic: تُحسب ضمن نطاق مراكز المختبر، بالإضافة لإحصاءات يومية لنتائج المختبر نفسه.
     * EN: Dashboard stats including total/completed/pending and today's result metrics.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $centerIds = $user->managedCenters()->pluck('id');

        $base = TestAssignment::query()
            ->whereHas('halaqah', fn ($q) => $q->whereIn('center_id', $centerIds));

        $total = (clone $base)->count();
        $completed = (clone $base)->whereHas('result')->count();
        $pending = $total - $completed;

        $todayResults = TestResult::query()
            ->where('examiner_user_id', $user->id)
            ->whereDate('created_at', today())
            ->get();

        $todayCount = $todayResults->count();
        $avgScore = $todayResults->isNotEmpty()
            ? round((float) $todayResults->avg('total_score'), 1)
            : null;
        $highestScore = $todayResults->max('total_score');
        $lowestScore = $todayResults->min('total_score');

        return response()->json([
            'total_assignments' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'today_examined' => $todayCount,
            'today_avg_score' => $avgScore,
            'today_highest_score' => $highestScore !== null ? (int) $highestScore : null,
            'today_lowest_score' => $lowestScore !== null ? (int) $lowestScore : null,
        ]);
    }

    /**
     * تفاصيل طالب لاستخدامها قبل إدخال نتيجة الاختبار.
     *
     * Arabic: يتحقق أن الطالب ضمن نطاق مراكز المختبر، ويعيد آخر النتائج مع تسمية عربية للمستوى.
     * EN: Returns student details + recent results for examiner before submitting a new result.
     */
    public function studentDetail(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();
        $centerIds = $user->managedCenters()->pluck('id');

        $student->load(['currentEnrollment.halaqah.center']);
        $enrollment = $student->currentEnrollment;
        if (! $enrollment) {
            $enrollment = $student->enrollments()->where('status', Enrollment::STATUS_ACTIVE)->first();
            $enrollment?->load('halaqah.center');
        }

        $centerId = $enrollment?->halaqah?->center_id;
        abort_unless($centerId && $centerIds->contains($centerId), 403, 'ليس لديك صلاحية عرض هذا الطالب');

        $lastResults = TestResult::query()
            ->whereHas('assignment', fn ($q) => $q->where('student_id', $student->id))
            ->with(['assignment.test'])
            ->orderByDesc('tested_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $lastResultsPayload = $lastResults->map(fn (TestResult $r) => [
            'date' => ($r->tested_at ?? $r->created_at)->format('Y-m-d'),
            'total_score' => (int) $r->total_score,
            'memorization_score' => $r->memorization_score,
            'tajweed_score' => $r->tajweed_score,
            'review_score' => $r->review_score,
            'tested_surah' => $r->tested_surah,
            'notes' => $r->notes,
            'level_ar' => ExaminerScore::arabicLabelFromTotal((int) $r->total_score),
        ]);

        $resultsCount = TestResult::query()
            ->whereHas('assignment', fn ($q) => $q->where('student_id', $student->id))
            ->count();

        return response()->json([
            'id' => $student->id,
            'name' => $student->full_name,
            'photo_url' => PublicMediaUrl::forStoragePath($student->photo_path),
            'halaqah_name' => $enrollment?->halaqah?->name,
            'center_name' => $enrollment?->halaqah?->center?->name,
            'memorized_parts' => 0,
            'current_surah' => null,
            'last_results' => $lastResultsPayload,
            'results_count' => $resultsCount,
        ]);
    }

    /**
     * حفظ نتيجة اختبار لطالب ضمن تعيين محدد.
     *
     * Arabic: يتأكد من وجود `TestAssignment` لهذا الطالب/الاختبار ومن صلاحية المختبر،
     * ثم يحسب المجموع والمستوى ويحدث/ينشئ `TestResult`. بعد الحفظ يتم إشعار المعلّم.
     * EN: Validates assignment/authorization, computes totals/level, upserts TestResult, and notifies teacher.
     *
     * @throws ValidationException عند غياب التعيين أو فشل التحقق
     */
    public function storeResult(Request $request, TestAssignmentPolicy $assignmentPolicy): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'test_id' => 'required|exists:tests,id',
            'tested_surah' => 'required|string|max:100',
            'memorization_score' => 'required|integer|min:0|max:100',
            'tajweed_score' => 'required|integer|min:0|max:100',
            'review_score' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string|max:500',
            'client_recorded_at' => 'nullable|date',
        ]);

        $user = $request->user();

        $assignment = TestAssignment::query()
            ->where('test_id', $validated['test_id'])
            ->where('student_id', $validated['student_id'])
            ->with('halaqah')
            ->first();

        if (! $assignment) {
            throw ValidationException::withMessages([
                'test_id' => ['لا يوجد تعيين لهذا الطالب لهذا الاختبار.'],
            ]);
        }

        if (! $assignmentPolicy->view($user, $assignment)) {
            abort(403, 'ليس لديك صلاحية اختبار هذا الطالب');
        }

        $total = ExaminerScore::totalFromComponents(
            $validated['memorization_score'],
            $validated['tajweed_score'],
            $validated['review_score']
        );

        $levelEnum = ExaminerScore::levelEnumFromTotal($total);
        $arabicLevel = ExaminerScore::arabicLabelFromTotal($total);

        $audit = OfflineSync::auditColumns($validated['client_recorded_at'] ?? null);

        $result = TestResult::query()->firstOrNew(['test_assignment_id' => $assignment->id]);
        $previousTotal = $result->exists ? (float) $result->total_score : null;

        $result->fill([
            'examiner_user_id' => $user->id,
            'memorization_score' => $validated['memorization_score'],
            'tajweed_score' => $validated['tajweed_score'],
            'review_score' => $validated['review_score'],
            'total_score' => $total,
            'level' => $levelEnum,
            'tested_surah' => $validated['tested_surah'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if (! $result->exists) {
            // وقت الاختبار الفعلي على جهاز المختبِر، لا وقت وصول المزامنة للخادم.
            $result->tested_at = $audit['client_recorded_at'];
            $result->client_recorded_at = $audit['client_recorded_at'];
        }

        $result->synced_at = $audit['synced_at'];
        $result->save();

        $wasCreated = $result->wasRecentlyCreated;

        // إعادة إرسال نفس النتيجة من طابور المزامنة يجب ألّا تُغرق المعلّم بإشعارات مكررة.
        if ($wasCreated || $previousTotal !== (float) $total) {
            app(\App\Notifications\ExamResultNotification::class)->notifyTeacher($result);
        }

        return response()->json([
            'message' => 'تم حفظ نتيجة الاختبار بنجاح',
            'result_id' => $result->id,
            'total_score' => $total,
            'level' => $arabicLevel,
            'is_update' => ! $wasCreated,
        ], 201);
    }

    /**
     * ملخص يومي لنتائج المختبر.
     *
     * Arabic: يعيد توزيع المستويات العربية وقائمة نتائج مبسطة لليوم المحدد.
     * EN: Returns a daily summary of examiner results with distribution and list.
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->query('date', today()->format('Y-m-d'));

        $results = TestResult::query()
            ->where('examiner_user_id', $user->id)
            ->whereDate('created_at', $date)
            ->with(['assignment.student:id,full_name'])
            ->get();

        $distribution = [
            'ممتاز' => $results->filter(fn (TestResult $r) => (int) $r->total_score >= 90)->count(),
            'جيد جداً' => $results->filter(fn (TestResult $r) => (int) $r->total_score >= 75 && (int) $r->total_score < 90)->count(),
            'جيد' => $results->filter(fn (TestResult $r) => (int) $r->total_score >= 60 && (int) $r->total_score < 75)->count(),
            'مقبول' => $results->filter(fn (TestResult $r) => (int) $r->total_score >= 50 && (int) $r->total_score < 60)->count(),
            'ضعيف' => $results->filter(fn (TestResult $r) => (int) $r->total_score < 50)->count(),
        ];

        return response()->json([
            'date' => $date,
            'total_examined' => $results->count(),
            'avg_score' => $results->isNotEmpty() ? round((float) $results->avg('total_score'), 1) : 0.0,
            'highest_score' => $results->isNotEmpty() ? (int) $results->max('total_score') : 0,
            'lowest_score' => $results->isNotEmpty() ? (int) $results->min('total_score') : 0,
            'distribution' => $distribution,
            'results' => $results->map(fn (TestResult $r) => [
                'student_name' => $r->assignment?->student?->full_name,
                'total_score' => (int) $r->total_score,
                'tested_surah' => $r->tested_surah,
                'level_ar' => ExaminerScore::arabicLabelFromTotal((int) $r->total_score),
            ])->values()->all(),
        ]);
    }
}
