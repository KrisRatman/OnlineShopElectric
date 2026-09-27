<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\Cart\CartService;
use App\Services\YooKassa\DemoYooKassaClient;
use App\Services\YooKassa\YooKassaClient;
use Carbon\CarbonImmutable;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Корзина текущего посетителя — одна на запрос.
        $this->app->scoped(CartService::class);

        $this->app->singleton(YooKassaClient::class, fn () => config('yookassa.demo') ? new DemoYooKassaClient : new YooKassaClient(
            shopId: config('yookassa.shop_id'),
            secretKey: config('yookassa.secret_key'),
            baseUrl: config('yookassa.base_url'),
            timeout: (int) config('yookassa.timeout'),
        ));
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        CarbonImmutable::setLocale('ru');

        // Поле не в #[Fillable] — ошибка при разработке, а не тихая потеря данных.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventLazyLoading(! $this->app->isProduction());

        // Время хранится в UTC, админка показывает московское.
        FilamentTimezone::set(config('shop.timezone'));

        // Меню категорий в шапке витрины.
        View::composer('components.layout', fn ($view) => $view->with('navCategories', once(fn () => Category::query()
            ->active()
            ->roots()
            ->with(['children' => fn ($q) => $q->active()])
            ->orderBy('sort')
            ->get())));

        // Покупатель вошёл — гостевая корзина переезжает в его аккаунт.
        Event::listen(Login::class, fn (Login $event) => app(CartService::class)->mergeGuestCartInto($event->user));

        // За HTTPS-прокси (Cloudflare Worker перед хостингом) ссылки должны строиться от APP_URL.
        $appUrl = (string) config('app.url');

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme('https');
            Paginator::currentPathResolver(fn () => url(request()->path()));
        }
    }
}
