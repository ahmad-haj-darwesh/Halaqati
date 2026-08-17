<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\DailyEvaluation;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\MemorizationEntry;
use App\Models\SupervisorFieldVisit;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Notifications\SupervisoryVisitNotification;
use App\Support\OfflineSync;
use App\Support\PublicMediaUrl;
use App\Support\UserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * نقاط نهاية المشرف (Supervisor) لتطبيق الموبايل.
 *
 * Arabic: توفر بيانات الحساب، قوائم المراكز/الحلقات/المعلّمين ضمن نطاق المراكز المُدارة،
 * إحصاءات المشرف، تفاصيل معلّم، سجلات يومية للحلقة، تسجيل زيارة ميدانية، قائمة زياراتي،
 * وإحصاءات الحضور.
 * EN: Supervisor mobile API endpoints for scope-based listings, stats, teacher detail, halaqah daily, field visit creation, and attendance stats.
 */
class SupervisorApiController extends Controller
{
    /**
     * بيانات "me" للمشرف.
     * EN: Returns supervisor identity and managed centers count.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $centerIds = $user->managedCenters()->pluck('id');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'CenterSupervisor',
            'managed_centers_count' => $centerIds->count(),
        ]);
    }

    /**
     * قائمة المراكز ضمن نطاق المشرف.
     * EN: Lists centers available in supervisor scope.
     */
    public function centers(Request $request, UserScope $scope): JsonResponse
    {
        $user = $request->user();
        $q = Center::query()->with('region')->orderBy('name');
        $q = $scope->applyCentersToCentersQuery($user, $q);

        return response()->json([
            'data' => $q->get()->map(fn (Center $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'region_name' => $c->region?->name,
                'phone' => $c->phone,
                'address' => $c->address,
            ])->values()->all(),
        ]);
    }

    /**
     * قائمة الحلقات ضمن نطاق المشرف.
     * EN: Lists halaqahs available in supervisor scope.
     */
    public function halaqahs(Request $request, UserScope $scope): JsonResponse
    {
        $user = $request->user();
        $q = Halaqah::query()->with(['center.region', 'teacherProfile.user'])->orderBy('name');
        $q = $scope->applyCentersToHalaqahsQuery($user, $q);

        return response()->json([
            'data' => $q->get()->map(fn (Halaqah $h) => [
                'id' => $h->id,
                'name' => $h->name,
                'center_name' => $h->center?->name,
                'region_name' => $h->center?->region?->name,
                'teacher_name' => $h->teacherProfile?->user?->name,
                'teacher_user_id' => $h->teacherProfile?->user_id,
            ])->values()->all(),
        ]);
    }

