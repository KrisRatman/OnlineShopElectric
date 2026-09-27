<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Каталог: категория, поиск, фильтры (бренд, цена, наличие, характеристики) и сортировка.
 * Все фильтры живут в адресе страницы — ссылку с подборкой можно отправить.
 */
#[Layout('layouts.app')]
class Catalog extends Component
{
    use WithPagination;

    public const PER_PAGE = 12;

    public const SORTS = [
        'popular' => 'Сначала популярные',
        'price_asc' => 'Сначала дешёвые',
        'price_desc' => 'Сначала дорогие',
        'new' => 'Новинки',
    ];

    public ?Category $category = null;

    #[Url(except: '')]
    public string $q = '';

    /** @var list<string> slug брендов */
    #[Url(as: 'brand', except: [])]
    public array $brands = [];

    /** Цена в рублях: так её вводит покупатель. */
    #[Url(as: 'from', except: null)]
    public ?int $priceFrom = null;

    #[Url(as: 'to', except: null)]
    public ?int $priceTo = null;

    #[Url(as: 'stock', except: false)]
    public bool $inStock = false;

    /** @var array<string, list<string>> slug характеристики → выбранные значения */
    #[Url(as: 'f', except: [])]
    public array $attrs = [];

    #[Url(except: 'popular')]
    public string $sort = 'popular';

    public function mount(?Category $category = null): void
    {
        abort_if($category !== null && ! $category->is_active, 404);

        $this->category = $category?->load(['parent', 'children' => fn ($q) => $q->active()]);

        if (! array_key_exists($this->sort, self::SORTS)) {
            $this->sort = 'popular';
        }
    }

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('brands', 'priceFrom', 'priceTo', 'inStock', 'attrs');
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return $this->brands !== [] || $this->priceFrom !== null || $this->priceTo !== null
            || $this->inStock || array_filter($this->attrs) !== [];
    }

    /** Категория + поиск, без фильтров: по нему строятся варианты фильтров. */
    private function scope(): Builder
    {
        $query = Product::query()->active();

        if ($this->category !== null) {
            $query->whereIn('category_id', $this->category->selfAndChildrenIds());
        }

        foreach (preg_split('/\s+/u', trim($this->q), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $like = '%'.addcslashes(mb_substr($word, 0, 50), '%_\\').'%';

            $query->where(fn (Builder $q) => $q
                ->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like)));
        }

        return $query;
    }

    private function filtered(): Builder
    {
        $query = $this->scope();

        if ($this->brands !== []) {
            $query->whereHas('brand', fn (Builder $q) => $q->whereIn('slug', $this->brands));
        }

        if ($this->priceFrom !== null) {
            $query->where('price', '>=', $this->priceFrom * 100);
        }

        if ($this->priceTo !== null) {
            $query->where('price', '<=', $this->priceTo * 100);
        }

        if ($this->inStock) {
            $query->where('stock', '>', 0);
        }

        $attributeIds = ProductAttribute::query()->whereIn('slug', array_keys($this->attrs))->pluck('id', 'slug');

        foreach ($this->attrs as $slug => $values) {
            $values = array_values(array_filter((array) $values, 'is_string'));

            if ($values === [] || ! isset($attributeIds[$slug])) {
                continue;
            }

            $query->whereHas('attributeValues', fn (Builder $q) => $q
                ->where('attribute_id', $attributeIds[$slug])
                ->whereIn('value', $values));
        }

        return $query;
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = $this->filtered()->with(['mainImage', 'brand']);

        // Товары, которых нет в наличии, всегда в конце списка.
        $query->orderByRaw('stock > 0 desc');

        match ($this->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'new' => $query->latest('id'),
            default => $query->orderByDesc('is_featured')->latest('id'),
        };

        return $query->paginate(self::PER_PAGE);
    }

    /** @return Collection<int, Brand> */
    #[Computed]
    public function brandOptions(): Collection
    {
        return Brand::query()
            ->whereIn('id', $this->scope()->select('brand_id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * Характеристики для фильтра со значениями, которые есть у товаров в выдаче.
     *
     * @return Collection<int, array{attribute: ProductAttribute, values: list<string>}>
     */
    #[Computed]
    public function attributeOptions(): Collection
    {
        $productIds = $this->scope()->select('id');

        return ProductAttribute::query()
            ->where('is_filterable', true)
            ->orderBy('sort')
            ->with(['values' => fn ($q) => $q->whereIn('product_id', $productIds)->select('attribute_id', 'value')->distinct()])
            ->get()
            ->map(fn (ProductAttribute $attribute) => [
                'attribute' => $attribute,
                'values' => $attribute->values->pluck('value')->unique()->sort(SORT_NATURAL)->values()->all(),
            ])
            ->filter(fn (array $option) => count($option['values']) > 1)
            ->values();
    }

    /** @return array{min: int, max: int} в рублях */
    #[Computed]
    public function priceRange(): array
    {
        $row = $this->scope()->toBase()->selectRaw('min(price) as min_price, max(price) as max_price')->first();

        return [
            'min' => intdiv((int) $row->min_price, 100),
            'max' => (int) ceil((int) $row->max_price / 100),
        ];
    }

    public function title(): string
    {
        if ($this->category !== null) {
            return $this->category->name;
        }

        return $this->q !== '' ? "Поиск: {$this->q}" : 'Каталог';
    }

    public function render(): View
    {
        $roots = $this->category === null
            ? Category::query()->active()->roots()->orderBy('sort')->get()
            : collect();

        return view('livewire.catalog', ['roots' => $roots])->title($this->title());
    }
}
