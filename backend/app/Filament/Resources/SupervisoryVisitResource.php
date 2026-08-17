<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupervisoryVisitResource\Pages;
use App\Filament\Resources\SupervisoryVisitResource\RelationManagers;
use App\Models\Center;
use App\Models\Halaqah;
use App\Models\SupervisionRubric;
use App\Models\TeacherProfile;
use App\Models\SupervisoryVisit;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * مورد Filament لإدارة الزيارات الإشرافية.
 *
 * Arabic: يوفّر نموذج wizard لإدخال نطاق الزيارة (قالب/مركز/حلقة)، اختيار المعلّم،
 * بيانات الزيارة وملخصها، بالإضافة إلى جدول وقائمة إجراءات (Finalize/Duplicate).
 * EN: Filament resource to create, edit, list, and manage supervisory visits.
 */
class SupervisoryVisitResource extends Resource
{
    protected static ?string $model = SupervisoryVisit::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationLabel = 'الزيارات الإشرافية';
    protected static ?string $modelLabel = 'زيارة';
    protected static ?string $pluralModelLabel = 'الزيارات الإشرافية';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('النطاق')
                        ->schema([
                            Select::make('supervision_rubric_id')
                                ->label('قالب التقييم')
                                ->required()
                                ->options(SupervisionRubric::where('is_active', true)->pluck('name', 'id'))
                                ->searchable(),
                            Select::make('center_id')
                                ->label('المركز')
                                ->required()
                                ->options(function () {
                                    $user = auth()->user();
                                    if (! $user) {
                                        return [];
                                    }
                                    if ($user->hasRole('SuperAdmin')) {
                                        return Center::pluck('name', 'id');
                                    }
                                    return $user->managedCenters()->pluck('name', 'id');
                                })
                                ->searchable()
                                ->live(),
                            Select::make('halaqah_id')
                                ->label('الحلقة')
                                ->required()
                                ->options(function (Forms\Get $get) {
                                    $centerId = $get('center_id');
                                    if (! $centerId) {
                                        return [];
                                    }
                                    return Halaqah::where('center_id', $centerId)->pluck('name', 'id');
                                })
                                ->searchable()
                                ->live(),
                        ])->columns(2),
                    Step::make('المعلم')
                        ->schema([
                            Select::make('teacher_user_id')
                                ->label('المعلم')
                                ->required()
                                ->options(function (Forms\Get $get) {
                                    $halaqahId = $get('halaqah_id');
                                    if (! $halaqahId) {
                                        return [];
                                    }
                                    return TeacherProfile::where('halaqah_id', $halaqahId)
                                        ->with('user')
                                        ->get()
                                        ->mapWithKeys(fn ($p) => [$p->user_id => $p->user->name . ' (' . $p->user->email . ')'])
                                        ->all();
                                })
                                ->searchable(),
                        ]),
                    Step::make('بيانات الزيارة')
                        ->schema([
                            DateTimePicker::make('visited_at')->label('تاريخ الزيارة')->required()->default(now()),
                            TextInput::make('duration_minutes')->label('المدة (دقيقة)')->numeric()->minValue(1),
                            Select::make('overall_level')
                                ->label('التقييم العام')
                                ->options([
                                    'excellent' => 'ممتاز',
                                    'good' => 'جيد',
                                    'acceptable' => 'مقبول',
                                    'weak' => 'ضعيف',
                                ])->nullable(),
                            Toggle::make('is_finalized')->label('مقفلة (Finalize)')->default(false),
                        ])->columns(2),
                    Step::make('الخلاصة')
                        ->schema([
                            Textarea::make('summary')->label('ملخص')->rows(3),
                            Textarea::make('recommendations')->label('توصيات')->rows(3),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    /**
     * جدول عرض الزيارات داخل Filament.
     *
     * Arabic: يعرض المعلّم/المركز/الحلقة/التاريخ/المستوى/الدرجة وحالة الإقفال.
     * EN: Visit listing table with filters and actions.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')->label('المعلم')->searchable(),
                Tables\Columns\TextColumn::make('center.name')->label('المركز')->sortable(),
                Tables\Columns\TextColumn::make('halaqah.name')->label('الحلقة')->sortable(),
                Tables\Columns\TextColumn::make('visited_at')->label('التاريخ')->dateTime('Y/m/d H:i')->sortable(),
                Tables\Columns\TextColumn::make('overall_level')->label('المستوى')->badge(),
                Tables\Columns\TextColumn::make('overall_score')->label('الدرجة')->sortable(),
                Tables\Columns\IconColumn::make('is_finalized')->label('مقفلة')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(Center::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('halaqah_id')
                    ->label('الحلقة')
                    ->options(Halaqah::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('overall_level')
                    ->label('المستوى')
                    ->options([
                        'excellent' => 'excellent',
                        'good' => 'good',
                        'acceptable' => 'acceptable',
                        'weak' => 'weak',
                    ]),
                Tables\Filters\TernaryFilter::make('is_finalized')->label('مقفلة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('finalize')
                    ->label('Finalize')
                    ->icon('heroicon-o-lock-closed')
                    ->requiresConfirmation()
                    ->visible(fn (SupervisoryVisit $record) => ! $record->is_finalized)
                    ->action(function (SupervisoryVisit $record) {
                        abort_unless(auth()->user()?->can('update', $record) ?? false, 403);
                        $record->update(['is_finalized' => true]);
                    }),
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (SupervisoryVisit $record) {
                        abort_unless(auth()->user()?->can('create', SupervisoryVisit::class) ?? false, 403);
                        $new = $record->replicate(['is_finalized', 'overall_score']);
                        $new->is_finalized = false;
                        $new->overall_score = null;
                        $new->visited_at = now();
                        $new->save();
                        foreach ($record->scores as $score) {
                            $new->scores()->create([
                                'supervision_rubric_item_id' => $score->supervision_rubric_item_id,
                                'score' => $score->score,
                                'note' => $score->note,
                            ]);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ScoresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupervisoryVisits::route('/'),
            'create' => Pages\CreateSupervisoryVisit::route('/create'),
            'edit' => Pages\EditSupervisoryVisit::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب صلاحيات المستخدم.
     *
     * Arabic: يتم تفويض تحديد النطاق إلى سياسة `SupervisoryVisitPolicy` لتوحيد
     * منطق الصلاحيات بين لوحة Filament وباقي النظام.
     * EN: Applies policy-based scoping via SupervisoryVisitPolicy.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with(['teacher', 'center', 'halaqah', 'rubric'])->withCount('scores');

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        $policy = app(\App\Policies\SupervisoryVisitPolicy::class);
        return $policy->scopeQueryForUser($user, $query);
    }
}
