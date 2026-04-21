<?php

namespace App\Providers;

use App\Models\PembelianDetail;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\StokOpname;
use App\Observers\PembelianDetailObserver;
use App\Observers\PembelianObserver;
use App\Observers\PenjualanDetailObserver;
use App\Observers\PenjualanObserver;
use App\Observers\StokOpnameObserver;
use App\Policies\StokOpnamePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        PenjualanDetail::observe(PenjualanDetailObserver::class);
        Penjualan::observe(PenjualanObserver::class);
        Pembelian::observe(PembelianObserver::class);
        PembelianDetail::observe(PembelianDetailObserver::class);
        StokOpname::observe(StokOpnameObserver::class);

        Gate::policy(StokOpname::class, StokOpnamePolicy::class);
    }
}
