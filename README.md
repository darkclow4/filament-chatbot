# Filament Chatbot

`filament-chatbot` is a floating chatbot plugin for Filament 5 powered by `laravel/ai`.

It mounts a global Livewire chat widget inside your Filament panel, stores conversation history using Laravel AI's conversation tables, and lets you choose which AI agent class should handle the conversation through configuration.

## Features

- Built for Filament 4 or newer
- Powered by `laravel/ai`
- Floating chatbot available across the Filament panel
- Database-backed remembered conversations
- Configurable `agent` class via config
- Optional provider and model overrides

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

## Configuration

The published config file looks like this:

```php
<?php

return [
    'enabled' => true,
    'title' => 'AI Assistant',
    'description' => 'Ask anything about this admin panel.',
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

## Example App Config

```php
<?php

return [
    'enabled' => true,
    'title' => 'Ops Assistant',
    'description' => 'Ask about users, workflow, or admin operations.',
    'placeholder' => 'Ask the assistant...',
    'agent' => App\Ai\Agents\AdminPanelAgent::class,
    'provider' => 'gemini',
    'model' => 'gemini-2.5-flash',
    'max_messages' => 100,
];
```

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
