<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CenterResource\Pages;
use App\Models\Center;
use App\Models\Region;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
 * مورد Filament لإدارة المراكز (Centers).
 *
 * Arabic: يعرّف نموذج الإدخال (اختيار المنطقة، اسم المركز، المشرف المسؤول، بيانات الاتصال)
 * وجدول العرض والفلاتر والصلاحيات ونطاق الاستعلام حسب دور المستخدم.
 * EN: Filament resource for managing centers (form, table, filters, permissions, and query scoping).
 */
class CenterResource extends Resource
{
    protected static ?string $model = Center::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'المراكز';

    protected static ?string $modelLabel = 'مركز';

    protected static ?string $pluralModelLabel = 'المراكز';

    protected static ?int $navigationSort = 2;

    /**
     * نموذج إنشاء/تعديل المركز في Filament.
     *
     * Arabic: يختار المنطقة ويربط مشرفاً مسؤولاً (Admin/SuperAdmin/…)، مع حقول
     * مساعدة للعنوان والهاتف.
     * EN: Center create/edit form schema.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('region_id')
                ->label('المنطقة')
                ->options(Region::pluck('name', 'id'))
                ->required()
                ->searchable(),
            TextInput::make('name')
                ->label('اسم المركز')
                ->required()
                ->maxLength(255),
            Select::make('admin_user_id')
                ->label('المشرف المسؤول')
                ->helperText('يُعرض المستخدمون الذين لديهم دور: Admin، أو مشرف تربوي، أو مشرف حلقات، أو SuperAdmin.')
                ->options(
                    User::query()
                        ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', [
                            'SuperAdmin',
                            'Admin',
                            'EducationalSupervisor',
                            'CenterSupervisor',
                        ]))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                )
                ->nullable()
                ->searchable(),
            TextInput::make('address')->label('العنوان'),
            TextInput::make('phone')->label('الهاتف')->tel(),
        ]);
    }

    /**
     * جدول عرض المراكز (List page).
     *
     * Arabic: يعرض الاسم والمنطقة والمشرف وعدد الحلقات، مع فلتر حسب المنطقة.
     * EN: Centers listing table configuration.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                TextColumn::make('region.name')->label('المنطقة')->sortable(),
                TextColumn::make('admin.name')->label('المشرف')->searchable(),
                TextColumn::make('halaqahs_count')
                    ->label('عدد الحلقات')
                    ->counts('halaqahs')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('region_id')
                    ->label('المنطقة')
                    ->options(Region::pluck('name', 'id')),
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
            'index' => Pages\ListCenters::route('/'),
            'create' => Pages\CreateCenter::route('/create'),
            'edit' => Pages\EditCenter::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب المستخدم الحالي.
     *
     * Arabic: SuperAdmin يرى كل المراكز، بينما Admin (المشرف المسؤول) يرى فقط المراكز
     * التي يكون `admin_user_id` الخاص بها يساوي معرفه.
     * EN: Scopes centers query: SuperAdmin unrestricted; otherwise only centers administered by user.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('SuperAdmin')) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->where('admin_user_id', $user->id);
    }

    /**
     * السماح بإنشاء مركز من لوحة الإدارة.
     * EN: Whether user can create centers.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['SuperAdmin', 'Admin']) ?? false;
    }
}
