<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    ...(
        class_exists(\Filament\PanelProvider::class)
            ? [App\Providers\Filament\SellerPanelProvider::class]
            : []
    ),
];
