<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\Enrollment;
use App\Models\Halaqah;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * مدير علاقة التسجيلات للطالب داخل Filament.
 *
 * Arabic: يتيح إدارة سجلات `Enrollment` المرتبطة بالطالب من داخل صفحة الطالب،
 * مع تقييد خيارات الحلقات حسب صلاحيات المستخدم، وضمان منطق بسيط عند إنشاء تسجيل
 * جديد (إيقاف أي تسجيل نشط سابق عند إنشاء تسجيل نشط جديد).
 * EN: Manages a student's enrollments relation (form/table) with permission-aware halaqah options.
 */
class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'التسجيلات';

    /**
     * نموذج إنشاء/تعديل تسجيل الطالب.
     * EN: Enrollment form schema.
     */
    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('halaqah_id')
                ->label('الحلقة')
                ->required()
                ->options(fn () => $this->getAllowedHalaqahs()),
            DatePicker::make('enrolled_at')
                ->label('تاريخ التسجيل')
                ->default(now())
                ->required()
                ->native(false),
            Select::make('status')
                ->label('الحالة')
                ->required()
                ->options([
                    Enrollment::STATUS_ACTIVE => 'نشط',
                    Enrollment::STATUS_PAUSED => 'موقوف',
                    Enrollment::STATUS_GRADUATED => 'متخرج',
                    Enrollment::STATUS_DROPPED => 'منسحب',
                ])
                ->default(Enrollment::STATUS_ACTIVE),
            DatePicker::make('left_at')
                ->label('تاريخ الإنهاء')
                ->native(false),
            TextInput::make('leave_reason')
                ->label('السبب')
                ->maxLength(255),
        ]);
    }

    /**
     * جدول عرض التسجيلات.
     *
     * Arabic: يعرض الحلقة/المركز/الحالة وتواريخ التسجيل/الإنهاء، مع إجراءات إنشاء/تعديل/حذف.
     * EN: Enrollments table configuration.
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('halaqah.name')->label('الحلقة')->sortable(),
                Tables\Columns\TextColumn::make('halaqah.center.name')->label('المركز')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge(),
                Tables\Columns\TextColumn::make('enrolled_at')->label('التسجيل')->date('Y/m/d')->sortable(),
                Tables\Columns\TextColumn::make('left_at')->label('الإنهاء')->date('Y/m/d')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة تسجيل')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Arabic: إيقاف التسجيل النشط السابق عند إنشاء تسجيل نشط جديد.
                        // EN: Close previous active enrollment if creating a new active one.
                        if (($data['status'] ?? null) === Enrollment::STATUS_ACTIVE) {
                            $student = $this->getOwnerRecord();
                            $student->enrollments()->active()->update([
                                'status' => Enrollment::STATUS_PAUSED,
                                'left_at' => now()->toDateString(),
                                'leave_reason' => 'Transferred',
                            ]);
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with('halaqah.center.region')->latest('enrolled_at');
            });
    }

    /**
     * إرجاع قائمة الحلقات المسموحة للاختيار ضمن هذا السياق.
     *
     * Arabic: SuperAdmin يرى كل الحلقات، وغير ذلك يقتصر على الحلقات التابعة للمراكز المُدارة.
     * EN: Returns selectable halaqahs, scoped to managed centers for non-SuperAdmin users.
     *
     * @return array<int, string>
     */
    private function getAllowedHalaqahs(): array
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $q = Halaqah::query()->with('center.region');

        if (! $user->hasRole('SuperAdmin')) {
            $centerIds = $user->managedCenters()->pluck('id');
            $q->whereIn('center_id', $centerIds);
        }

        return $q->get()->mapWithKeys(function (Halaqah $h) {
            $center = $h->center;
            $regionName = $center?->region?->name ?? '';
            $centerName = $center?->name ?? '';
            return [$h->id => trim("{$regionName} / {$centerName} / {$h->name}", ' /')];
        })->all();
    }
}

