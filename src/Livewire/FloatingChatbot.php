<?php

namespace Darkclow4\FilamentChatbot\Livewire;

use Darkclow4\FilamentChatbot\ChatbotPlugin;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Streaming\Events\TextDelta;
use Livewire\Component;
use RuntimeException;
use Throwable;

class FloatingChatbot extends Component
{
    public array $messages = [];

    public ?string $conversationId = null;

    public bool $sending = false;

    public string $streamedMessage = '';

    public function mount(): void
    {
        if (! $this->isEnabled() || ! $this->hasAuthenticatedUser()) {
            return;
        }

        $this->restoreLatestConversation();
        $this->loadMessages();
    }

    public function sendPrompt(string $prompt): array
    {
        if (! $this->isEnabled() || ! $this->hasAuthenticatedUser()) {
            $this->skipRender();

            return $this->payload();
        }

        $prompt = trim($prompt);

        if ($prompt === '') {
            $this->addError('message', 'Please enter a message.');

            $this->skipRender();

            return $this->payload();
        }

        $this->sending = true;

        try {
            $agent = $this->resolveAgent();

            if ($this->isStreamingEnabled()) {
                $response = $this->conversationId !== null && method_exists($agent, 'continue')
                    ? $agent->continue($this->conversationId, as: $this->getAuthenticatedUser())->stream(
                        $prompt,
                        provider: $this->configuredProvider(),
                        model: $this->configuredModel(),
                    )
                    : $agent->forUser($this->getAuthenticatedUser())->stream(
                        $prompt,
                        provider: $this->configuredProvider(),
                        model: $this->configuredModel(),
                    );

                $this->streamedMessage = '';

                foreach ($response as $event) {
                    if ($event instanceof TextDelta) {
                        $this->streamedMessage .= $event->delta;
                        $this->stream($this->formatMessageContent('assistant', $this->streamedMessage), replace: true, name: 'assistant-response');
                    }
                }

                $this->conversationId = $response->conversationId;
                $this->streamedMessage = '';
            } else {
                $response = $this->conversationId !== null && method_exists($agent, 'continue')
                    ? $agent->continue($this->conversationId, as: $this->getAuthenticatedUser())->prompt(
                        $prompt,
                        provider: $this->configuredProvider(),
                        model: $this->configuredModel(),
                    )
                    : $agent->forUser($this->getAuthenticatedUser())->prompt(
                        $prompt,
                        provider: $this->configuredProvider(),
                        model: $this->configuredModel(),
                    );

                $this->conversationId = $response->conversationId;
            }

            $this->resetErrorBag('message');
            $this->loadMessages();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Chatbot failed to respond')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->sending = false;
        }

        $this->skipRender();

        return $this->payload();
    }

    public function startNewConversation(): array
    {
        $this->conversationId = null;
        $this->messages = [];
        $this->resetErrorBag();

        $this->skipRender();

        return $this->payload();
    }

    public function isEnabled(): bool
    {
        return ChatbotPlugin::isEnabledFor(
            user: ChatbotPlugin::authUser(),
            panel: ChatbotPlugin::currentPanel(),
        );
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled() && $this->hasAuthenticatedUser();
    }

    public function isStreamingEnabled(): bool
    {
        return ChatbotPlugin::isStreamingFor(
            user: ChatbotPlugin::authUser(),
            panel: ChatbotPlugin::currentPanel(),
        );
    }

    public function isDraggable(): bool
    {
        return ChatbotPlugin::isDraggableFor(
            user: ChatbotPlugin::authUser(),
            panel: ChatbotPlugin::currentPanel(),
        );
    }

    public function title(): string
    {
        return (string) config('filament-chatbot.title', 'AI Assistant');
    }

    public function description(): string
    {
        return (string) config('filament-chatbot.description', 'Ask anything about this admin panel.');
    }

    public function placeholder(): string
    {
        return (string) config('filament-chatbot.placeholder', 'Type your message...');
    }

    public function emptyStateHeading(): string
    {
        return (string) config('filament-chatbot.empty_state_heading', 'Need a hand?');
    }

    public function emptyStateDescription(): string
    {
        return (string) config('filament-chatbot.empty_state_description', 'Ask for help with data, workflow, or admin tasks.');
    }

    public function sendButtonLabel(): string
    {
        return (string) config('filament-chatbot.send_button_label', 'Send');
    }

    public function newChatLabel(): string
    {
        return (string) config('filament-chatbot.new_chat_label', 'New chat');
    }

    protected function restoreLatestConversation(): void
    {
        $userId = $this->getAuthenticatedUser()->getAuthIdentifier();

        if (! is_string($userId) && ! is_int($userId)) {
            return;
        }

        $this->conversationId = DB::table(config('ai.conversations.tables.conversations', 'agent_conversations'))
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->value('id');
    }

    protected function resolveAgent(): Agent&Conversational
    {
        $agentClass = config('filament-chatbot.agent');

        if (! is_string($agentClass) || $agentClass === '' || ! class_exists($agentClass)) {
            throw new RuntimeException('The configured chatbot agent class could not be found.');
        }

        $agent = method_exists($agentClass, 'make')
            ? $agentClass::make()
            : app($agentClass);

        if (! $agent instanceof Agent) {
            throw new RuntimeException('The configured chatbot agent must implement Laravel\\Ai\\Contracts\\Agent.');
        }

        if (! $agent instanceof Conversational) {
            throw new RuntimeException('The configured chatbot agent must implement Laravel\\Ai\\Contracts\\Conversational.');
        }

        if (! method_exists($agent, 'forUser')) {
            throw new RuntimeException('The configured chatbot agent must support remembered conversations.');
        }

        return $agent;
    }

    protected function loadMessages(): void
    {
        if ($this->conversationId === null) {
            $this->messages = [];

            return;
        }

        $this->messages = $this->messagesQuery()
            ->where('conversation_id', $this->conversationId)
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'created_at'])
            ->map(fn (object $message): array => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'html' => $this->formatMessageContent($message->role, $message->content),
                'created_at' => $message->created_at,
            ])
            ->all();
    }

    protected function formatMessageContent(string $role, ?string $content): string
    {
        $content ??= '';

        if ($role === 'assistant') {
            $content = preg_replace('/<system-reminder>.*?<\/system-reminder>/is', '', $content) ?? $content;

            return (string) Str::markdown($content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }

        return nl2br(e($content));
    }

    protected function messagesQuery(): Builder
    {
        return DB::table(config('ai.conversations.tables.messages', 'agent_conversation_messages'));
    }

    protected function configuredProvider(): ?string
    {
        $provider = config('filament-chatbot.provider');

        return is_string($provider) && $provider !== '' ? $provider : null;
    }

    protected function configuredModel(): ?string
    {
        $model = config('filament-chatbot.model');

        return is_string($model) && $model !== '' ? $model : null;
    }

    protected function getAuthenticatedUser(): Authenticatable
    {
        $user = ChatbotPlugin::authUser();

        if (! $user instanceof Authenticatable) {
            throw new RuntimeException('A signed-in Filament user is required to use the chatbot.');
        }

        return $user;
    }

    protected function hasAuthenticatedUser(): bool
    {
        return ChatbotPlugin::authUser() instanceof Authenticatable;
    }

    public function render()
    {
        return view('filament-chatbot::livewire.floating-chatbot');
    }

    /**
     * @return array{messages: array<int, array{id:string, role:string, content:string, html:string, created_at:mixed}>, streaming: bool}
     */
    protected function payload(): array
    {
        return [
            'messages' => $this->messages,
            'streaming' => $this->isStreamingEnabled(),
        ];
    }
}
