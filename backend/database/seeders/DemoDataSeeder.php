<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\DailyEvaluation;
use App\Models\Enrollment;
use App\Models\Halaqah;
use App\Models\MemorizationEntry;
use App\Models\Region;
use App\Models\Student;
use App\Models\SupervisionRubric;
use App\Models\SupervisoryVisit;
use App\Models\SupervisoryVisitScore;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Models\TestResult;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * بيانات تجريبية واقعية للعرض والمناقشة.
 *
 * Arabic: تُنشئ منظومة كاملة (مناطق ← مراكز ← حلقات ← معلّمون وطلاب ← سجلات
 * يومية ← اختبارات ← زيارات إشرافية) بحجم كافٍ لإظهار التقارير والرسوم البيانية.
 *
 * التصميم مقصود بثلاث خصائص لأن البذرة تُشغَّل على الإنتاج:
 *  1. **لا تحذف شيئاً** — كل الإنشاء عبر `firstOrCreate` أو فحص وجود مسبق.
 *  2. **قابلة لإعادة التشغيل** — تشغيلها مرتين لا يضاعف البيانات.
 *  3. **حتمية** — بذرة عشوائية ثابتة، فالنتيجة نفسها في كل تشغيل.
 *
 * EN: Idempotent, non-destructive demo dataset for the project defense.
 */
class DemoDataSeeder extends Seeder
{
    /** كلمة مرور موحّدة لحسابات العرض. */
    private const PASSWORD = 'password';

    /** عدد أيام السجلات اليومية إلى الوراء (تُتخطّى الجُمَع). */
    private const DAYS_BACK = 21;

    /** @var array<int, string> */
    private const STUDENT_NAMES = [
        'أحمد محمود العلي', 'عبد الرحمن خالد الحسن', 'محمد سامي الدرويش',
        'يوسف عماد النعيمي', 'إبراهيم فادي الشامي', 'عمر ياسر الحلبي',
        'زيد منير القاسم', 'حمزة وليد الخطيب', 'مصعب رائد السيد',
        'أنس بشار العمري', 'خالد نبيل الرفاعي', 'سيف الدين طارق مراد',
        'بلال عصام الجابري', 'طلحة أيمن الكردي', 'أسامة رامي الحموي',
        'معاذ جهاد البكري', 'صهيب فراس الديري', 'قتيبة ماهر الأسعد',
        'الحارث سمير العيسى', 'عبد الله نزار الحمصي', 'سلمان عادل الزعبي',
        'إسماعيل غسان الطويل', 'زكريا هيثم الصالح', 'يحيى مأمون الشيخ',
        'إدريس أنور الحاج', 'داود مازن العبد', 'سليمان بسام الخوري',
        'موسى رياض النجار', 'هارون كمال الحداد', 'عيسى وسيم البيطار',
    ];

    /** @var array<int, string> */
    private const TEACHER_NAMES = [
        'الشيخ عبد الكريم الأنصاري',
        'الشيخ محمد نور الحافظ',
        'الشيخ أسامة بن سعيد',
        'الشيخ إحسان المقرئ',
        'الشيخ زياد التلاوي',
        'الشيخ رضوان المجوّد',
    ];

    /** @var array<int, string> */
    private const SURAHS = [
        'البقرة', 'آل عمران', 'النساء', 'المائدة', 'الأنعام',
        'الأعراف', 'يوسف', 'الكهف', 'مريم', 'يس', 'الملك', 'النبأ',
    ];

