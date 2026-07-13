# Filament Chatbot

`filament-chatbot` is a floating chatbot plugin for Filament powered by `laravel/ai`.

It mounts a global Livewire chat widget inside your Filament panel, stores conversation history using Laravel AI's conversation tables, supports optional direct streaming without broadcast infrastructure, and lets you control behavior through both config and `ChatbotPlugin` APIs.

## Features

- Built for Filament 4 or newer
- Powered by `laravel/ai`
- Floating chatbot available across the Filament panel
- Database-backed remembered conversations
- Configurable `agent` class via config
- Optional provider and model overrides
- Optional draggable trigger button

## Requirements

- PHP 8.3
- Laravel 12 or newer
- Filament 4 or newer
- Livewire 3 or newer
- `laravel/ai` configured with at least one working provider

## Installation

Install the package with Composer:

```bash
composer require darkclow4/filament-chatbot
```

Publish the package config if you want to customize the chatbot:

```bash
php artisan vendor:publish --tag=filament-chatbot-config
```

If you have not published Laravel AI's config and migrations yet, do that as well:

```bash
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate
```

Publish Filament assets so the chatbot CSS and JS are available:

```bash
php artisan filament:assets
```

## Package Registration

The package supports Laravel auto-discovery through:

```php
Darkclow4\FilamentChatbot\FilamentChatbotServiceProvider::class
```

In most applications, no manual provider registration is needed.

## Register The Plugin In Your Panel

Register the plugin in your Filament panel provider:

```php
<?php

namespace App\Providers\Filament;

use Darkclow4\FilamentChatbot\ChatbotPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(ChatbotPlugin::make());
    }
}
```

The chatbot is injected with a panel-scoped render hook, so it only appears on panels where the plugin is registered.

The current plugin implementation mounts the floating chatbot through `PanelsRenderHook::BODY_START` and loads minified package assets via Filament's asset manager.

## Plugin API

The plugin exposes fluent methods so you can control visibility, streaming mode, drag behavior, and the chatbot accent color directly from your panel provider.

```php
use Darkclow4\FilamentChatbot\ChatbotPlugin;

->plugin(
    ChatbotPlugin::make()
        ->enabled(fn ($user) => $user?->hasRole('admin') ?? false)
        ->streaming(true)
        ->draggable(true)
        ->primaryColor('#10b981')
)
```

### `enabled()`

Use `enabled()` to control whether the chatbot should be visible.

Example, visible only to admins:

```php
ChatbotPlugin::make()
    ->enabled(fn ($user) => $user?->hasRole('admin') ?? false)
```

### `streaming()`

Use `streaming()` to enable direct AI streaming without Reverb / broadcast.

```php
ChatbotPlugin::make()
    ->streaming(true)
```

### `draggable()`

Use `draggable()` to let users reposition the chatbot launcher anywhere inside the panel viewport.

```php
ChatbotPlugin::make()
    ->draggable(true)
```

### `primaryColor()`

Use `primaryColor()` to override the chatbot accent color.

Supported values:

- hex string, for example `#10b981`
- `Closure`

If not provided, the chatbot falls back to the Filament panel primary color.

```php
ChatbotPlugin::make()
    ->primaryColor('#10b981')
```

## Configuration

The published config file looks like this:

```php
<?php

return [
    'enabled' => true,
    'streaming' => false,
    'draggable' => false,
    'title' => 'AI Assistant',
    'description' => 'Ask anything about this app.',
    'placeholder' => 'Type your message...',
    'empty_state_heading' => 'Need a hand?',
    'empty_state_description' => 'Ask for help with data, workflow, or admin tasks.',
    'send_button_label' => 'Send',
    'new_chat_label' => 'New chat',
    'agent' => Darkclow4\FilamentChatbot\Ai\Agents\FilamentChatbotAgent::class,
    'provider' => null,
    'model' => null,
    'max_messages' => 100,
];
```

### Configuration Precedence

Plugin API values take priority over config values for `enabled`, `streaming`, and `draggable`.

Resolution order:

1. use plugin API value if it has been configured
2. otherwise fall back to config value

## Choosing The Agent

The most important config value is `agent`.

It should contain a fully qualified class name for the Laravel AI agent you want to use, for example:

```php
'agent' => App\Ai\Agents\AdminPanelAgent::class,
```

The configured agent should:

- implement `Laravel\Ai\Contracts\Agent`
- implement `Laravel\Ai\Contracts\Conversational`
- support remembered conversations, typically by using `Laravel\Ai\Concerns\RemembersConversations`

Example custom agent:

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;

class AdminPanelAgent implements Agent, Conversational
{
    use Promptable;
    use RemembersConversations;

    public function instructions(): string
    {
        return 'You are a helpful admin assistant for our Filament panel.';
    }
}
```

## Provider And Model Override

By default, the package lets Laravel AI use your default provider from `config/ai.php`.

If you want to force a provider or model for the chatbot only, set these values:

```php
'provider' => 'gemini',
'model' => 'gemini-2.5-flash',
```

If either value is `null`, the package falls back to the agent / SDK defaults.

## Streaming Mode

By default, streaming is disabled.

When enabled, the package uses direct streaming from Laravel AI and Livewire's streaming support to progressively render assistant output in the chat bubble.

This mode does **not** require:

- Reverb
- websockets
- broadcast channels

It streams directly over the Livewire request/response cycle.

## Conversation Storage

The chatbot stores conversation history in Laravel AI's database tables:

- `agent_conversations`
- `agent_conversation_messages`

Each signed-in Filament user gets their own remembered conversation context. The widget resumes the latest conversation for that user when possible.

## Markdown And Math Rendering

Assistant responses are rendered as HTML from Markdown.

This means common Markdown features such as:

- bold
- italic
- lists
- inline code
- code blocks
- blockquotes

will be formatted in the chat UI.

## Default Agent

The package ships with a fallback agent:

```php
Darkclow4\FilamentChatbot\Ai\Agents\FilamentChatbotAgent::class
```

This is useful for quick setup, but in a real application you will usually want to point `agent` to your own agent class so you can define custom instructions, tools, and behavior.

## Testing

The package works well with Laravel AI fakes. For example:

```php
use App\Ai\Agents\AdminPanelAgent;

AdminPanelAgent::fake(['Hello from the assistant']);
```

## Notes

- If the chatbot UI does not appear after installation, run `php artisan filament:assets` again.
- If the chatbot cannot answer, make sure your AI provider credentials are configured in `config/ai.php` / `.env`.
- If you want database-backed history, Laravel AI migrations must be published and migrated.
