<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherProfileResource\Pages;
use App\Models\Center;
use App\Models\Halaqah;
use App\Models\TeacherProfile;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * مورد Filament لإدارة ملفات المعلّمين (Teacher Profiles).
 *
 * Arabic: يربط مستخدم بدور Teacher ببيانات إضافية (هاتف/مؤهل/تاريخ تعيين/ملاحظات)
 * وإسناده لحلقة (اختياري). كما يقيّد عرض الحلقات والنتائج حسب المراكز المُدارة لغير SuperAdmin.
 * EN: Filament resource for teacher profiles, with optional halaqah assignment and permission-aware scoping.
 */
class TeacherProfileResource extends Resource
{
    protected static ?string $model = TeacherProfile::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'المعلمون';
    protected static ?string $modelLabel = 'معلم';
    protected static ?string $pluralModelLabel = 'المعلمون';
    protected static ?int $navigationSort = 4;

    /**
     * نموذج إنشاء/تعديل ملف معلّم.
     *
     * Arabic:
     * - `user_id`: يقتصر على مستخدمي Teacher الذين لا يملكون `teacherProfile` مسبقاً.\n+     * - `halaqah_id`: قائمة الحلقات تختلف حسب الصلاحية (SuperAdmin يرى الكل؛ غير ذلك حسب المراكز المُدارة).
     *
     * EN: TeacherProfile create/edit form schema.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('user_id')
                ->label('المستخدم')
                ->options(
                    User::role('Teacher')
                        ->whereDoesntHave('teacherProfile')
                        ->pluck('name', 'id')
                )
                ->required()
                ->searchable(),
            Select::make('halaqah_id')
                ->label('الحلقة')
                ->options(function () {
                    $user = auth()->user();
                    if ($user->hasRole('SuperAdmin')) {
                        return Halaqah::with('center.region')
                            ->get()
                            ->mapWithKeys(fn ($h) => [$h->id => "{$h->center->region->name} / {$h->center->name} / {$h->name}"]);
                    }

                    $centerIds = $user->managedCenters()->pluck('id');

                    return Halaqah::whereIn('center_id', $centerIds)
                        ->with('center.region')
                        ->get()
                        ->mapWithKeys(fn ($h) => [$h->id => "{$h->center->region->name} / {$h->center->name} / {$h->name}"]);
                })
                ->nullable()
                ->searchable(),
            TextInput::make('phone')->label('الهاتف')->tel(),
            TextInput::make('qualification')->label('المؤهل العلمي'),
            DatePicker::make('hire_date')->label('تاريخ التعيين'),
            Textarea::make('notes')->label('ملاحظات')->rows(3),
        ]);
    }

    /**
     * جدول عرض المعلّمين.
     *
     * Arabic: يعرض اسم المعلّم والحلقة/المركز/المنطقة والهاتف مع فلتر حسب المركز.
     * EN: Teacher profiles listing table with center filter.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('اسم المعلم')->searchable()->sortable(),
                TextColumn::make('halaqah.name')->label('الحلقة'),
                TextColumn::make('halaqah.center.name')->label('المركز'),
                TextColumn::make('halaqah.center.region.name')->label('المنطقة'),
                TextColumn::make('phone')->label('الهاتف'),
            ])
            ->filters([
                SelectFilter::make('halaqah.center_id')
                    ->label('المركز')
                    ->options(Center::pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['value'], fn ($q, $v) =>
                            $q->whereHas('halaqah', fn ($hq) => $hq->where('center_id', $v))
                        )
                    ),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    /**
     * صفحات المورد (List/Create/Edit).
     * EN: Resource pages routes.
     *
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeacherProfiles::route('/'),
            'create' => Pages\CreateTeacherProfile::route('/create'),
            'edit'   => Pages\EditTeacherProfile::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب المراكز المُدارة.
     *
     * Arabic: SuperAdmin يرى كل ملفات المعلّمين. غير ذلك: يعرض فقط المعلّمين المرتبطين
     * بحلقات ضمن مراكز يديرها المستخدم.
     * EN: Scopes teacher profiles to managed centers for non-SuperAdmin users.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('SuperAdmin')) {
            return parent::getEloquentQuery();
        }

        $centerIds = $user->managedCenters()->pluck('id');

        return parent::getEloquentQuery()
            ->whereHas('halaqah', fn ($q) => $q->whereIn('center_id', $centerIds));
    }

    /**
     * السماح بإنشاء ملف معلّم.
     * EN: Whether the current user can create teacher profiles.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['SuperAdmin', 'Admin']) ?? false;
    }
}
