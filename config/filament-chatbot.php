<?php

use Darkclow4\FilamentChatbot\Ai\Agents\FilamentChatbotAgent;

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

    'agent' => FilamentChatbotAgent::class,

    'provider' => null,

    'model' => null,

    'max_messages' => 100,
];
