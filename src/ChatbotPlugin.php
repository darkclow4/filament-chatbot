<?php

namespace Darkclow4\FilamentChatbot;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;

class ChatbotPlugin implements Plugin
{
    protected static bool|string|Closure|null $enabledUsing = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function enabled(bool|string|Closure|null $condition = true): static
    {
        static::$enabledUsing = $condition;

        return $this;
    }

    public static function clearEnabledConfiguration(): void
    {
        static::$enabledUsing = null;
    }

    public static function isEnabledFor(?Authenticatable $user = null, ?Panel $panel = null): bool
    {
        $panel ??= static::currentPanel();
        $user ??= static::authUser();

        $pluginEnabled = static::resolveCondition(static::$enabledUsing, $user, $panel);
        $configEnabled = static::resolveCondition(config('filament-chatbot.enabled', true), $user, $panel);

        if ($pluginEnabled === false || $configEnabled === false) {
            return false;
        }

        return ($pluginEnabled ?? true) && ($configEnabled ?? true);
    }

    public function getId(): string
    {
        return 'filament-chatbot';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->assets([
                Css::make('filament-chatbot-styles', __DIR__.'/../resources/dist/filament-chatbot.min.css'),
                Js::make('filament-chatbot-scripts', __DIR__.'/../resources/dist/filament-chatbot.min.js'),
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

    public static function currentPanel(): ?Panel
    {
        if (method_exists(Filament::class, 'getCurrentPanel')) {
            return Filament::getCurrentPanel();
        }

        return null;
    }

    public static function authUser(): ?Authenticatable
    {
        if (method_exists(Filament::class, 'auth')) {
            $user = Filament::auth()->user();

            return $user instanceof Authenticatable ? $user : null;
        }

        $user = auth()->user();

        return $user instanceof Authenticatable ? $user : null;
    }

    protected static function resolveCondition(bool|string|Closure|null $condition, ?Authenticatable $user, ?Panel $panel): ?bool
    {
        if ($condition === null) {
            return null;
        }

        if (is_bool($condition)) {
            return $condition;
        }

        if ($condition instanceof Closure) {
            return (bool) $condition($user, $panel);
        }

        if (class_exists($condition)) {
            return (bool) app($condition)($user, $panel);
        }

        return (bool) Arr::get(config('filament-chatbot.conditions', []), $condition);
    }
}