    public function run(): void
    {
        // بذرة ثابتة: نفس المخرجات في كل تشغيل، فلا تتغيّر أرقام العرض بين مرة وأخرى.
        mt_srand(20260818);

        $this->command?->info('إنشاء المناطق والمراكز...');
        $centers = $this->seedRegionsAndCenters();

        $this->command?->info('إنشاء المعلّمين والمختبِرين والحلقات...');
        [$halaqahs, $examiners] = $this->seedStaffAndHalaqahs($centers);

        $this->command?->info('إنشاء الطلاب وتسجيلهم...');
        $this->seedStudents($halaqahs);

        $this->command?->info('إنشاء السجلات اليومية (حضور/تقييم/حفظ)...');
        $this->seedDailyRecords($halaqahs);

        $this->command?->info('إنشاء الاختبارات ونتائجها...');
        $this->seedTests($halaqahs, $examiners);

        $this->command?->info('إنشاء الزيارات الإشرافية...');
        $this->seedSupervisoryVisits($halaqahs);

        $this->command?->info('إنشاء إشعارات تجريبية...');
        $this->seedNotifications($halaqahs);

        $this->report();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Center>
     */
    private function seedRegionsAndCenters(): \Illuminate\Support\Collection
    {
        $map = [
            'منطقة المزة' => ['مركز النور القرآني', 'مركز الهدى'],
            'منطقة الميدان' => ['مركز الفرقان'],
        ];

        $centers = collect();

        foreach ($map as $regionName => $centerNames) {
            $region = Region::firstOrCreate(
                ['name' => $regionName],
                ['description' => 'منطقة ضمن نطاق المنظومة'],
            );

            foreach ($centerNames as $i => $centerName) {
                $supervisor = $this->makeUser(
                    name: 'مشرف '.$centerName,
                    email: 'supervisor'.($centers->count() + 1).'@halaqaty.de',
                    role: 'CenterSupervisor',
                );

                $centers->push(Center::firstOrCreate(
                    ['name' => $centerName],
                    [
                        'region_id' => $region->id,
                        'admin_user_id' => $supervisor->id,
                        'address' => 'دمشق — '.$regionName,
                        'phone' => '0911'.str_pad((string) (100000 + $centers->count()), 6, '0'),
                    ],
                ));
            }
        }

        return $centers;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Center>  $centers
     * @return array{0: \Illuminate\Support\Collection<int, Halaqah>, 1: \Illuminate\Support\Collection<int, User>}
     */
    private function seedStaffAndHalaqahs(\Illuminate\Support\Collection $centers): array
    {
        $halaqahs = collect();
        $teacherIndex = 0;

        foreach ($centers as $center) {
            foreach ([1, 2] as $n) {
                $halaqah = Halaqah::firstOrCreate(
                    ['name' => 'حلقة '.$center->name.' — '.$n, 'center_id' => $center->id],
                    ['description' => 'حلقة تحفيظ القرآن الكريم', 'capacity' => 15],
                );

                $teacher = $this->makeUser(
                    name: self::TEACHER_NAMES[$teacherIndex] ?? 'معلّم '.($teacherIndex + 1),
                    email: 'teacher'.($teacherIndex + 1).'@halaqaty.de',
                    role: 'Teacher',
                );

                TeacherProfile::firstOrCreate(
                    ['user_id' => $teacher->id],
                    [
                        'halaqah_id' => $halaqah->id,
                        'phone' => '0933'.str_pad((string) (200000 + $teacherIndex), 6, '0'),
                        'qualification' => 'إجازة في القراءات',
                        'hire_date' => now()->subYears(2)->toDateString(),
                    ],
                );

                $halaqahs->push($halaqah);
                $teacherIndex++;
            }
        }

        $examiners = collect([1, 2])->map(fn (int $n) => $this->makeUser(
            name: 'المختبِر '.($n === 1 ? 'أبو بكر الصديقي' : 'عثمان الفاروقي'),
            email: 'examiner'.$n.'@halaqaty.de',
            role: 'Examiner',
        ));

        return [$halaqahs, $examiners];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Halaqah>  $halaqahs
     */
    private function seedStudents(\Illuminate\Support\Collection $halaqahs): void
    {
        $nameIndex = 0;

        foreach ($halaqahs as $halaqah) {
            $existing = Enrollment::where('halaqah_id', $halaqah->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->count();

            for ($i = $existing; $i < 10; $i++) {
                $base = self::STUDENT_NAMES[$nameIndex % count(self::STUDENT_NAMES)];
                $suffix = intdiv($nameIndex, count(self::STUDENT_NAMES));
                $fullName = $suffix > 0 ? $base.' '.$suffix : $base;
                $nameIndex++;

                $student = Student::firstOrCreate(
                    ['full_name' => $fullName],
                    [
                        'gender' => 'male',
                        'birth_date' => now()->subYears(mt_rand(8, 16))->subDays(mt_rand(0, 364))->toDateString(),
                        'guardian_name' => 'والد '.explode(' ', $fullName)[0],
                        'guardian_phone' => '0955'.str_pad((string) (300000 + $nameIndex), 6, '0'),
                        'is_active' => true,
                    ],
                );

                Enrollment::firstOrCreate(
                    ['student_id' => $student->id, 'halaqah_id' => $halaqah->id],
                    [
                        'enrolled_at' => now()->subMonths(mt_rand(2, 10))->toDateString(),
                        'status' => Enrollment::STATUS_ACTIVE,
                    ],
                );
            }
        }
    }

    /**
     * سجلات الحضور والتقييم والحفظ لآخر أيام الدراسة.
     *
     * Arabic: تُدرَج دفعةً واحدة (bulk) لأن العدد بالآلاف؛ الإدراج صفّاً صفّاً عبر
     * Eloquent كان سيستغرق دقائق على استضافة مشتركة.
     */
    private function seedDailyRecords(\Illuminate\Support\Collection $halaqahs): void
    {
        $attendance = [];
        $evaluations = [];
        $memorization = [];
        $now = now();

        foreach ($halaqahs as $halaqah) {
            $teacherId = TeacherProfile::where('halaqah_id', $halaqah->id)->value('user_id');
            $studentIds = Enrollment::where('halaqah_id', $halaqah->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->pluck('student_id');

            for ($d = self::DAYS_BACK; $d >= 0; $d--) {
                $date = Carbon::today()->subDays($d);

                if ($date->isFriday()) {
                    continue; // عطلة أسبوعية
                }

                // تخطٍّ سريع: إن كان اليوم مسجَّلاً مسبقاً لهذه الحلقة لا نكرّره.
                if (AttendanceRecord::where('halaqah_id', $halaqah->id)
                    ->whereDate('date', $date)->exists()) {
                    continue;
                }

                foreach ($studentIds as $studentId) {
                    $roll = mt_rand(1, 100);
                    $status = $roll <= 85
                        ? AttendanceRecord::STATUS_PRESENT
                        : ($roll <= 94 ? AttendanceRecord::STATUS_EXCUSED : AttendanceRecord::STATUS_UNEXCUSED);

                    $attendance[] = [
                        'halaqah_id' => $halaqah->id,
                        'student_id' => $studentId,
                        'date' => $date->toDateString(),
                        'status' => $status,
                        'recorded_by_user_id' => $teacherId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if ($status !== AttendanceRecord::STATUS_PRESENT) {
                        continue; // لا تقييم ولا حفظ للغائب
                    }

                    $mark = mt_rand(1, 100);
                    $evaluations[] = [
                        'halaqah_id' => $halaqah->id,
                        'student_id' => $studentId,
                        'date' => $date->toDateString(),
                        'overall' => $mark <= 45
                            ? DailyEvaluation::OVERALL_EXCELLENT
                            : ($mark <= 85 ? DailyEvaluation::OVERALL_GOOD : DailyEvaluation::OVERALL_NEEDS_IMPROVEMENT),
                        'recorded_by_user_id' => $teacherId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $from = mt_rand(1, 250);
                    $memorization[] = [
                        'halaqah_id' => $halaqah->id,
                        'student_id' => $studentId,
                        'date' => $date->toDateString(),
                        'memorization_from' => (string) $from,
                        'memorization_to' => (string) ($from + mt_rand(1, 6)),
                        'revision_from' => (string) max(1, $from - mt_rand(10, 40)),
                        'revision_to' => (string) $from,
                        'mistakes' => mt_rand(0, 5),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach ([
            'attendance_records' => $attendance,
            'daily_evaluations' => $evaluations,
            'memorization_entries' => $memorization,
        ] as $table => $rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Halaqah>  $halaqahs
     * @param  \Illuminate\Support\Collection<int, User>  $examiners
     */
    private function seedTests(\Illuminate\Support\Collection $halaqahs, \Illuminate\Support\Collection $examiners): void
    {
        $creator = User::role('SuperAdmin')->first() ?? User::first();

        foreach ($halaqahs->take(4) as $i => $halaqah) {
            $test = Test::firstOrCreate(
                ['title' => 'اختبار شهري — '.$halaqah->name],
                [
                    'type' => Test::TYPE_REGULAR,
                    'description' => 'اختبار دوري لقياس مستوى الحفظ والتجويد',
                    'scope_halaqah_id' => $halaqah->id,
                    'scheduled_at' => now()->subDays(10 - $i)->setTime(10, 0),
                    'created_by_user_id' => $creator?->id,
                    'is_published' => true,
                ],
            );

            $studentIds = Enrollment::where('halaqah_id', $halaqah->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->pluck('student_id');

            foreach ($studentIds as $n => $studentId) {
                $assignment = TestAssignment::firstOrCreate(
                    ['test_id' => $test->id, 'student_id' => $studentId],
                    [
                        'halaqah_id' => $halaqah->id,
                        'assigned_at' => $test->scheduled_at,
                        'assigned_by_user_id' => $creator?->id,
                        'status' => TestAssignment::STATUS_COMPLETED,
                    ],
                );

                // نترك آخر طالبين بلا نتيجة ليظهر في العرض حالة "بانتظار الاختبار".
                if ($n >= $studentIds->count() - 2) {
                    $assignment->update(['status' => TestAssignment::STATUS_ASSIGNED]);

                    continue;
                }

                if (TestResult::where('test_assignment_id', $assignment->id)->exists()) {
                    continue;
                }

                $memo = mt_rand(12, 20);
                $tajweed = mt_rand(11, 20);
                $review = mt_rand(10, 20);
                $total = $memo + $tajweed + $review;

                TestResult::create([
                    'test_assignment_id' => $assignment->id,
                    'examiner_user_id' => $examiners[$n % $examiners->count()]->id,
                    'memorization_score' => $memo,
                    'tajweed_score' => $tajweed,
                    'review_score' => $review,
                    'tested_surah' => self::SURAHS[array_rand(self::SURAHS)],
                    'total_score' => $total,
                    'level' => match (true) {
                        $total >= 54 => TestResult::LEVEL_EXCELLENT,
                        $total >= 45 => TestResult::LEVEL_GOOD,
                        $total >= 36 => TestResult::LEVEL_ACCEPTABLE,
                        default => TestResult::LEVEL_WEAK,
                    },
                    'notes' => 'أداء جيد مع الحاجة إلى مراجعة أحكام المدود.',
                    'tested_at' => $test->scheduled_at,
                ]);
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Halaqah>  $halaqahs
     */
    private function seedSupervisoryVisits(\Illuminate\Support\Collection $halaqahs): void
    {
        $rubric = $this->ensureRubric();

        foreach ($halaqahs as $i => $halaqah) {
            $teacherId = TeacherProfile::where('halaqah_id', $halaqah->id)->value('user_id');
            $supervisorId = $halaqah->center?->admin_user_id;

            if ($teacherId === null || $supervisorId === null) {
                continue;
            }

            $visitedAt = now()->subDays(5 + $i);

            if (SupervisoryVisit::where('halaqah_id', $halaqah->id)
                ->whereDate('visited_at', $visitedAt->toDateString())->exists()) {
                continue;
            }

            $scores = $rubric->items->map(fn ($item) => [
                'item' => $item,
                'score' => mt_rand((int) ceil($item->max_score * 0.6), $item->max_score),
            ]);

            $total = $scores->sum('score');
            $max = $rubric->items->sum('max_score');
            $percent = $max > 0 ? ($total / $max) * 100 : 0;

            $visit = SupervisoryVisit::create([
                'supervision_rubric_id' => $rubric->id,
                'supervisor_user_id' => $supervisorId,
                'center_id' => $halaqah->center_id,
                'halaqah_id' => $halaqah->id,
                'teacher_user_id' => $teacherId,
                'visited_at' => $visitedAt,
                'duration_minutes' => mt_rand(30, 60),
                'overall_level' => match (true) {
                    $percent >= 85 => 'excellent',
                    $percent >= 70 => 'good',
                    default => 'needs_improvement',
                },
                'overall_score' => round($percent, 2),
                'summary' => 'زيارة ميدانية للحلقة، انضباط جيد والتزام بالخطة.',
                'recommendations' => 'زيادة وقت المراجعة الجماعية وتنويع أساليب التحفيز.',
                'is_finalized' => true,
            ]);

            foreach ($scores as $row) {
                SupervisoryVisitScore::create([
                    'supervisory_visit_id' => $visit->id,
                    'supervision_rubric_item_id' => $row['item']->id,
                    'score' => $row['score'],
                    'note' => null,
                ]);
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Halaqah>  $halaqahs
     */
    private function seedNotifications(\Illuminate\Support\Collection $halaqahs): void
    {
        foreach ($halaqahs->take(3) as $halaqah) {
            $teacherId = TeacherProfile::where('halaqah_id', $halaqah->id)->value('user_id');

            if ($teacherId === null || AppNotification::where('user_id', $teacherId)->exists()) {
                continue;
            }

            AppNotification::create([
                'user_id' => $teacherId,
                'title' => 'تذكير: تسجيل الحضور',
                'body' => "يرجى تسجيل حضور «{$halaqah->name}» لليوم",
                'type' => 'record_reminder',
                'data' => ['halaqah_id' => (string) $halaqah->id],
            ]);

            AppNotification::create([
                'user_id' => $teacherId,
                'title' => 'زيارة إشرافية',
                'body' => 'سُجِّلت زيارة إشرافية جديدة لحلقتك',
                'type' => 'supervisory_visit',
                'data' => ['halaqah_id' => (string) $halaqah->id],
                'read_at' => now()->subHours(3),
            ]);
        }
    }

    /**
     * ضمان وجود نموذج تقييم إشرافي ببنوده.
     *
     * Arabic: `SupervisionSeeder` ينسحب مبكراً إن لم تكن هناك مراكز وقت تشغيله،
     * فلا يُنشئ النموذج. لذا لا تعتمد هذه البذرة عليه بل تُنشئه عند غيابه.
     * EN: Creates the rubric when missing instead of depending on another seeder.
     */
    private function ensureRubric(): SupervisionRubric
    {
        $rubric = SupervisionRubric::where('is_active', true)
            ->whereHas('items')
            ->with('items')
            ->first();

        if ($rubric !== null) {
            return $rubric;
        }

        $rubric = SupervisionRubric::firstOrCreate(
            ['name' => 'نموذج تقييم الزيارة الإشرافية'],
            [
                'description' => 'بنود تقييم أداء المعلّم أثناء الزيارة الميدانية',
                'is_active' => true,
                'created_by_user_id' => User::role('SuperAdmin')->value('id') ?? User::query()->value('id'),
            ],
        );

        $items = [
            'plan_commitment' => 'الالتزام بالخطة',
            'explanation_skill' => 'مهارة الشرح والتلقين',
            'halaqah_management' => 'إدارة الحلقة والانضباط',
            'student_interaction' => 'التفاعل مع الطلاب',
            'assessment_followup' => 'التقويم والمتابعة',
        ];

        foreach (array_values(array_keys($items)) as $order => $key) {
            $rubric->items()->firstOrCreate(
                ['key' => $key],
                [
                    'label' => $items[$key],
                    'max_score' => 10,
                    'sort_order' => $order + 1,
                    'is_active' => true,
                ],
            );
        }

        return $rubric->load('items');
    }

    private function makeUser(string $name, string $email, string $role): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make(self::PASSWORD), 'is_active' => true],
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function report(): void
    {
        $this->command?->newLine();
        $this->command?->info('اكتملت البيانات التجريبية:');

        foreach ([
            'مناطق' => Region::count(),
            'مراكز' => Center::count(),
            'حلقات' => Halaqah::count(),
            'معلّمون' => TeacherProfile::count(),
            'طلاب' => Student::count(),
            'تسجيلات' => Enrollment::count(),
            'سجلات حضور' => AttendanceRecord::count(),
            'تقييمات يومية' => DailyEvaluation::count(),
            'سجلات حفظ' => MemorizationEntry::count(),
            'اختبارات' => Test::count(),
            'نتائج اختبارات' => TestResult::count(),
            'زيارات إشرافية' => SupervisoryVisit::count(),
            'إشعارات' => AppNotification::count(),
        ] as $label => $count) {
            $this->command?->line(sprintf('  %-18s %d', $label, $count));
        }

        $this->command?->newLine();
        $this->command?->line('حسابات العرض (كلمة المرور: '.self::PASSWORD.')');
        $this->command?->line('  teacher1@halaqaty.de … teacher6@halaqaty.de   (معلّم)');
        $this->command?->line('  examiner1@halaqaty.de, examiner2@halaqaty.de  (مختبِر)');
        $this->command?->line('  supervisor1@halaqaty.de … supervisor3@halaqaty.de (مشرف حلقات)');
    }
}
