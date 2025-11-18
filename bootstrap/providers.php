<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Domains\Prediction\Providers\PredictionServiceProvider::class,
    App\Domains\ValueBets\Providers\ValueBetServiceProvider::class,
    App\Domains\User\Providers\UserServiceProvider::class,
];
