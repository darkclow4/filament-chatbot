<?php

namespace Darkclow4\FilamentChatbot;

use Darkclow4\FilamentChatbot\Livewire\FloatingChatbot;
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
    }
}
