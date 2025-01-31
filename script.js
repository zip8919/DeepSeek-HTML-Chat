const chatContainer = document.getElementById('chatContainer');
const messageInput = document.getElementById('messageInput');
const sendButton = document.getElementById('sendButton');
const loading = document.getElementById('loading');
const errorDiv = document.getElementById('error');

let chatHistory = [];
const MAX_HISTORY_LENGTH = 10;

const mdRenderer = new marked.Renderer();
marked.setOptions({
    breaks: true,
    gfm: true,
    highlight: (code) => hljs.highlightAuto(code).value
});

function safeMarkdown(text) {
    const mathText = text
        .replace(/\$\$(.*?)\$\$/g, '<div class="math">$1</div>')
        .replace(/\$(.*?)\$/g, '<span class="math">$1</span>');

    const rawHtml = marked.parse(mathText);
    const cleanHtml = DOMPurify.sanitize(rawHtml, {
        ADD_TAGS: ['math', 'katex'],
        ADD_ATTR: ['class']
    });
    
    setTimeout(() => {
        document.querySelectorAll('.math').forEach(el => {
            katex.render(el.textContent, el, {
                throwOnError: false,
                displayMode: el.tagName === 'DIV'
            });
        });
    }, 50);
    
    return cleanHtml;
}

messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

sendButton.addEventListener('click', sendMessage);

async function sendMessage() {
    const message = messageInput.value.trim();
    if (!message) return;

    messageInput.disabled = true;
    sendButton.disabled = true;
    loading.style.display = 'block';
    errorDiv.style.display = 'none';

    const tempUserMsgId = appendTempMessage(message, 'user');
    messageInput.value = '';

    try {
        const response = await fetch('back.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                message: message,
                history: chatHistory.slice(-MAX_HISTORY_LENGTH)
            })
        });

        const data = await response.json();

        if (!data.success) {
            removeTempMessage(tempUserMsgId);
            showError(data.error || '请求失败');
            return;
        }

        const botMsgId = appendMessage(data.reply, 'bot', data.meta);
        chatHistory.push({ role: 'user', content: message });
        chatHistory.push({ role: 'assistant', content: data.reply });

        while (chatHistory.length > MAX_HISTORY_LENGTH * 2) {
            chatHistory.shift();
        }

        document.getElementById(botMsgId).scrollIntoView({
            behavior: 'smooth',
            block: 'end'
        });

    } catch (error) {
        removeTempMessage(tempUserMsgId);
        showError(`网络异常: ${error.message}`);
    } finally {
        messageInput.disabled = false;
        sendButton.disabled = false;
        loading.style.display = 'none';
        messageInput.focus();
    }
}

function appendTempMessage(text, type) {
    const msgId = `temp-${Date.now()}`;
    const messageDiv = document.createElement('div');
    messageDiv.id = msgId;
    messageDiv.className = `message ${type}-message temporary`;
    
    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';
    bubble.textContent = text;
    
    messageDiv.appendChild(bubble);
    chatContainer.appendChild(messageDiv);
    return msgId;
}

function removeTempMessage(msgId) {
    const elem = document.getElementById(msgId);
    if (elem) elem.remove();
}

function appendMessage(text, type, meta) {
    const msgId = `msg-${Date.now()}`;
    const messageDiv = document.createElement('div');
    messageDiv.id = msgId;
    messageDiv.className = `message ${type}-message`;

    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';

    if (type === 'bot' && text.includes('&lt;/think&gt;')) {
        const [thinkPart, replyPart] = text.split('&lt;/think&gt;');
        
        if (thinkPart.trim()) {
            const thinkSection = document.createElement('div');
            thinkSection.className = 'think-section';
            thinkSection.innerHTML = safeMarkdown(thinkPart);
            bubble.appendChild(thinkSection);
        }

        if (replyPart.trim()) {
            const replyDiv = document.createElement('div');
            replyDiv.className = 'reply-content';
            replyDiv.innerHTML = `
                <div class="reply-label">正式回复</div>
                ${safeMarkdown(replyPart)}
            `;
            bubble.appendChild(replyDiv);
        }
    } else {
        bubble.innerHTML = safeMarkdown(text);
    }

    if (meta) {
        const metaInfo = document.createElement('div');
        metaInfo.className = 'meta-info';
        metaInfo.innerHTML = `
            <span>使用上下文：${meta.context.used}条</span>
            ${meta.context.cut > 0 ? 
              `<span class="cut-info">(截断${meta.context.cut}条)</span>` : ''}
        `;
        bubble.appendChild(metaInfo);
    }

    messageDiv.appendChild(bubble);
    chatContainer.appendChild(messageDiv);
    return msgId;
}

function showError(msg) {
    errorDiv.textContent = `错误：${msg}`;
    errorDiv.style.display = 'block';
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}
