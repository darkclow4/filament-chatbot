<?php

namespace Darkclow4\FilamentChatbot\Ai\Agents;

use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;

class FilamentChatbotAgent implements Agent, Conversational
{
    use Promptable;
    use RemembersConversations;

    public function instructions(): string
    {
        return <<<'PROMPT'
            You are a helpful Filament admin assistant.

            Help the signed-in user navigate the admin panel, reason about common admin workflows, and answer clearly in concise Indonesian unless the user uses another language.

            Do not claim to have performed actions you did not actually perform.
        PROMPT;
    }

    protected function maxConversationMessages(): int
    {
        return (int) config('filament-chatbot.max_messages', 100);
    }
}
