@if ($this->isAvailable())
    <div wire:key="filament-chatbot-floating" class="fi-chatbot">
        <button type="button" data-chatbot-launcher class="fi-chatbot-launcher" aria-controls="filament-chatbot-panel"
            aria-expanded="false">
            <span class="fi-chatbot-launcher__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8 10.5H16M8 14H13.5M20 11.2C20 15.6183 16.4183 19.2 12 19.2C10.8458 19.2 9.74859 18.9556 8.75775 18.5157L4.8 19.8L5.97654 16.4267C5.16838 15.0282 4.8 13.3888 4.8 11.2C4.8 6.78172 8.38172 3.2 12 3.2C16.4183 3.2 20 6.78172 20 11.2Z"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <span class="fi-chatbot-launcher__label">Assistant</span>
        </button>

        <div id="filament-chatbot-panel" data-chatbot-panel class="fi-chatbot-panel" style="display: none;">
            <div class="fi-chatbot-panel__header">
                <div>
                    <h2 class="fi-chatbot-panel__title">{{ $this->title() }}</h2>
                    <p class="fi-chatbot-panel__description">{{ $this->description() }}</p>
                </div>

                <div class="fi-chatbot-panel__actions">
                    <button type="button" data-chatbot-new-chat class="fi-chatbot-panel__action">
                        {{ $this->newChatLabel() }}
                    </button>

                    <button type="button" data-chatbot-close class="fi-chatbot-panel__close"
                        aria-label="Close chatbot">
                        &times;
                    </button>
                </div>
            </div>

            <div id="filament-chatbot-messages" data-chatbot-messages
                data-empty-state-heading="{{ $this->emptyStateHeading() }}"
                data-empty-state-description="{{ $this->emptyStateDescription() }}" data-failed-label="Failed to send"
                data-retry-label="Retry" data-error-label="Something went wrong. Please try again."
                class="fi-chatbot-messages">
                <div data-chatbot-messages-list>
                    @forelse ($messages as $chatMessage)
                        <article @class([
                            'fi-chatbot-message',
                            'fi-chatbot-message--user' => $chatMessage['role'] === 'user',
                            'fi-chatbot-message--assistant' => $chatMessage['role'] !== 'user',
                        ])>
                            <p class="fi-chatbot-message__role">
                                {{ $chatMessage['role'] === 'user' ? 'You' : 'Assistant' }}
                            </p>

                            <div class="fi-chatbot-message__body">{!! $chatMessage['html'] !!}</div>
                        </article>
                    @empty
                        <div class="fi-chatbot-empty-state">
                            <p class="fi-chatbot-empty-state__heading">{{ $this->emptyStateHeading() }}</p>
                            <p class="fi-chatbot-empty-state__description">{{ $this->emptyStateDescription() }}</p>
                        </div>
                    @endforelse
                </div>

                <div data-chatbot-typing class="fi-chatbot-typing">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>

            <div class="fi-chatbot-form">
                <textarea id="filament-chatbot-message" data-chatbot-textarea rows="3" class="fi-chatbot-form__textarea"
                    placeholder="{{ $this->placeholder() }}"></textarea>

                <div class="fi-chatbot-form__footer">
                    <button type="button" data-chatbot-send class="fi-chatbot-form__submit">
                        {{ $this->sendButtonLabel() }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
