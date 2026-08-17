<?php

namespace App\Providers;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Models\TestResult;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Center;
use App\Models\Halaqah;
use App\Models\Region;
use App\Models\SupervisionRubric;
use App\Models\StudentProfileSubmission;
use App\Models\SupervisoryVisit;
use App\Policies\CenterPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\HalaqahPolicy;
use App\Policies\RegionPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TestAssignmentPolicy;
use App\Policies\TestPolicy;
use App\Policies\TestResultPolicy;
use App\Policies\TeacherProfilePolicy;
use App\Policies\UserPolicy;
use App\Policies\SupervisionRubricPolicy;
use App\Policies\StudentProfileSubmissionPolicy;
use App\Policies\SupervisoryVisitPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * مزوّد خدمات التطبيق العام (Laravel Service Provider).
 *
 * Arabic: يربط الـ Policies بالموديلات عبر `Gate::policy` لضمان تطبيق الصلاحيات
 * في الـ API ولوحة Filament.
 * EN: Registers model policies for authorization.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Arabic: تعريف الربط بين Models وPolicies المعتمدة في النظام.
     * EN: Bootstraps authorization policy mappings.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Region::class, RegionPolicy::class);
        Gate::policy(Center::class, CenterPolicy::class);
        Gate::policy(Halaqah::class, HalaqahPolicy::class);
        Gate::policy(TeacherProfile::class, TeacherProfilePolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(StudentProfileSubmission::class, StudentProfileSubmissionPolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        Gate::policy(Test::class, TestPolicy::class);
        Gate::policy(TestAssignment::class, TestAssignmentPolicy::class);
        Gate::policy(TestResult::class, TestResultPolicy::class);
        Gate::policy(SupervisionRubric::class, SupervisionRubricPolicy::class);
        Gate::policy(SupervisoryVisit::class, SupervisoryVisitPolicy::class);
    }
}