    /**
     * قائمة المعلّمين ضمن نطاق المشرف مع آخر الزيارات ومتوسط آخر النتائج.
     * EN: Lists teachers in scope with last visit info and rolling average.
     */
    public function teachers(Request $request): JsonResponse
    {
        $user = $request->user();
        $centerIds = $user->managedCenters()->pluck('id');
        if ($centerIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $rows = TeacherProfile::query()
            ->with(['user:id,name,email', 'halaqah.center.region'])
            ->whereHas('halaqah', fn ($q) => $q->whereIn('center_id', $centerIds))
            ->orderBy('id')
            ->limit(500)
            ->get();

        $visitByTeacher = SupervisorFieldVisit::query()
            ->where('supervisor_user_id', $user->id)
            ->whereIn('teacher_user_id', $rows->pluck('user_id')->unique()->filter()->all())
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('teacher_user_id');

        return response()->json([
            'data' => $rows->map(function (TeacherProfile $p) use ($visitByTeacher) {
                $visits = $visitByTeacher->get($p->user_id, collect());
                $last = $visits->first();
                $avg = $visits->isNotEmpty()
                    ? round(
                        (float) $visits->avg(
                            fn (SupervisorFieldVisit $v) => ($v->teaching_skill_score + $v->plan_adherence_score + $v->student_engagement_score) / 3
                        ),
                        1
                    )
                    : null;

                return [
                    'id' => $p->id,
                    'user_id' => $p->user_id,
                    'teacher_name' => $p->user?->name,
                    'email' => $p->user?->email,
                    'phone' => $p->phone,
                    'qualification' => $p->qualification,
                    'halaqah_name' => $p->halaqah?->name,
                    'halaqah_id' => $p->halaqah_id,
                    'center_name' => $p->halaqah?->center?->name,
                    'center_id' => $p->halaqah?->center_id,
                    'region_name' => $p->halaqah?->center?->region?->name,
                    'last_visit_date' => $last ? $last->visit_date->format('Y-m-d') : null,
                    'avg_visit_score' => $avg,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * إحصاءات مختصرة للمشرف (هذا الشهر + آخر 7 أيام للحضور).
     * EN: Supervisor dashboard stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $centerIds = $user->managedCenters()->pluck('id');

        if ($centerIds->isEmpty()) {
            return response()->json([
                'visits_this_month' => 0,
                'avg_teaching_score' => 0.0,
                'avg_plan_score' => 0.0,
                'avg_engagement' => 0.0,
                'unvisited_halaqahs' => 0,
                'attendance_rate_7d' => null,
                'centers_count' => 0,
            ]);
        }

        $now = now();

        $visitsThisMonth = SupervisorFieldVisit::query()
            ->where('supervisor_user_id', $user->id)
            ->whereMonth('visit_date', $now->month)
            ->whereYear('visit_date', $now->year)
            ->count();

        $avgRow = SupervisorFieldVisit::query()
            ->where('supervisor_user_id', $user->id)
            ->whereMonth('visit_date', $now->month)
            ->whereYear('visit_date', $now->year)
            ->selectRaw('
                AVG(teaching_skill_score) as avg_teaching,
                AVG(plan_adherence_score) as avg_plan,
                AVG(student_engagement_score) as avg_engagement
            ')
            ->first();

        // معلّم يُعتبر «مُزاراً» ضمن نافذة 30 يوماً إن وُجدت له زيارة بتاريخ ضمن هذه الفترة (وليس تاريخاً قديماً جداً).
        $visitedTeacherIds = SupervisorFieldVisit::query()
            ->where('supervisor_user_id', $user->id)
            ->whereDate('visit_date', '>=', $now->copy()->subDays(30)->toDateString())
            ->pluck('teacher_user_id')
            ->unique()
            ->filter()
            ->values();

        $unvisitedHalaqahs = 0;
        if ($visitedTeacherIds->isEmpty()) {
            $unvisitedHalaqahs = Halaqah::query()
                ->whereIn('center_id', $centerIds)
                ->whereHas('teacherProfile')
                ->count();
        } else {
            $unvisitedHalaqahs = Halaqah::query()
                ->whereIn('center_id', $centerIds)
                ->whereHas('teacherProfile', fn (Builder $q) => $q->whereNotIn('user_id', $visitedTeacherIds->all()))
                ->count();
        }

        $from = now()->subDays(7)->toDateString();
        $totalAtt = AttendanceRecord::query()
            ->whereHas('halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $centerIds))
            ->where('date', '>=', $from)
            ->count();
        $presentAtt = AttendanceRecord::query()
            ->whereHas('halaqah', fn (Builder $hq) => $hq->whereIn('center_id', $centerIds))
            ->where('date', '>=', $from)
            ->where('status', AttendanceRecord::STATUS_PRESENT)
            ->count();

        $rate = $totalAtt > 0
            ? round(($presentAtt / $totalAtt) * 100, 1)
            : null;

        return response()->json([
            'visits_this_month' => $visitsThisMonth,
            'avg_teaching_score' => round((float) ($avgRow->avg_teaching ?? 0), 1),
            'avg_plan_score' => round((float) ($avgRow->avg_plan ?? 0), 1),
            'avg_engagement' => round((float) ($avgRow->avg_engagement ?? 0), 1),
            'unvisited_halaqahs' => $unvisitedHalaqahs,
            'attendance_rate_7d' => $rate,
            'centers_count' => $centerIds->count(),
        ]);
    }

    /**
     * تفاصيل معلّم ضمن نطاق المشرف مع آخر الزيارات ومؤشرات ملخصة.
     * EN: Teacher detail endpoint for supervisor.
     */
    public function teacherDetail(Request $request, User $teacher): JsonResponse
    {
        $user = $request->user();
        $centerIds = $user->managedCenters()->pluck('id');

        $teacher->load(['teacherProfile.halaqah.center']);
        $profile = $teacher->teacherProfile;
        if (! $profile || ! $profile->halaqah) {
            abort(403, 'ليس لديك صلاحية عرض هذا المعلم');
        }
        abort_unless($centerIds->contains($profile->halaqah->center_id), 403, 'ليس لديك صلاحية عرض هذا المعلم');

        $visitModels = SupervisorFieldVisit::query()
            ->where('supervisor_user_id', $user->id)
            ->where('teacher_user_id', $teacher->id)
            ->latest('visit_date')
            ->latest('id')
            ->take(3)
            ->get();

        $lastVisits = $visitModels->map(function (SupervisorFieldVisit $v) {
            $avg = round(
                ($v->teaching_skill_score + $v->plan_adherence_score + $v->student_engagement_score) / 3,
                1
            );

            return [
                'date' => $v->visit_date->format('Y-m-d'),
                'teaching_skill_score' => $v->teaching_skill_score,
                'plan_adherence_score' => $v->plan_adherence_score,
                'student_engagement_score' => $v->student_engagement_score,
                'avg_score' => $avg,
                'notes' => $v->notes,
                'recommendations' => $v->recommendations,
            ];
        });

        $lastVisitDate = SupervisorFieldVisit::query()
            ->where('supervisor_user_id', $user->id)
            ->where('teacher_user_id', $teacher->id)
            ->max('visit_date');

        $studentCount = Enrollment::query()
            ->where('halaqah_id', $profile->halaqah_id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->count();

        $overallAvg = $lastVisits->isNotEmpty()
            ? round((float) $lastVisits->pluck('avg_score')->avg(), 1)
            : null;

        return response()->json([
            'id' => $teacher->id,
            'name' => $teacher->name,
            'photo_url' => PublicMediaUrl::forStoragePath($profile->photo_path),
            'phone' => $profile->phone,
            'halaqahs' => [
                [
                    'id' => $profile->halaqah->id,
                    'name' => $profile->halaqah->name,
                    'center_name' => $profile->halaqah->center?->name,
                ],
            ],
            'student_count' => $studentCount,
            'last_visit_date' => $lastVisitDate ? (string) $lastVisitDate : null,
            'visits_count' => SupervisorFieldVisit::query()
                ->where('supervisor_user_id', $user->id)
                ->where('teacher_user_id', $teacher->id)
                ->count(),
            'last_visits' => $lastVisits->values()->all(),
            'overall_avg' => $overallAvg,
        ]);
    }

    /**
     * السجل اليومي للحلقة (حضور/تقييم/حفظ) في تاريخ محدد.
     * EN: Halaqah daily overview for a given date.
     */
    public function halaqahDaily(Request $request, Halaqah $halaqah): JsonResponse
    {
        $user = $request->user();
        $centerIds = $user->managedCenters()->pluck('id');
        $date = $request->query('date', now()->format('Y-m-d'));

        abort_unless($centerIds->contains($halaqah->center_id), 403, 'ليس لديك صلاحية عرض هذه الحلقة');

        $halaqah->load(['center', 'teacherProfile.user']);

        $enrollments = Enrollment::query()
            ->where('halaqah_id', $halaqah->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->with('student')
            ->orderBy('id')
            ->get();

        $records = $enrollments->map(function (Enrollment $enrollment) use ($halaqah, $date) {
            $student = $enrollment->student;
            $att = AttendanceRecord::query()
                ->where('student_id', $student->id)
                ->where('halaqah_id', $halaqah->id)
                ->whereDate('date', $date)
                ->first();

            $eval = DailyEvaluation::query()
                ->where('student_id', $student->id)
                ->where('halaqah_id', $halaqah->id)
                ->whereDate('date', $date)
                ->first();

            $memo = MemorizationEntry::query()
                ->where('student_id', $student->id)
                ->where('halaqah_id', $halaqah->id)
                ->whereDate('date', $date)
                ->first();

            $attStatus = $this->mapAttendanceToApi($att?->status);

            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'attendance_status' => $attStatus,
                'performance_status' => $eval?->overall,
                'memorization_from' => $memo?->memorization_from,
                'memorization_to' => $memo?->memorization_to,
                'notes' => $eval?->general_note ?? $att?->notes,
            ];
        });

        $summary = [
            'total' => $records->count(),
            'present' => $records->where('attendance_status', 'present')->count(),
            'absent_excused' => $records->where('attendance_status', 'absent_excused')->count(),
            'absent_unexcused' => $records->where('attendance_status', 'absent_unexcused')->count(),
            'not_recorded' => $records->where('attendance_status', 'not_recorded')->count(),
        ];
        $summary['attendance_rate'] = $summary['total'] > 0
            ? (int) round(($summary['present'] / $summary['total']) * 100)
            : 0;

        return response()->json([
            'halaqah_name' => $halaqah->name,
            'center_name' => $halaqah->center?->name,
            'teacher_name' => $halaqah->teacherProfile?->user?->name,
            'date' => $date,
            'summary' => $summary,
            'records' => $records->values()->all(),
        ]);
    }

    /**
     * تسجيل زيارة ميدانية (SupervisorFieldVisit) لمعلّم ضمن مركز.
     *
     * Arabic: يتحقق من أن المعلّم ينتمي للمركز، ثم ينشئ الزيارة ويشعّر المعلّم.
     * العملية **idempotent** عبر `client_uuid` الذي يولّده التطبيق مرة واحدة لكل زيارة:
     * لو انقطع الاتصال بعد وصول الطلب وقبل وصول الرد، تعيد طبقةُ المزامنة الإرسالَ
     * بنفس المعرّف فيعيد الخادم الزيارة نفسها بدل إنشاء زيارة مكررة وإشعار ثانٍ للمعلّم.
     * EN: Creates a field visit (idempotent via the client-generated `client_uuid`),
     * validating teacher-center membership and notifying the teacher once.
     *
     * @throws ValidationException عند فشل التحقق من انتماء المعلّم للمركز.
     */
    public function storeVisit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_uuid' => 'nullable|uuid',
            'client_recorded_at' => 'nullable|date',
            'teacher_id' => 'required|exists:users,id',
            'center_id' => 'required|exists:centers,id',
            'visit_date' => 'required|date|before_or_equal:today',
            'teaching_skill_score' => 'required|integer|min:1|max:10',
            'plan_adherence_score' => 'required|integer|min:1|max:10',
            'student_engagement_score' => 'required|integer|min:1|max:10',
            'notes' => 'nullable|string|max:1000',
            'recommendations' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $clientUuid = $validated['client_uuid'] ?? null;

        // إعادة إرسال من طابور المزامنة: أعِد الزيارة الموجودة بدل إنشاء نسخة ثانية.
        if ($clientUuid !== null) {
            $existing = SupervisorFieldVisit::query()->where('client_uuid', $clientUuid)->first();

            if ($existing !== null) {
                abort_unless(
                    (int) $existing->supervisor_user_id === (int) $user->id,
                    403,
                    'معرّف الزيارة مستخدم من حساب آخر'
                );

                return $this->visitResponse($existing, wasCreated: false);
            }
        }

        $centerIds = $user->managedCenters()->pluck('id');

        abort_unless($centerIds->contains((int) $validated['center_id']), 403, 'ليس لديك صلاحية تسجيل زيارة لهذا المركز');

        $teacherInCenter = TeacherProfile::query()
            ->where('user_id', $validated['teacher_id'])
            ->whereHas('halaqah', fn (Builder $q) => $q->where('center_id', $validated['center_id']))
            ->exists();

        if (! $teacherInCenter) {
            throw ValidationException::withMessages([
                'teacher_id' => ['هذا المعلم لا ينتمي إلى هذا المركز.'],
            ]);
        }

        $attributes = [
            'client_uuid' => $clientUuid,
            'supervisor_user_id' => $user->id,
            'teacher_user_id' => $validated['teacher_id'],
            'center_id' => $validated['center_id'],
            'visit_date' => $validated['visit_date'],
            'teaching_skill_score' => $validated['teaching_skill_score'],
            'plan_adherence_score' => $validated['plan_adherence_score'],
            'student_engagement_score' => $validated['student_engagement_score'],
            'notes' => $validated['notes'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'status' => 'completed',
        ] + OfflineSync::auditColumns($validated['client_recorded_at'] ?? null);

        try {
            $visit = SupervisorFieldVisit::create($attributes);
        } catch (UniqueConstraintViolationException) {
            // سباق بين طلبين متزامنين بنفس المعرّف: الفهرس الفريد حسم الأمر، فنعيد الفائز.
            $visit = SupervisorFieldVisit::query()->where('client_uuid', $clientUuid)->firstOrFail();

            return $this->visitResponse($visit, wasCreated: false);
        }

        app(SupervisoryVisitNotification::class)->notifyTeacher($visit);

        return $this->visitResponse($visit, wasCreated: true);
    }

    /**
     * استجابة موحّدة لتسجيل زيارة — تُستخدم للإنشاء ولإعادة الإرسال على السواء.
     *
     * Arabic: `was_created=false` تخبر التطبيق أن الزيارة كانت محفوظة مسبقاً، فيزيلها
     * من طابور المزامنة دون اعتبارها خطأً ودون تكرار رسالة النجاح.
     * EN: Shared response for create and idempotent replay.
     */
    private function visitResponse(SupervisorFieldVisit $visit, bool $wasCreated): JsonResponse
    {
        $avgScore = round(
            ($visit->teaching_skill_score + $visit->plan_adherence_score + $visit->student_engagement_score) / 3,
            1
        );

        return response()->json([
            'message' => $wasCreated ? 'تم تسجيل الزيارة الإشرافية بنجاح' : 'الزيارة مسجّلة مسبقاً',
            'visit_id' => $visit->id,
            'client_uuid' => $visit->client_uuid,
            'avg_score' => $avgScore,
            'date' => $visit->visit_date->format('Y-m-d'),
            'was_created' => $wasCreated,
        ], $wasCreated ? 201 : 200);
    }

    /**
     * قائمة زيارات المشرف (paginated).
     * EN: Returns paginated list of supervisor field visits.
     */
    public function myVisits(Request $request): JsonResponse
    {
        $user = $request->user();

        $visits = SupervisorFieldVisit::query()
            ->where('supervisor_user_id', $user->id)
            ->with(['teacher:id,name', 'center:id,name'])
            ->latest('visit_date')
            ->latest('id')
            ->paginate(20);

        $data = $visits->getCollection()->map(function (SupervisorFieldVisit $v) {
            $avg = round(
                ($v->teaching_skill_score + $v->plan_adherence_score + $v->student_engagement_score) / 3,
                1
            );

            return [
                'id' => $v->id,
                'visit_date' => $v->visit_date->format('Y-m-d'),
                'teacher_name' => $v->teacher?->name,
                'center_name' => $v->center?->name,
                'teaching_skill_score' => $v->teaching_skill_score,
                'plan_adherence_score' => $v->plan_adherence_score,
                'student_engagement_score' => $v->student_engagement_score,
                'avg_score' => $avg,
                'notes' => $v->notes,
                'recommendations' => $v->recommendations,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'total' => $visits->total(),
            'current_page' => $visits->currentPage(),
            'last_page' => $visits->lastPage(),
        ]);
    }

    /**
     * إحصاءات الحضور ضمن المراكز المُدارة لفترة محددة (days).
     * EN: Attendance statistics for managed centers over a window of days.
     */
    public function attendanceStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $centerIds = $user->managedCenters()->pluck('id');
        $days = max(1, min(90, (int) $request->query('days', 7)));

        if ($centerIds->isEmpty()) {
            return response()->json([
                'period_days' => $days,
                'halaqahs' => [],
                'overall_rate' => 0.0,
            ]);
        }

        $from = now()->subDays($days)->toDateString();

        $halaqahs = Halaqah::query()
            ->whereIn('center_id', $centerIds)
            ->orderBy('name')
            ->get();

        $result = $halaqahs->map(function (Halaqah $h) use ($from) {
            $records = AttendanceRecord::query()
                ->where('halaqah_id', $h->id)
                ->where('date', '>=', $from)
                ->get();

            $total = $records->count();
            $present = $records->where('status', AttendanceRecord::STATUS_PRESENT)->count();
            $rate = $total > 0 ? (int) round(($present / $total) * 100) : 0;

            return [
                'halaqah_id' => $h->id,
                'halaqah_name' => $h->name,
                'center_id' => $h->center_id,
                'attendance_rate' => $rate,
                'total_records' => $total,
                'present_count' => $present,
            ];
        })->sortByDesc('attendance_rate')->values();

        $overall = $result->isNotEmpty()
            ? round($result->avg('attendance_rate'), 1)
            : 0.0;

        return response()->json([
            'period_days' => $days,
            'halaqahs' => $result,
            'overall_rate' => $overall,
        ]);
    }

    /**
     * تحويل حالة الحضور المخزنة إلى قيم API للموبايل.
     * EN: Maps internal attendance status to API values.
     */
    private function mapAttendanceToApi(?string $status): string
    {
        return match ($status) {
            AttendanceRecord::STATUS_PRESENT => 'present',
            AttendanceRecord::STATUS_EXCUSED => 'absent_excused',
            AttendanceRecord::STATUS_UNEXCUSED => 'absent_unexcused',
            default => 'not_recorded',
        };
    }
}
