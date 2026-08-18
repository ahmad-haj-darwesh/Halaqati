<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\DailyEvaluation;
use App\Models\Enrollment;
use App\Models\EvaluationReason;
use App\Models\SupervisoryVisit;
use App\Models\TestAssignment;
use App\Models\TestResult;
use App\Support\UserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * خدمة مؤشرات الأداء للوحة التقارير (KPIs).
 *
 * Arabic: تبني إحصاءات مجمّعة (حضور/تقييمات/اختبارات/إشراف) حسب نطاق عرض
 * (مركز/منطقة/حلقة) مع احترام صلاحيات المستخدم عبر `UserScope`.
 * EN: Builds dashboard KPIs aggregated by scope, respecting user permissions.
 */
class DashboardKpiService
{
    public function __construct(private readonly UserScope $scope) {}

    /**
     * الحصول على مؤشرات لوحة التقارير ضمن نطاق محدد وفترة زمنية.
     *
     * Arabic: يقوم بتطبيع التاريخ، حل نطاق المراكز/الحلقات، ثم حساب:
     * - عدد الطلاب النشطين\n+     * - توزيع الحضور والنسب\n+     * - توزيع التقييمات اليومية\n+     * - متوسطات الاختبارات وتوزيع المستويات\n+     * - متوسط درجة الزيارات وأضعف بنود التقييم
     *
     * EN: Computes KPIs (attendance, evaluations, tests, supervision) for a given scope and date range.
     *
     * @return array<string,mixed>
     */
    public function kpisByScope(string $scopeType, ?int $scopeId, string $dateFrom, string $dateTo, \App\Models\User $user): array
    {
        [$from, $to] = $this->normalizeDates($dateFrom, $dateTo);

        $centerIds = $this->resolveCenterIdsForScope($user, $scopeType, $scopeId);
        $halaqahIds = $this->resolveHalaqahIdsForScope($centerIds, $scopeType, $scopeId);

        $activeStudents = Enrollment::query()
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->whereIn('halaqah_id', $halaqahIds)
            ->distinct('student_id')
            ->count('student_id');

        $attendanceCounts = AttendanceRecord::query()
            ->whereBetween('date', [$from, $to])
            ->whereIn('halaqah_id', $halaqahIds)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = (int) ($attendanceCounts[AttendanceRecord::STATUS_PRESENT] ?? 0);
        $excused = (int) ($attendanceCounts[AttendanceRecord::STATUS_EXCUSED] ?? 0);
        $unexcused = (int) ($attendanceCounts[AttendanceRecord::STATUS_UNEXCUSED] ?? 0);
        $attendanceTotal = max(1, $present + $excused + $unexcused);

        $dailyEvalCounts = DailyEvaluation::query()
            ->whereBetween('date', [$from, $to])
            ->whereIn('halaqah_id', $halaqahIds)
            ->select('overall', DB::raw('COUNT(*) as total'))
            ->groupBy('overall')
            ->pluck('total', 'overall');

        $topReasons = DB::table('daily_evaluation_reason as der')
            ->join('daily_evaluations as de', 'de.id', '=', 'der.daily_evaluation_id')
            ->join('evaluation_reasons as er', 'er.id', '=', 'der.evaluation_reason_id')
            ->whereBetween('de.date', [$from, $to])
            ->whereIn('de.halaqah_id', $halaqahIds)
            ->select('er.id', 'er.label', 'er.type', DB::raw('COUNT(*) as total'))
            ->groupBy('er.id', 'er.label', 'er.type')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $testAvg = TestResult::query()
            ->whereHas('assignment', fn (Builder $q) => $q->whereIn('halaqah_id', $halaqahIds))
            ->whereNotNull('total_score')
            ->whereBetween(DB::raw('DATE(tested_at)'), [$from, $to])
            ->avg('total_score');

        $testLevelDist = TestResult::query()
            ->whereHas('assignment', fn (Builder $q) => $q->whereIn('halaqah_id', $halaqahIds))
            ->whereBetween(DB::raw('DATE(tested_at)'), [$from, $to])
            ->select('level', DB::raw('COUNT(*) as total'))
            ->groupBy('level')
            ->pluck('total', 'level');

        $visitAvg = SupervisoryVisit::query()
            ->whereIn('center_id', $centerIds)
            ->whereNotNull('overall_score')
            ->whereBetween(DB::raw('DATE(visited_at)'), [$from, $to])
            ->avg('overall_score');

        $lowestItems = DB::table('supervisory_visit_scores as vs')
            ->join('supervisory_visits as v', 'v.id', '=', 'vs.supervisory_visit_id')
            ->join('supervision_rubric_items as i', 'i.id', '=', 'vs.supervision_rubric_item_id')
            ->whereIn('v.center_id', $centerIds)
            ->whereBetween(DB::raw('DATE(v.visited_at)'), [$from, $to])
            ->select('i.id', 'i.label', DB::raw('AVG(COALESCE(vs.score,0)) as avg_score'))
            ->groupBy('i.id', 'i.label')
            ->orderBy('avg_score')
            ->limit(10)
            ->get();

        return [
            'range' => ['from' => $from, 'to' => $to],
            'active_students' => $activeStudents,
            'attendance' => [
                'present' => $present,
                'excused' => $excused,
                'unexcused' => $unexcused,
                'present_percent' => round(($present / $attendanceTotal) * 100, 2),
                'unexcused_percent' => round(($unexcused / $attendanceTotal) * 100, 2),
            ],
            'daily_evaluations' => [
                'excellent' => (int) ($dailyEvalCounts[DailyEvaluation::OVERALL_EXCELLENT] ?? 0),
                'good' => (int) ($dailyEvalCounts[DailyEvaluation::OVERALL_GOOD] ?? 0),
                'needs_improvement' => (int) ($dailyEvalCounts[DailyEvaluation::OVERALL_NEEDS_IMPROVEMENT] ?? 0),
            ],
            'top_reasons' => $topReasons,
            'tests' => [
                'avg_total_score' => $testAvg ? round((float) $testAvg, 2) : null,
                'levels' => [
                    'excellent' => (int) ($testLevelDist['excellent'] ?? 0),
                    'good' => (int) ($testLevelDist['good'] ?? 0),
                    'acceptable' => (int) ($testLevelDist['acceptable'] ?? 0),
                    'weak' => (int) ($testLevelDist['weak'] ?? 0),
                ],
            ],
            'supervision' => [
                'avg_overall_score' => $visitAvg ? round((float) $visitAvg, 2) : null,
                'lowest_items' => $lowestItems,
            ],
        ];
    }

