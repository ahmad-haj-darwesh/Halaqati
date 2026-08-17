<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\DailyEvaluation;
use App\Models\Enrollment;
use App\Models\EvaluationReason;
use App\Models\MemorizationEntry;
use App\Models\Student;
use App\Models\User;
use App\Policies\TeacherDailyRecordsPolicy;
use App\Support\OfflineSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * نقاط نهاية تسجيلات المعلم اليومية (Attendance/Evaluation/Memorization).
 *
 * Arabic: توفّر استرجاع بيانات اليوم (طلاب + أسباب) وحفظ السجلات دفعة واحدة مع
 * احترام سياسة الصلاحيات وقيود مهلة تعديل التقييم.
 * EN: Teacher daily records endpoints for retrieving and upserting today's records.
 */
class TeacherDailyController extends Controller
{
    /**
     * بيانات يوم للمعلم (اليوم الحالي افتراضياً).
     *
     * Arabic: يعيد قائمة الطلاب النشطين في حلقة المعلم مع سجلات ذلك اليوم (إن وجدت)
     * وأسباب التقييم المفعّلة. يقبل `?date=YYYY-MM-DD` ضمن نافذة المزامنة، ليتمكن
     * التطبيق من التحقق ممّا وصل فعلاً للخادم بعد تفريغ طابور العمل دون إنترنت.
     * EN: Returns a day's roster with its records; accepts an in-window `date` query
     * param so the app can reconcile after draining its offline queue.
     *
     * @throws ValidationException عند تاريخ خارج نافذة المزامنة
     */
    public function today(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $halaqahId = $user->teacherProfile?->halaqah_id;

        $now = Carbon::now();
        $date = $now->toDateString();

        $requestedDate = $request->query('date');
        if (is_string($requestedDate) && $requestedDate !== '') {
            if (! OfflineSync::isDateWithinWindow($requestedDate, $now)) {
                throw ValidationException::withMessages([
                    'date' => [OfflineSync::outOfWindowMessage()],
                ]);
            }

            $date = Carbon::parse($requestedDate)->toDateString();
        }

        if (! $halaqahId) {
            return response()->json([
                'date' => $date,
                'halaqah_id' => null,
                'students' => [],
                'reasons' => [],
            ]);
        }

        $students = Student::query()
            ->where('is_active', true)
            ->whereHas('enrollments', function (Builder $q) use ($halaqahId) {
                $q->where('halaqah_id', $halaqahId)->where('status', Enrollment::STATUS_ACTIVE);
            })
            ->with([
                'attendanceRecords' => fn ($q) => $q->where('date', $date),
                'dailyEvaluations' => fn ($q) => $q->where('date', $date)->with('reasons'),
                'memorizationEntries' => fn ($q) => $q->where('date', $date),
            ])
            ->orderBy('full_name')
            ->get()
            ->map(function (Student $s) use ($date) {
                $attendance = $s->attendanceRecords->first();
                $eval = $s->dailyEvaluations->first();
                $memo = $s->memorizationEntries->first();

                return [
                    'id' => $s->id,
                    'full_name' => $s->full_name,
                    'date' => $date,
                    'attendance' => $attendance ? [
                        'status' => $attendance->status,
                        'notes' => $attendance->notes,
                    ] : null,
                    'evaluation' => $eval ? [
                        'overall' => $eval->overall,
                        'general_note' => $eval->general_note,
                        'reason_ids' => $eval->reasons->pluck('id')->all(),
                        'can_edit' => $eval->canTeacherEditEvaluation(),
                    ] : null,
                    'memorization' => $memo ? [
                        'memorization_from' => $memo->memorization_from,
                        'memorization_to' => $memo->memorization_to,
                        'revision_from' => $memo->revision_from,
                        'revision_to' => $memo->revision_to,
                        'mistakes' => $memo->mistakes,
                    ] : null,
                ];
            })
            ->values();

        $reasons = EvaluationReason::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get(['id', 'key', 'label', 'type']);

        return response()->json([
            'date' => $date,
            'halaqah_id' => $halaqahId,
            'students' => $students,
            'reasons' => $reasons,
        ]);
    }

