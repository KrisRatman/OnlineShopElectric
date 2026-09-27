<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\MoneyInput;
use App\Models\Category;
use App\Models\ProductAttribute;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Товар')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                                if ($operation === 'create' || blank($get('slug'))) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Адрес (slug)')
                            ->required()
                            ->alphaDash()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('sku')
                            ->label('Артикул')
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true),
                        Select::make('category_id')
                            ->label('Категория')
                            ->required()
                            ->searchable()
                            ->options(fn () => self::categoryOptions()),
                        Select::make('brand_id')
                            ->label('Бренд')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('description')
                            ->label('Описание')
                            ->helperText('Абзацы разделяйте пустой строкой.')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),

                Section::make('Цена и склад')
                    ->columnSpan(1)
                    ->schema([
                        MoneyInput::make('price')
                            ->label('Цена')
                            ->required(),
                        MoneyInput::make('old_price')
                            ->label('Старая цена')
                            ->helperText('Зачёркнутая цена. Пусто — без скидки.')
                            ->gt('price'),
                        TextInput::make('stock')
                            ->label('Остаток на складе')
                            ->helperText('Доступно к продаже. Резерв неоплаченных заказов уже вычтен.')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->suffix('шт.'),
                        Toggle::make('is_active')
                            ->label('Показывать на витрине')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Хит продаж'),
                    ]),

                Section::make('Фото')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('images')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort')
                            ->reorderable()
                            ->grid(4)
                            ->addActionLabel('Добавить фото')
                            ->defaultItems(0)
                            ->schema([
                                FileUpload::make('path')
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->directory('products')
                                    ->image()
                                    ->maxSize(4096)
                                    ->required(),
                            ]),
                    ]),

                Section::make('Характеристики')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('attributeValues')
                            ->hiddenLabel()
                            ->relationship()
                            ->columns(2)
                            ->addActionLabel('Добавить характеристику')
                            ->defaultItems(0)
                            ->schema([
                                Select::make('attribute_id')
                                    ->label('Характеристика')
                                    ->options(fn () => ProductAttribute::query()->orderBy('sort')->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('value')
                                    ->label('Значение')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }

    /**
     * Товар кладётся в подкатегорию: «Ноутбуки → Игровые ноутбуки».
     *
     * @return array<string, array<int, string>>
     */
    private static function categoryOptions(): array
    {
        return Category::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort')
            ->get()
            ->mapWithKeys(fn (Category $root) => [
                $root->name => $root->children->isEmpty()
                    ? [$root->id => $root->name]
                    : $root->children->pluck('name', 'id')->all(),
            ])
            ->all();
    }
}
