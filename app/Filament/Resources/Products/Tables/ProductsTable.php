<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['mainImage', 'category', 'brand']))
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('mainImage.path')
                    ->label('')
                    ->disk('public')
                    ->imageSize(48)
                    ->extraImgAttributes(['class' => 'object-contain']),
                TextColumn::make('name')
                    ->label('Товар')
                    ->description(fn (Product $record) => $record->sku)
                    ->searchable(['name', 'sku'])
                    ->sortable()
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Категория')
                    ->toggleable(),
                TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn (int $state) => money_rub($state))
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Остаток')
                    ->suffix(' шт.')
                    ->badge()
                    ->color(fn (int $state) => match (true) {
                        $state === 0 => 'danger',
                        $state <= config('shop.low_stock') => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('На витрине'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->options(fn () => Category::query()->whereNotNull('parent_id')->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('brand_id')
                    ->label('Бренд')
                    ->relationship('brand', 'name'),
                Filter::make('low_stock')
                    ->label('Заканчиваются')
                    ->query(fn (Builder $query) => $query->where('stock', '<=', config('shop.low_stock'))),
                TernaryFilter::make('is_active')
                    ->label('На витрине'),
            ])
            ->recordActions([
                // Быстрая правка остатка прямо из списка — например, после приёмки товара.
                Action::make('stock')
                    ->label('Остаток')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->modalHeading(fn (Product $record) => "Остаток: {$record->name}")
                    ->modalWidth('md')
                    ->fillForm(fn (Product $record) => ['stock' => $record->stock])
                    ->schema([
                        TextInput::make('stock')
                            ->label('Доступно к продаже')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->suffix('шт.'),
                    ])
                    ->action(function (Product $record, array $data) {
                        $record->update(['stock' => (int) $data['stock']]);
                        Notification::make()->title('Остаток обновлён')->success()->send();
                    }),
                EditAction::make(),
                // Товар из заказов удалять не стоит: позиции заказа сохранят снимок, но ссылка пропадёт.
                DeleteAction::make()
                    ->hidden(fn (Product $record) => $record->orderItems()->exists()),
            ]);
    }
}
