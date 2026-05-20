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

    protected static bool|string|Closure|null $streamingUsing = null;

    protected static string|Closure|null $primaryColorUsing = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function enabled(bool|string|Closure|null $condition = true): static
    {
        static::$enabledUsing = $condition;

        return $this;
    }

    public function streaming(bool|string|Closure|null $condition = true): static
    {
        static::$streamingUsing = $condition;

        return $this;
    }

    public function primaryColor(string|Closure|null $color = null): static
    {
        static::$primaryColorUsing = $color;

        return $this;
    }

    public static function clearEnabledConfiguration(): void
    {
        static::$enabledUsing = null;
    }

    public static function clearStreamingConfiguration(): void
    {
        static::$streamingUsing = null;
    }

    public static function clearPrimaryColorConfiguration(): void
    {
        static::$primaryColorUsing = null;
    }

    public static function isEnabledFor(?Authenticatable $user = null, ?Panel $panel = null): bool
    {
        $panel ??= static::currentPanel();
        $user ??= static::authUser();

        $pluginEnabled = static::resolveCondition(static::$enabledUsing, $user, $panel);

        if ($pluginEnabled !== null) {
            return $pluginEnabled;
        }

        return static::resolveCondition(config('filament-chatbot.enabled', true), $user, $panel) ?? true;
    }

    public static function isStreamingFor(?Authenticatable $user = null, ?Panel $panel = null): bool
    {
        $panel ??= static::currentPanel();
        $user ??= static::authUser();

        $pluginStreaming = static::resolveCondition(static::$streamingUsing, $user, $panel);

        if ($pluginStreaming !== null) {
            return $pluginStreaming;
        }

        return static::resolveCondition(config('filament-chatbot.streaming', false), $user, $panel) ?? false;
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
                fn (): string => Blade::render(
                    '<div id="filament-chatbot-root" style="--filament-chatbot-primary: {{ $color }};">@livewire(\'filament-chatbot\')</div>',
                    ['color' => static::primaryColorValue($panel)]
                ),
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

    protected static function primaryColorValue(?Panel $panel = null): string
    {
        $panel ??= static::currentPanel();
        $user = static::authUser();

        $color = static::$primaryColorUsing;

        if ($color instanceof Closure) {
            $color = $color($user, $panel);
        }

        if (is_string($color) && $color !== '') {
            return $color;
        }

        $configColor = config('filament-chatbot.primary_color');

        if (is_string($configColor) && $configColor !== '') {
            return $configColor;
        }

        return 'var(--primary-500, var(--color-primary-500, #f59e0b))';
    }
}
