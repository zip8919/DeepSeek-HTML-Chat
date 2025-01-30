// 完整JavaScript
const chatContainer = document.getElementById('chatContainer')
const messageInput = document.getElementById('messageInput')
const sendButton = document.getElementById('sendButton')
const loading = document.getElementById('loading')
const errorDiv = document.getElementById('error')

messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        sendMessage()
    }
})

async function sendMessage() {
    const message = messageInput.value.trim()
    if (!message) return

    messageInput.disabled = true
    sendButton.disabled = true
    loading.style.display = 'block'
    errorDiv.style.display = 'none'

    appendMessage(message, 'user')
    messageInput.value = ''

    try {
        const response = await fetch('back.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message })
        })
        
        const data = await response.json()

        if (!data.success) {
            showError(data.error || '请求失败')
            return
        }

        appendMessage(data.reply, 'bot')

    } catch (error) {
        showError(`异常: ${error.stack}`)
    } finally {
        messageInput.disabled = false
        sendButton.disabled = false
        loading.style.display = 'none'
        messageInput.focus()
    }
}

function appendMessage(text, type) {
    const messageDiv = document.createElement('div')
    messageDiv.className = `message ${type}-message`
    
    const bubble = document.createElement('div')
    bubble.className = 'message-bubble'

    if (type === 'bot' && text.includes('&lt;/think&gt;')) {
        const parts = text.split('&lt;/think&gt;')
        const thinkContent = parts[0].trim()
        const replyContent = parts[1].trim()

        // 思考部分
        if (thinkContent) {
            const thinkSection = document.createElement('div')
            thinkSection.className = 'think-section'
            
            const thinkLabel = document.createElement('div')
            thinkLabel.className = 'think-label'
            thinkLabel.textContent = '思考过程'
            
            const thinkContentDiv = document.createElement('div')
            thinkContentDiv.className = 'think-content'
            thinkContentDiv.textContent = thinkContent
            
            thinkSection.appendChild(thinkLabel)
            thinkSection.appendChild(thinkContentDiv)
            bubble.appendChild(thinkSection)
        }

        // 正式回复
        if (replyContent) {
            const replyContentDiv = document.createElement('div')
            replyContentDiv.className = 'reply-content'
            replyContentDiv.textContent = replyContent
            bubble.appendChild(replyContentDiv)
        }
    } else {
        bubble.textContent = text
    }

    messageDiv.appendChild(bubble)
    chatContainer.appendChild(messageDiv)
    
    // 滚动逻辑
    const inputArea = document.querySelector('.input-area')
    const scrollOffset = chatContainer.scrollHeight - chatContainer.clientHeight
    const inputHeight = inputArea ? inputArea.offsetHeight : 0
    
    chatContainer.scrollTo({
        top: scrollOffset + inputHeight,
        behavior: 'smooth'
    })
}

function showError(msg) {
    errorDiv.textContent = `错误：${msg}`
    errorDiv.style.display = 'block'
    setTimeout(() => {
        errorDiv.style.display = 'none'
    }, 5000)
}