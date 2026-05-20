let filamentChatbotKatexLoader = null

function loadFilamentChatbotMathRenderer() {
    if (window.renderMathInElement) {
        return Promise.resolve()
    }

    if (filamentChatbotKatexLoader) {
        return filamentChatbotKatexLoader
    }

    filamentChatbotKatexLoader = new Promise((resolve, reject) => {
        let katexStylesheetId = 'filament-chatbot-katex-styles'
        let katexScriptId = 'filament-chatbot-katex-script'
        let katexAutoRenderScriptId = 'filament-chatbot-katex-auto-render-script'

        if (! document.getElementById(katexStylesheetId)) {
            let stylesheet = document.createElement('link')
            stylesheet.id = katexStylesheetId
            stylesheet.rel = 'stylesheet'
            stylesheet.href = 'https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css'
            document.head.appendChild(stylesheet)
        }

        let loadScript = (id, src, callback) => {
            let existing = document.getElementById(id)

            if (existing) {
                if (existing.dataset.loaded === 'true') {
                    callback()
                } else {
                    existing.addEventListener('load', callback, { once: true })
                    existing.addEventListener('error', reject, { once: true })
                }

                return
            }

            let script = document.createElement('script')
            script.id = id
            script.src = src
            script.defer = true
            script.addEventListener('load', () => {
                script.dataset.loaded = 'true'
                callback()
            }, { once: true })
            script.addEventListener('error', reject, { once: true })
            document.head.appendChild(script)
        }

        loadScript(katexScriptId, 'https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js', () => {
            loadScript(katexAutoRenderScriptId, 'https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js', resolve)
        })
    })

    return filamentChatbotKatexLoader
}

function renderFilamentChatbotMath(element) {
    if (! element) {
        return
    }

    loadFilamentChatbotMathRenderer()
        .then(() => {
            if (! window.renderMathInElement) {
                return
            }

            window.renderMathInElement(element, {
                delimiters: [
                    { left: '$$', right: '$$', display: true },
                    { left: '$', right: '$', display: false },
                    { left: '\\(', right: '\\)', display: false },
                    { left: '\\[', right: '\\]', display: true },
                ],
                throwOnError: false,
                strict: 'ignore',
            })
        })
        .catch(() => {})
}