    /**
     * حفظ/تحديث السجلات اليومية (Upsert) — بما يشمل المزامنة المؤجلة من طابور التطبيق.
     *
     * Arabic: يقبل اليوم الحالي أو أي يوم ضمن نافذة `offline.sync_window_days`، حتى لا
     * يضيع عمل معلّم سجّل دون إنترنت وتأخرت مزامنته. يتحقق من صلاحية الكتابة لكل طالب،
     * ويمنع تعديل التقييم بعد انتهاء نافذة التعديل إذا كان التغيير سيؤثر على القيم الحالية.
     * العملية idempotent بطبيعتها (`updateOrCreate` على `student_id`+`date`) فإعادة إرسال
     * نفس الدفعة بعد فشل شبكي لا تُنتج تكراراً.
     * EN: Upserts daily records for today or any date inside the offline sync window,
     * enforcing authorization and evaluation edit-window rules.
     *
     * @throws ValidationException عند تاريخ خارج نافذة المزامنة أو تعديل تقييم بعد انتهاء المهلة
     */
    public function upsert(Request $request, TeacherDailyRecordsPolicy $policy): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $halaqahId = $user->teacherProfile?->halaqah_id;
        abort_unless($halaqahId, 422);

        $payload = $request->validate([
            'date' => ['nullable', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.attendance_status' => ['nullable', Rule::in([
                AttendanceRecord::STATUS_PRESENT,
                AttendanceRecord::STATUS_EXCUSED,
                AttendanceRecord::STATUS_UNEXCUSED,
            ])],
            'records.*.attendance_note' => ['nullable', 'string'],
            'records.*.evaluation_overall' => ['nullable', Rule::in([
                DailyEvaluation::OVERALL_EXCELLENT,
                DailyEvaluation::OVERALL_GOOD,
                DailyEvaluation::OVERALL_NEEDS_IMPROVEMENT,
                DailyEvaluation::OVERALL_NONE,
            ])],
            'records.*.reason_ids' => ['nullable', 'array'],
            'records.*.reason_ids.*' => ['integer', 'exists:evaluation_reasons,id'],
            'records.*.general_note' => ['nullable', 'string'],
            'records.*.memorization_from' => ['nullable', 'string'],
            'records.*.memorization_to' => ['nullable', 'string'],
            'records.*.revision_from' => ['nullable', 'string'],
            'records.*.revision_to' => ['nullable', 'string'],
            'records.*.mistakes' => ['nullable', 'string'],
            'records.*.client_recorded_at' => ['nullable', 'date'],
        ]);

        $now = Carbon::now();
        $today = $now->toDateString();
        $date = $today;

        if (isset($payload['date']) && $payload['date'] !== null && $payload['date'] !== '') {
            $requested = Carbon::parse($payload['date'])->toDateString();

            if (! OfflineSync::isDateWithinWindow($requested, $now)) {
                throw ValidationException::withMessages([
                    'date' => [OfflineSync::outOfWindowMessage()],
                ]);
            }

            $date = $requested;
        }

        $studentIds = collect($payload['records'])->pluck('student_id')->unique()->values();

        $students = Student::whereIn('id', $studentIds)->get()->keyBy('id');

        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);
            abort_unless($student, 422);
            abort_unless($policy->teacherCanWriteForStudent($user, $student, (int) $halaqahId), 403);
        }

        DB::transaction(function () use ($payload, $date, $halaqahId, $user, $students, $now) {
            foreach ($payload['records'] as $r) {
                $studentId = (int) $r['student_id'];
                $audit = OfflineSync::auditColumns($r['client_recorded_at'] ?? null, $now);

                if (! empty($r['attendance_status'])) {
                    $this->saveWithAudit(
                        AttendanceRecord::firstOrNew(['student_id' => $studentId, 'date' => $date]),
                        [
                            'halaqah_id' => $halaqahId,
                            'status' => $r['attendance_status'],
                            'recorded_by_user_id' => $user->id,
                            'notes' => $r['attendance_note'] ?? null,
                        ],
                        $audit
                    );
                }

                if (array_key_exists('evaluation_overall', $r) || array_key_exists('reason_ids', $r) || array_key_exists('general_note', $r)) {
                    $existingEval = DailyEvaluation::query()
                        ->where('student_id', $studentId)
                        ->where('date', $date)
                        ->with('reasons')
                        ->first();

                    if ($existingEval !== null && ! $existingEval->canTeacherEditEvaluation()) {
                        if ($this->evaluationPayloadWouldChangeExisting($existingEval, $r)) {
                            $name = $students->get($studentId)?->full_name ?? (string) $studentId;

                            throw ValidationException::withMessages([
                                'records' => [
                                    "لم يعد مسموحاً بتعديل التقييم بعد مرور ساعة من أول إرسال. (الطالب: {$name})",
                                ],
                            ]);
                        }

                        continue;
                    }

                    $overall = $r['evaluation_overall'] ?? DailyEvaluation::OVERALL_NONE;
                    $eval = $this->saveWithAudit(
                        $existingEval ?? DailyEvaluation::firstOrNew(['student_id' => $studentId, 'date' => $date]),
                        [
                            'halaqah_id' => $halaqahId,
                            'overall' => $overall,
                            'recorded_by_user_id' => $user->id,
                            'general_note' => $r['general_note'] ?? null,
                        ],
                        $audit
                    );

                    $reasonIds = collect($r['reason_ids'] ?? [])->unique()->values()->all();
                    $eval->reasons()->sync($reasonIds);
                }

                if (
                    array_key_exists('memorization_from', $r) ||
                    array_key_exists('memorization_to', $r) ||
                    array_key_exists('revision_from', $r) ||
                    array_key_exists('revision_to', $r) ||
                    array_key_exists('mistakes', $r)
                ) {
                    $this->saveWithAudit(
                        MemorizationEntry::firstOrNew(['student_id' => $studentId, 'date' => $date]),
                        [
                            'halaqah_id' => $halaqahId,
                            'memorization_from' => $r['memorization_from'] ?? null,
                            'memorization_to' => $r['memorization_to'] ?? null,
                            'revision_from' => $r['revision_from'] ?? null,
                            'revision_to' => $r['revision_to'] ?? null,
                            'mistakes' => $r['mistakes'] ?? null,
                        ],
                        $audit
                    );
                }
            }
        });

        return response()->json(['message' => 'saved', 'date' => $date]);
    }

    /**
     * حفظ سجل مع أعمدة تدقيق المزامنة.
     *
     * Arabic: `client_recorded_at` يُضبط **عند الإنشاء فقط** لأنه يمثل لحظة أول إدخال
     * على الجهاز، وعليه تُحتسب نافذة تعديل التقييم — لو أعدنا ضبطه مع كل مزامنة
     * لأمكن تمديد النافذة إلى ما لا نهاية بإعادة الإرسال. أما `synced_at` فيُحدَّث دائماً
     * لأنه يوثّق آخر وصول فعلي للخادم.
     * EN: Persists a record with sync audit columns; the client timestamp is written
     * once on creation so the evaluation edit window cannot be extended by re-syncing.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array{client_recorded_at: \Illuminate\Support\Carbon, synced_at: \Illuminate\Support\Carbon}  $audit
     *
     * @template TModel of Model
     *
     * @phpstan-param TModel $model
     *
     * @phpstan-return TModel
     */
    private function saveWithAudit(Model $model, array $attributes, array $audit): Model
    {
        $model->fill($attributes);

        if (! $model->exists) {
            $model->client_recorded_at = $audit['client_recorded_at'];
        }

        $model->synced_at = $audit['synced_at'];
        $model->save();

        return $model;
    }

    /**
     * هل ستغيّر حمولة التقييم قيماً موجودة بعد انتهاء مهلة التعديل؟
     *
     * Arabic: يستخدم لمنع تعديل التقييم بعد مرور ساعة، مع السماح بإعادة إرسال
     * نفس القيم دون تغيير (لتفادي أخطاء مزامنة من الواجهة).
     * EN: Determines whether the incoming payload would change persisted evaluation values.
     */
    private function evaluationPayloadWouldChangeExisting(DailyEvaluation $existing, array $r): bool
    {
        $newOverall = $r['evaluation_overall'] ?? DailyEvaluation::OVERALL_NONE;
        if ($existing->overall !== $newOverall) {
            return true;
        }

        $newNote = $r['general_note'] ?? null;
        if (($existing->general_note ?? '') !== ($newNote ?? '')) {
            return true;
        }

        $newIds = collect($r['reason_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
        $oldIds = $existing->reasons->pluck('id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return $newIds !== $oldIds;
    }

    /**
     * تقرير شهري مبسّط لحلقة المعلم.
     *
     * Arabic: يعيد إحصاءات الحضور والتقييم وأسباب التقييم الأكثر تكراراً ضمن شهر محدد.
     * EN: Returns monthly aggregates (attendance/evaluations/top reasons) for the teacher's halaqah.
     */
    public function monthly(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $halaqahId = $user->teacherProfile?->halaqah_id;
        abort_unless($halaqahId, 422);

        $month = $request->query('month', now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month) === 1, 422);

        $start = "{$month}-01";
        $end = now()->createFromFormat('Y-m-d', $start)->endOfMonth()->toDateString();

        $attendanceCounts = AttendanceRecord::query()
            ->where('halaqah_id', $halaqahId)
            ->whereBetween('date', [$start, $end])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $evaluationCounts = DailyEvaluation::query()
            ->where('halaqah_id', $halaqahId)
            ->whereBetween('date', [$start, $end])
            ->select('overall', DB::raw('count(*) as total'))
            ->groupBy('overall')
            ->pluck('total', 'overall');

        $reasonBreakdown = DB::table('daily_evaluation_reason as der')
            ->join('daily_evaluations as de', 'de.id', '=', 'der.daily_evaluation_id')
            ->join('evaluation_reasons as er', 'er.id', '=', 'der.evaluation_reason_id')
            ->where('de.halaqah_id', $halaqahId)
            ->whereBetween('de.date', [$start, $end])
            ->groupBy('er.id', 'er.key', 'er.label', 'er.type')
            ->select('er.id', 'er.key', 'er.label', 'er.type', DB::raw('count(*) as total'))
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'month' => $month,
            'range' => ['start' => $start, 'end' => $end],
            'attendance' => [
                AttendanceRecord::STATUS_PRESENT => (int) ($attendanceCounts[AttendanceRecord::STATUS_PRESENT] ?? 0),
                AttendanceRecord::STATUS_EXCUSED => (int) ($attendanceCounts[AttendanceRecord::STATUS_EXCUSED] ?? 0),
                AttendanceRecord::STATUS_UNEXCUSED => (int) ($attendanceCounts[AttendanceRecord::STATUS_UNEXCUSED] ?? 0),
            ],
            'evaluations' => [
                DailyEvaluation::OVERALL_EXCELLENT => (int) ($evaluationCounts[DailyEvaluation::OVERALL_EXCELLENT] ?? 0),
                DailyEvaluation::OVERALL_GOOD => (int) ($evaluationCounts[DailyEvaluation::OVERALL_GOOD] ?? 0),
                DailyEvaluation::OVERALL_NEEDS_IMPROVEMENT => (int) ($evaluationCounts[DailyEvaluation::OVERALL_NEEDS_IMPROVEMENT] ?? 0),
                DailyEvaluation::OVERALL_NONE => (int) ($evaluationCounts[DailyEvaluation::OVERALL_NONE] ?? 0),
            ],
            'reasons' => $reasonBreakdown,
        ]);
    }
}
