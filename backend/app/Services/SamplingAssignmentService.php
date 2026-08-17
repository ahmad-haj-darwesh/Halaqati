<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Models\User;
use App\Policies\TestPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * خدمة توليد إسنادات الاختبار بنمط العيّنة (Sampling).
 *
 * Arabic: تختار مجموعة من الطلاب المؤهلين وتقوم بإنشاء `TestAssignment` لهم وفق
 * استراتيجية اختيار (عشوائي/طبقي) وبشكل قابل لإعادة الإنتاج عبر seed.
 * EN: Generates test assignments for a sampling test using deterministic selection.
 */
class SamplingAssignmentService
{
    /**
     * توليد إسنادات الاختبار.
     *
     * Arabic: يتحقق من الصلاحية ونوع الاختبار، ثم يحدد الحجم المطلوب (عدد أو نسبة)
     * ويطبّق استراتيجية اختيار، ويُنشئ سجلات `TestAssignment` ضمن معاملة.
     * EN: Validates permissions/type, selects target students, and creates assignments transactionally.
     *
     * @return array{created:int,student_ids:array<int>}
     */
    public function generate(Test $test, User $actor, array $options): array
    {
        abort_unless($actor->can('update', $test), 403);

        if ($test->type !== Test::TYPE_SAMPLING) {
            throw ValidationException::withMessages(['type' => ['Test must be sampling.']]);
        }

        $strategy = $options['strategy'] ?? ($test->sampling_strategy ?? 'random');
        $activeOnly = (bool) ($options['active_only'] ?? $test->sampling_active_only ?? true);
        $seed = $options['seed'] ?? $test->sampling_seed;
        $count = $options['count'] ?? $test->sampling_count;
        $percent = $options['percent'] ?? $test->sampling_percent;

        $studentsByHalaqah = $this->getEligibleStudentIdsByHalaqah($test, $activeOnly);

        if ($studentsByHalaqah->isEmpty()) {
            return ['created' => 0, 'student_ids' => []];
        }

        $allStudentIds = $studentsByHalaqah->flatten()->unique()->values();

        $target = $this->resolveTargetCount($allStudentIds->count(), $count, $percent);
        if ($target <= 0) {
            throw ValidationException::withMessages(['count' => ['Sampling size must be > 0.']]);
        }

        $selected = match ($strategy) {
            'stratified' => $this->selectStratified($studentsByHalaqah, $target, $seed),
            default => $this->selectRandom($allStudentIds, $target, $seed),
        };

        $created = 0;
        DB::transaction(function () use ($test, $actor, $selected, &$created) {
            foreach ($selected as $row) {
                /** @var array{student_id:int,halaqah_id:int} $row */
                $assignment = TestAssignment::firstOrCreate(
                    ['test_id' => $test->id, 'student_id' => $row['student_id']],
                    [
                        'halaqah_id' => $row['halaqah_id'],
                        'assigned_at' => now(),
                        'assigned_by_user_id' => $actor->id,
                        'status' => TestAssignment::STATUS_ASSIGNED,
                    ]
                );
                if ($assignment->wasRecentlyCreated) {
                    $created++;
                }
            }
        });

        return [
            'created' => $created,
            'student_ids' => collect($selected)->pluck('student_id')->unique()->values()->all(),
        ];
    }

    private function resolveTargetCount(int $population, $count, $percent): int
    {
        if (is_numeric($count)) {
            return min($population, (int) $count);
        }

        if (is_numeric($percent)) {
            $p = (float) $percent;
            $n = (int) ceil(($p / 100.0) * $population);
            return max(1, min($population, $n));
        }

        return min($population, 1);
    }

