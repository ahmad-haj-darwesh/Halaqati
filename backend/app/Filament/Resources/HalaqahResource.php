<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HalaqahResource\Pages;
use App\Models\Center;
use App\Models\Halaqah;
use App\Models\TeacherProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * مورد Filament لإدارة الحلقات (Halaqahs).
 *
 * Arabic: يعرّف نموذج إدخال الحلقة وربطها بالمركز وتعيين المعلّم (اختياري) مع
 * مراعاة نطاق صلاحيات المستخدم. كما يعرّف جدول العرض والفلاتر ونطاق الاستعلام.
 * EN: Filament resource for managing halaqahs (form/table/filters) with permission-aware scoping.
 */
class HalaqahResource extends Resource
{
    protected static ?string $model = Halaqah::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'الحلقات';

    protected static ?string $modelLabel = 'حلقة';

    protected static ?string $pluralModelLabel = 'الحلقات';

    protected static ?int $navigationSort = 3;

    /**
     * نموذج إنشاء/تعديل الحلقة.
     *
     * Arabic: يقيّد خيارات المراكز وفق دور المستخدم (SuperAdmin أو المراكز المُدارة)،
     * ويسمح بتعيين معلّم عبر `TeacherProfile` وفق منطق خيارات مخصص.
     * EN: Halaqah create/edit form schema with center/teacher selection.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('center_id')
                ->label('المركز')
                ->options(function () {
                    $user = auth()->user();
                    if ($user->hasRole('SuperAdmin')) {
                        return Center::with('region')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => "{$c->region->name} - {$c->name}"]);
                    }

                    return $user->managedCenters()->with('region')
                        ->get()
                        ->mapWithKeys(fn ($c) => [$c->id => "{$c->region->name} - {$c->name}"]);
                })
                ->required()
                ->searchable()
                ->live(),
            TextInput::make('name')
                ->label('اسم الحلقة')
                ->required()
                ->maxLength(255),
            TextInput::make('capacity')
                ->label('الطاقة الاستيعابية')
                ->numeric()
                ->default(20),
            Textarea::make('description')->label('الوصف')->rows(2),
            Select::make('teacher_profile_id')
                ->label('المعلم')
                ->helperText('يُربَط المعلم عبر ملف المعلّم؛ يمكن أيضاً التعيين من صفحة «المعلمون».')
                ->searchable()
                ->nullable()
                ->placeholder('— بدون معلم —')
                ->options(function (Get $get, $livewire) {
                    $halaqahId = $livewire->record?->id;
                    $centerId = $get('center_id') ? (int) $get('center_id') : $livewire->record?->center_id;

                    return static::teacherProfileOptionsForForm($halaqahId, $centerId);
                }),
        ]);
    }

    /**
     * ملفات معلّمين متاحة للتعيين: غير مرتبطة بحلقة، أو معلّم هذه الحلقة، أو معلّم لحلقة في نفس المركز.
     *
     * Arabic: تُستخدم لتقليل التعارضات عند نقل/تبديل المعلّمين بين الحلقات.
     * EN: Produces selectable teacher profiles for the form while minimizing conflicts.
     *
     * @return array<int, string>
     */
    protected static function teacherProfileOptionsForForm(?int $halaqahId, ?int $centerId): array
    {
        $query = TeacherProfile::query()->with(['user', 'halaqah']);

        $query->where(function ($q) use ($halaqahId, $centerId) {
            $q->whereNull('halaqah_id');
            if ($halaqahId) {
                $q->orWhere('halaqah_id', $halaqahId);
            }
            if ($centerId) {
                $q->orWhereHas('halaqah', fn ($h) => $h->where('center_id', $centerId));
            }
        });

        $user = auth()->user();
        if ($user && ! $user->hasRole('SuperAdmin')) {
            $ids = $user->managedCenters()->pluck('id');
            $query->where(function ($q) use ($ids) {
                $q->whereNull('halaqah_id')
                    ->orWhereHas('halaqah', fn ($h) => $h->whereIn('center_id', $ids));
            });
        }

        return $query->orderBy('id')
            ->get()
            ->mapWithKeys(fn (TeacherProfile $p) => [$p->id => $p->user?->name ?? '—'])
            ->all();
    }

    /**
     * جدول عرض الحلقات.
     *
     * Arabic: يعرض اسم الحلقة ومركزها ومنطقتها والمعلّم المعيّن والطاقة الاستيعابية،
     * مع فلتر حسب المركز.
     * EN: Halaqahs listing table configuration.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                TextColumn::make('center.name')->label('المركز')->sortable(),
                TextColumn::make('center.region.name')->label('المنطقة')->sortable(),
                TextColumn::make('teacherProfile.user.name')->label('المعلم')->placeholder('—'),
                TextColumn::make('capacity')->label('الطاقة'),
            ])
            ->filters([
                SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(Center::pluck('name', 'id')),
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
            'index' => Pages\ListHalaqahs::route('/'),
            'create' => Pages\CreateHalaqah::route('/create'),
            'edit' => Pages\EditHalaqah::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب المستخدم الحالي.
     *
     * Arabic: SuperAdmin يرى كل الحلقات. غير ذلك يقتصر على الحلقات التابعة للمراكز
     * التي يديرها المستخدم.
     * EN: Scopes halaqah query to managed centers for non-SuperAdmin users.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()->with(['teacherProfile.user']);

        if ($user->hasRole('SuperAdmin')) {
            return $query;
        }

        $centerIds = $user->managedCenters()->pluck('id');

        return $query->whereIn('center_id', $centerIds);
    }

    /**
     * السماح بإنشاء حلقة من لوحة الإدارة.
     * EN: Whether user can create halaqahs.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['SuperAdmin', 'Admin']) ?? false;
    }
}