    /**
     * سلسلة يومية للحضور (لاستخدامها في الرسوم البيانية).
     *
     * Arabic: تعيد مصفوفات متوازية لأيام الفترة، وعدد الحضور/المعذور/غير المعذور لكل يوم.
     * EN: Returns daily time-series arrays for attendance statuses.
     *
     * @return array{labels:array<int,string>,present:array<int,int>,excused:array<int,int>,unexcused:array<int,int>}
     */
    public function attendanceDailySeries(string $scopeType, ?int $scopeId, string $dateFrom, string $dateTo, \App\Models\User $user): array
    {
        [$from, $to] = $this->normalizeDates($dateFrom, $dateTo);

        $centerIds = $this->resolveCenterIdsForScope($user, $scopeType, $scopeId);
        $halaqahIds = $this->resolveHalaqahIdsForScope($centerIds, $scopeType, $scopeId);

        $rows = AttendanceRecord::query()
            ->whereBetween('date', [$from, $to])
            ->whereIn('halaqah_id', $halaqahIds)
            ->select('date', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        $days = collect();
        $cur = Carbon::parse($from);
        $end = Carbon::parse($to);
        while ($cur->lte($end)) {
            $days->push($cur->toDateString());
            $cur->addDay();
        }

        $map = $rows->groupBy(fn ($r) => $r->date . '|' . $r->status)->map->sum('total');

        $present = [];
        $excused = [];
        $unexcused = [];
        foreach ($days as $d) {
            $present[] = (int) ($map["{$d}|" . AttendanceRecord::STATUS_PRESENT] ?? 0);
            $excused[] = (int) ($map["{$d}|" . AttendanceRecord::STATUS_EXCUSED] ?? 0);
            $unexcused[] = (int) ($map["{$d}|" . AttendanceRecord::STATUS_UNEXCUSED] ?? 0);
        }

        return [
            'labels' => $days->all(),
            'present' => $present,
            'excused' => $excused,
            'unexcused' => $unexcused,
        ];
    }

    /**
     * تطبيع وتحقيق فترة التاريخ.
     *
     * Arabic: يضمن صيغة `Y-m-d` ويرتب البداية والنهاية عند انقلابهما.
     * EN: Normalizes and orders date range.
     *
     * @return array{from:string,to:string}
     */
    private function normalizeDates(string $from, string $to): array
    {
        $f = Carbon::parse($from)->toDateString();
        $t = Carbon::parse($to)->toDateString();
        if ($f > $t) {
            [$f, $t] = [$t, $f];
        }
        return [$f, $t];
    }

    /**
     * حل قائمة المراكز المتاحة ضمن النطاق حسب صلاحيات المستخدم.
     *
     * Arabic: SuperAdmin يمكنه رؤية جميع المراكز أو نطاقاً محدداً، وغير ذلك يتم
     * التقاطع مع المراكز التي يديرها المستخدم عبر `UserScope`.
     * EN: Resolves accessible center IDs for the given scope and user permissions.
     *
     * @return Collection<int,int>
     */
    private function resolveCenterIdsForScope(\App\Models\User $user, string $scopeType, ?int $scopeId): Collection
    {
        $userCenterIds = $this->scope->centerIds($user);

        if ($user->hasRole('SuperAdmin')) {
            if ($scopeType === 'center' && $scopeId) {
                return collect([(int) $scopeId]);
            }
            if ($scopeType === 'region' && $scopeId) {
                return \App\Models\Center::where('region_id', $scopeId)->pluck('id')->map(fn ($v) => (int) $v);
            }
            if ($scopeType === 'halaqah' && $scopeId) {
                return \App\Models\Halaqah::whereKey($scopeId)->pluck('center_id')->map(fn ($v) => (int) $v);
            }
            return \App\Models\Center::pluck('id')->map(fn ($v) => (int) $v);
        }

        // Non-superadmin: intersect with managed centers
        if ($scopeType === 'center' && $scopeId) {
            return $userCenterIds->contains((int) $scopeId) ? collect([(int) $scopeId]) : collect();
        }

        if ($scopeType === 'region' && $scopeId) {
            $inRegion = \App\Models\Center::where('region_id', $scopeId)->pluck('id')->map(fn ($v) => (int) $v);
            return $inRegion->intersect($userCenterIds)->values();
        }

        if ($scopeType === 'halaqah' && $scopeId) {
            $centerId = (int) (\App\Models\Halaqah::whereKey($scopeId)->value('center_id') ?? 0);
            return $userCenterIds->contains($centerId) ? collect([$centerId]) : collect();
        }

        return $userCenterIds;
    }

    /**
     * حل قائمة الحلقات ضمن المراكز/النطاق.
     *
     * Arabic: في حال كان النطاق حلقة بعينها يعاد معرفها مباشرة، وإلا تُستخرج
     * الحلقات التابعة للمراكز المتاحة.
     * EN: Resolves accessible halaqah IDs for the given scope.
     *
     * @param Collection<int,int> $centerIds
     * @return Collection<int,int>
     */
    private function resolveHalaqahIdsForScope(Collection $centerIds, string $scopeType, ?int $scopeId): Collection
    {
        if ($scopeType === 'halaqah' && $scopeId) {
            return collect([(int) $scopeId]);
        }
        return \App\Models\Halaqah::whereIn('center_id', $centerIds)->pluck('id')->map(fn ($v) => (int) $v);
    }

    /**
     * مراكز النطاق المحدد — واجهة عامة لاستخدام الودجات.
     *
     * Arabic: تتيح لودجات لوحة المؤشرات تطبيق نفس منطق النطاق والصلاحيات
     * المستخدم في حساب الـ KPIs، بدل تكراره في كل ودجة.
     * EN: Public accessor so dashboard widgets reuse the same scope/permission logic.
     *
     * @return Collection<int,int>
     */
    public function centerIdsForScope(\App\Models\User $user, string $scopeType, ?int $scopeId): Collection
    {
        return $this->resolveCenterIdsForScope($user, $scopeType, $scopeId);
    }

    /**
     * حلقات النطاق المحدد — واجهة عامة لاستخدام الودجات.
     * EN: Public accessor for scoped halaqah IDs.
     *
     * @return Collection<int,int>
     */
    public function halaqahIdsForScope(\App\Models\User $user, string $scopeType, ?int $scopeId): Collection
    {
        return $this->resolveHalaqahIdsForScope(
            $this->resolveCenterIdsForScope($user, $scopeType, $scopeId),
            $scopeType,
            $scopeId,
        );
    }
}

