<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

// ... (otros 'use' statements)

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // 🔥 ASEGÚRATE DE QUE ESTE BLOQUE EXISTA 🔥
        'App\Events\IngestaCompletada' => [
            'App\Listeners\MarcarColumnasMaestrasComoFijas',
        ],
    ];

    // ... (el resto del archivo)
}