function wireChatbotControls() {
    const root = document.getElementById('filament-chatbot-root')

    if (! root || root.dataset.chatbotControlsBound === 'true') {
        return
    }

    const launcher = root.querySelector('[data-chatbot-launcher]')
    const closeButton = root.querySelector('[data-chatbot-close]')
    const newChatButton = root.querySelector('[data-chatbot-new-chat]')
    const sendButton = root.querySelector('[data-chatbot-send]')
    const textarea = root.querySelector('[data-chatbot-textarea]')
    const panel = root.querySelector('[data-chatbot-panel]')
    const messages = root.querySelector('[data-chatbot-messages]')
    const messagesList = root.querySelector('[data-chatbot-messages-list]')
    const typing = root.querySelector('[data-chatbot-typing]')

    if (! launcher || ! closeButton || ! newChatButton || ! sendButton || ! textarea || ! panel || ! messages || ! messagesList || ! typing) {
        return
    }

    let togglePanel = (open) => {
        panel.style.display = open ? '' : 'none'
        launcher.setAttribute('aria-expanded', open ? 'true' : 'false')
        launcher.style.display = open ? 'none' : ''

        if (open) {
            requestAnimationFrame(() => {
                messages.scrollTop = messages.scrollHeight
                renderFilamentChatbotMath(messagesList)
            })
        }
    }

    let setProcessing = (isProcessing) => {
        textarea.disabled = isProcessing
        sendButton.disabled = isProcessing
        typing.style.display = isProcessing ? 'flex' : 'none'

        if (isProcessing) {
            requestAnimationFrame(() => {
                messages.scrollTop = messages.scrollHeight
            })
        }
    }

    let escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')

    let emptyStateHeading = messages.dataset.emptyStateHeading ?? 'Need a hand?'
    let emptyStateDescription = messages.dataset.emptyStateDescription ?? 'Ask for help with data, workflow, or admin tasks.'
    let failedLabel = messages.dataset.failedLabel ?? 'Failed to send'
    let retryLabel = messages.dataset.retryLabel ?? 'Retry'
    let errorLabel = messages.dataset.errorLabel ?? 'Something went wrong. Please try again.'

    let renderMessages = (items) => {
        if (! Array.isArray(items) || ! items.length) {
            messagesList.innerHTML = `
                <div class="fi-chatbot-empty-state">
                    <p class="fi-chatbot-empty-state__heading">${escapeHtml(emptyStateHeading)}</p>
                    <p class="fi-chatbot-empty-state__description">${escapeHtml(emptyStateDescription)}</p>
                </div>
            `

            return
        }

        messagesList.innerHTML = items.map((chatMessage) => `
            <article class="fi-chatbot-message ${chatMessage.role === 'user' ? 'fi-chatbot-message--user' : 'fi-chatbot-message--assistant'}">
                <p class="fi-chatbot-message__role">${chatMessage.role === 'user' ? 'You' : 'Assistant'}</p>
                <div class="fi-chatbot-message__body">${chatMessage.html ?? escapeHtml(chatMessage.content)}</div>
            </article>
        `).join('')

        renderFilamentChatbotMath(messagesList)
    }

    let appendUserMessage = (content) => {
        let emptyState = messagesList.querySelector('.fi-chatbot-empty-state')

        if (emptyState) {
            emptyState.remove()
        }

        let optimisticId = `chatbot-pending-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`

        messagesList.insertAdjacentHTML('beforeend', `
            <article class="fi-chatbot-message fi-chatbot-message--user" data-chatbot-pending-id="${optimisticId}">
                <p class="fi-chatbot-message__role">You</p>
                <div class="fi-chatbot-message__body">${escapeHtml(content)}</div>
                <div class="fi-chatbot-message__meta" style="display: none;"></div>
            </article>
        `)

        return optimisticId
    }

    let markMessageFailed = (optimisticId, prompt) => {
        let message = messagesList.querySelector(`[data-chatbot-pending-id="${optimisticId}"]`)

        if (! message) {
            return
        }

        message.classList.add('fi-chatbot-message--failed')

        let meta = message.querySelector('.fi-chatbot-message__meta')

        if (! meta) {
            return
        }

        meta.style.display = 'flex'
        meta.innerHTML = `
            <span class="fi-chatbot-message__status">${escapeHtml(failedLabel)}</span>
            <button type="button" class="fi-chatbot-message__retry">${escapeHtml(retryLabel)}</button>
        `

        meta.querySelector('.fi-chatbot-message__retry')?.addEventListener('click', async () => {
            if (sendButton.disabled) {
                return
            }

            message.remove()
            let retryId = appendUserMessage(prompt)

            requestAnimationFrame(() => {
                messages.scrollTop = messages.scrollHeight
            })

            try {
                await callChatbot('sendPrompt', [prompt])
            } catch (error) {
                markMessageFailed(retryId, prompt)
            }
        }, { once: true })
    }

    let callChatbot = async (method, params = []) => {
        let componentId = root.querySelector('[wire\\:id]')?.getAttribute('wire:id')

        if (! componentId || ! window.Livewire || typeof window.Livewire.find !== 'function') {
            setProcessing(false)
            return
        }

        let wire = window.Livewire.find(componentId)

        if (! wire) {
            setProcessing(false)
            return
        }

        setProcessing(true)

        if (typeof wire.call === 'function') {
            let response = await wire.call(method, ...params)

            if (response && typeof response === 'object') {
                renderMessages(response.messages ?? [])
            }

            setProcessing(false)
            requestAnimationFrame(() => {
                messages.scrollTop = messages.scrollHeight
            })

            return response
        }

        if (typeof wire.$call === 'function') {
            let response = await wire.$call(method, ...params)

            if (response && typeof response === 'object') {
                renderMessages(response.messages ?? [])
            }

            setProcessing(false)
            requestAnimationFrame(() => {
                messages.scrollTop = messages.scrollHeight
            })

            return response
        }

        setProcessing(false)
    }

    launcher.addEventListener('click', () => {
        let isOpen = panel.style.display !== 'none'

        togglePanel(! isOpen)
    })

    closeButton.addEventListener('click', () => {
        togglePanel(false)
    })

    newChatButton.addEventListener('click', async () => {
        await callChatbot('startNewConversation')
        textarea.value = ''
    })

    sendButton.addEventListener('click', async () => {
        if (sendButton.disabled) {
            return
        }

        let prompt = textarea.value.trim()

        if (! prompt.length) {
            return
        }

        textarea.value = ''
        let optimisticId = appendUserMessage(prompt)
        requestAnimationFrame(() => {
            messages.scrollTop = messages.scrollHeight
        })

        try {
            await callChatbot('sendPrompt', [prompt])
        } catch (error) {
            markMessageFailed(optimisticId, prompt)
        }
    })

    textarea.addEventListener('keydown', async (event) => {
        if (textarea.disabled) {
            return
        }

        if (event.key !== 'Enter' || event.shiftKey) {
            return
        }

        event.preventDefault()

        let prompt = textarea.value.trim()

        if (! prompt.length) {
            return
        }

        textarea.value = ''
        let optimisticId = appendUserMessage(prompt)
        requestAnimationFrame(() => {
            messages.scrollTop = messages.scrollHeight
        })

        try {
            await callChatbot('sendPrompt', [prompt])
        } catch (error) {
            markMessageFailed(optimisticId, prompt)
        }
    })

    togglePanel(false)
    setProcessing(false)
    renderFilamentChatbotMath(messagesList)
    root.dataset.chatbotControlsBound = 'true'
}

function bootFilamentChatbot() {
    wireChatbotControls()
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootFilamentChatbot)
} else {
    bootFilamentChatbot()
}

document.addEventListener('livewire:init', () => {
    bootFilamentChatbot()

    Livewire.on('filament-chatbot-scroll', () => {
        requestAnimationFrame(() => {
            const container = document.getElementById('filament-chatbot-messages')

            if (container) {
                container.scrollTop = container.scrollHeight
            }
        })
    })
})

document.addEventListener('livewire:navigated', bootFilamentChatbot)

let chatbotBootAttempts = 0

let chatbotBootInterval = window.setInterval(() => {
    bootFilamentChatbot()

    chatbotBootAttempts += 1

    if (document.getElementById('filament-chatbot-root')?.dataset.chatbotControlsBound === 'true' || chatbotBootAttempts >= 20) {
        window.clearInterval(chatbotBootInterval)
    }
}, 500)
