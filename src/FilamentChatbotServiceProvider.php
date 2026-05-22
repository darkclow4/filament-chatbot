<?php

namespace Darkclow4\FilamentChatbot;

use Darkclow4\FilamentChatbot\Livewire\FloatingChatbot;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentChatbotServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-chatbot')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        Livewire::component('filament-chatbot', FloatingChatbot::class);
        FilamentAsset::register([
            Js::make('filament-chatbot', __DIR__.'/../resources/dist/filament-chatbot.min.js'),
            Css::make('filament-chatbot', __DIR__.'/../resources/dist/filament-chatbot.min.css'),
        ], 'darkclow4/filament-chatbot');
    }
}
