<?php

namespace Darkclow4\FilamentChatbot;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class ChatbotPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-chatbot';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->assets([
                Css::make('filament-chatbot-styles', __DIR__.'/../resources/css/filament-chatbot.css'),
                Js::make('filament-chatbot-scripts', __DIR__.'/../resources/js/filament-chatbot.js'),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => Blade::render('<div id="filament-chatbot-root">@livewire(\'filament-chatbot\')</div>'),
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
