<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\CinetPayGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Un seul prestataire pour l'instant ; PAYMENT_DEFAULT_GATEWAY permet
        // d'en ajouter d'autres (ex. PayDunya) sans changer le reste de l'app.
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return match (config('services.payment_default_gateway', 'cinetpay')) {
                default => new CinetPayGateway(
                    apiKey: (string) config('services.cinetpay.api_key'),
                    siteId: (string) config('services.cinetpay.site_id'),
                    baseUrl: (string) config('services.cinetpay.base_url'),
                    notifyUrl: (string) config('services.cinetpay.notify_url'),
                    returnUrl: (string) config('services.cinetpay.return_url'),
                ),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