    /**
     * الحصول على الطلاب المؤهلين مرتّبين حسب الحلقة.
     *
     * Arabic: يعتمد على سجلات `Enrollment` ويمكن تقييده بنطاق الحلقة/المركز/المنطقة
     * المحدد في الاختبار.
     * EN: Returns eligible student IDs grouped by halaqah, filtered by test scope.
     *
     * @return Collection<int, Collection<int,int>>  halaqah_id => [student_id...]
     */
    private function getEligibleStudentIdsByHalaqah(Test $test, bool $activeOnly): Collection
    {
        $enrollments = Enrollment::query()
            ->select(['student_id', 'halaqah_id'])
            ->when($activeOnly, fn (Builder $q) => $q->where('status', Enrollment::STATUS_ACTIVE))
            ->when($test->scope_halaqah_id, fn (Builder $q) => $q->where('halaqah_id', $test->scope_halaqah_id))
            ->when($test->scope_center_id, function (Builder $q) use ($test) {
                $q->whereHas('halaqah', fn (Builder $hq) => $hq->where('center_id', $test->scope_center_id));
            })
            ->when($test->scope_region_id, function (Builder $q) use ($test) {
                $q->whereHas('halaqah.center', fn (Builder $cq) => $cq->where('region_id', $test->scope_region_id));
            })
            ->get();

        return $enrollments
            ->groupBy('halaqah_id')
            ->map(fn ($rows) => $rows->pluck('student_id')->unique()->sort()->values());
    }

    /**
     * اختيار عشوائي قابل لإعادة الإنتاج عبر seed.
     *
     * Arabic: يقوم بخلط القائمة باستخدام `mt_srand` ثم يلتقط أول \(target\) عنصر،
     * ثم يحدّد الحلقة الحالية لكل طالب بشكل حتمي.
     * EN: Deterministically shuffles and selects target students; resolves halaqah per student.
     *
     * @param Collection<int,int> $allStudentIds
     * @return array<int, array{student_id:int,halaqah_id:int}>
     */
    private function selectRandom(Collection $allStudentIds, int $target, $seed): array
    {
        $ids = $allStudentIds->sort()->values()->all();

        $rng = $this->seedToInt($seed);
        mt_srand($rng);
        $shuffled = $ids;
        for ($i = count($shuffled) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$shuffled[$i], $shuffled[$j]] = [$shuffled[$j], $shuffled[$i]];
        }

        $pickedIds = array_slice($shuffled, 0, $target);

        // determine halaqah_id for each student deterministically (current active enrollment first)
        $map = Enrollment::query()
            ->whereIn('student_id', $pickedIds)
            ->orderBy('halaqah_id')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => (int) $rows->first()->halaqah_id);

        return collect($pickedIds)->map(fn ($sid) => ['student_id' => (int) $sid, 'halaqah_id' => (int) $map[$sid]])->all();
    }

    /**
     * اختيار طبقي (Stratified) بتوزيع شبه متساوٍ بين الحلقات.
     *
     * Arabic: يخلط قائمة كل حلقة بشكل حتمي ثم يلتقط بطريقة round-robin حتى الوصول
     * للحجم المطلوب.
     * EN: Deterministically shuffles each halaqah pool and picks in round-robin fashion.
     *
     * @param Collection<int, Collection<int,int>> $studentsByHalaqah
     * @return array<int, array{student_id:int,halaqah_id:int}>
     */
    private function selectStratified(Collection $studentsByHalaqah, int $target, $seed): array
    {
        $halaqahIds = $studentsByHalaqah->keys()->sort()->values()->all();
        $rng = $this->seedToInt($seed);
        mt_srand($rng);

        // Round-robin pick across halaqahs for even distribution
        $pools = [];
        foreach ($halaqahIds as $hid) {
            $ids = $studentsByHalaqah[(int) $hid]->all();
            // shuffle each pool deterministically
            for ($i = count($ids) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            }
            $pools[(int) $hid] = $ids;
        }

        $selected = [];
        while (count($selected) < $target) {
            $didPick = false;
            foreach ($halaqahIds as $hid) {
                $hid = (int) $hid;
                if (! empty($pools[$hid])) {
                    $sid = array_shift($pools[$hid]);
                    $selected[] = ['student_id' => (int) $sid, 'halaqah_id' => $hid];
                    $didPick = true;
                    if (count($selected) >= $target) {
                        break;
                    }
                }
            }
            if (! $didPick) {
                break;
            }
        }

        return $selected;
    }

    private function seedToInt($seed): int
    {
        if ($seed === null || $seed === '') {
            return 12345;
        }
        if (is_int($seed) || ctype_digit((string) $seed)) {
            $i = (int) $seed;
            return $i <= 0 ? 12345 : $i;
        }
        return (int) (hexdec(substr(hash('sha256', (string) $seed), 0, 8)) % 2147483647);
    }
}

