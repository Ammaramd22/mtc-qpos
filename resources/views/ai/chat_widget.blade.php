{{-- ===== QPOS AI Assistant Floating Widget ===== --}}
<style>
#vsai-fab {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(145deg, #f5c518 0%, #e6b800 55%, #c4920a 100%);
    box-shadow: 0 6px 22px rgba(245, 197, 24, 0.35);
    border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: transform .2s, box-shadow .2s;
}
#vsai-fab:hover { transform: scale(1.08); box-shadow: 0 8px 28px rgba(245, 197, 24, 0.45); }
#vsai-fab svg { width: 26px; height: 26px; stroke: #111; }
#vsai-badge {
    position: absolute; top: -3px; right: -3px;
    background: #ef4444; color: #fff; font-size: 9px; font-weight: 700;
    border-radius: 99px; min-width: 18px; height: 18px; padding: 0 4px;
    display: none; align-items: center; justify-content: center; line-height: 1;
}
#vsai-panel {
    position: fixed; bottom: 90px; right: 24px; z-index: 9998;
    width: 380px; max-width: calc(100vw - 32px);
    background: #141414 !important;
    border: 1px solid #2a2a2a;
    border-radius: 18px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.55), 0 0 0 1px rgba(245,197,24,0.06);
    display: flex; flex-direction: column; overflow: hidden;
    transform: scale(0.92) translateY(16px); opacity: 0; pointer-events: none;
    transition: transform .22s cubic-bezier(.34,1.56,.64,1), opacity .18s ease;
    height: 520px;
    color: #f5f5f5 !important;
}
#vsai-panel.vsai-open {
    transform: scale(1) translateY(0); opacity: 1; pointer-events: all;
}
.vsai-header {
    background: linear-gradient(135deg, #1a1a1a 0%, #111 100%);
    border-bottom: 1px solid #2a2a2a;
    padding: 14px 16px 12px; display: flex; align-items: center; gap: 10px; flex-shrink: 0;
}
.vsai-header-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(145deg, #f5c518, #d4a012);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.vsai-header-avatar svg { width: 20px; height: 20px; stroke: #111 !important; fill: none; }
.vsai-header-info { flex: 1; min-width: 0; }
.vsai-header-info h4 { margin: 0; font-size: 14px; font-weight: 800; color: #fafafa !important; }
.vsai-header-info p { margin: 0; font-size: 11px; color: #a3a3a3 !important; }
.vsai-header-actions { display: flex; gap: 6px; }
.vsai-header-btn {
    background: #1a1a1a; border: 1px solid #333; border-radius: 8px;
    color: #e5e5e5 !important; width: 30px; height: 30px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; transition: background .15s, border-color .15s;
}
.vsai-header-btn:hover { background: rgba(245,197,24,0.12); border-color: #f5c518; color: #f5c518 !important; }
.vsai-quick-btns {
    display: flex; gap: 6px; flex-wrap: wrap; padding: 10px 12px 8px;
    border-bottom: 1px solid #2a2a2a; flex-shrink: 0; background: #111;
}
.vsai-quick-btn {
    background: #1a1a1a; color: #e5e5e5 !important; border: 1px solid #333; border-radius: 99px;
    font-size: 11px; font-weight: 600; padding: 5px 10px; cursor: pointer;
    transition: background .15s, border-color .15s, color .15s; white-space: nowrap;
}
.vsai-quick-btn:hover {
    background: rgba(245,197,24,0.14); border-color: rgba(245,197,24,0.45); color: #f5c518 !important;
}
.vsai-messages {
    flex: 1; overflow-y: auto; padding: 12px 14px; display: flex; flex-direction: column; gap: 10px;
    -webkit-overflow-scrolling: touch; background: #0a0a0a;
}
.vsai-msg { display: flex; gap: 8px; align-items: flex-end; }
.vsai-msg--user { flex-direction: row-reverse; }
.vsai-msg-avatar {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 13px;
}
.vsai-msg--bot .vsai-msg-avatar {
    background: linear-gradient(145deg,#f5c518,#d4a012); color: #111;
}
.vsai-msg--user .vsai-msg-avatar { background: #262626; color: #f5f5f5; }
.vsai-bubble {
    max-width: 78%; padding: 9px 13px; border-radius: 14px;
    font-size: 13px; line-height: 1.5; word-break: break-word;
}
.vsai-msg--bot .vsai-bubble {
    background: #1a1a1a; color: #f5f5f5 !important; border: 1px solid #2a2a2a;
    border-bottom-left-radius: 4px;
}
.vsai-msg--user .vsai-bubble {
    background: linear-gradient(135deg, #f5c518, #e6b800); color: #111 !important;
    border-bottom-right-radius: 4px; font-weight: 600;
}
.vsai-bubble p { margin: 0 0 4px; color: inherit !important; }
.vsai-bubble p:last-child { margin: 0; }
.vsai-bubble ul { margin: 4px 0; padding-left: 16px; color: inherit !important; }
.vsai-bubble strong { font-weight: 800; color: inherit !important; }
.vsai-typing { display: flex; gap: 4px; align-items: center; padding: 4px 2px; }
.vsai-dot { width: 7px; height: 7px; border-radius: 50%; background: #f5c518; animation: vsai-bounce .9s infinite; }
.vsai-dot:nth-child(2) { animation-delay: .15s; }
.vsai-dot:nth-child(3) { animation-delay: .3s; }
@keyframes vsai-bounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }
.vsai-input-area {
    padding: 10px 12px; border-top: 1px solid #2a2a2a; display: flex; gap: 8px;
    align-items: flex-end; flex-shrink: 0; background: #111 !important;
}
#vsai-input {
    flex: 1;
    border: 1.5px solid #333 !important;
    border-radius: 12px;
    padding: 9px 12px;
    font-size: 13px;
    resize: none;
    outline: none;
    max-height: 100px;
    min-height: 38px;
    line-height: 1.4;
    font-family: inherit;
    transition: border-color .15s, box-shadow .15s;
    background: #0a0a0a !important;
    color: #fafafa !important;
    -webkit-text-fill-color: #fafafa !important;
    caret-color: #f5c518 !important;
    box-shadow: none !important;
}
#vsai-input::placeholder {
    color: #737373 !important;
    -webkit-text-fill-color: #737373 !important;
    opacity: 1 !important;
}
#vsai-input:focus {
    border-color: #f5c518 !important;
    box-shadow: 0 0 0 3px rgba(245, 197, 24, 0.18) !important;
}
#vsai-send {
    width: 38px; height: 38px; border-radius: 12px; border: none; cursor: pointer; flex-shrink: 0;
    background: linear-gradient(145deg, #f5c518, #d4a012); color: #111 !important;
    display: flex; align-items: center; justify-content: center;
    transition: filter .15s, transform .15s; font-size: 16px;
}
#vsai-send:hover { filter: brightness(1.05); transform: translateY(-1px); }
#vsai-send:disabled { opacity: .45; cursor: not-allowed; transform: none; }
.vsai-insights-link {
    display: flex; align-items: center; gap: 6px; padding: 8px 14px;
    background: #111; border-top: 1px solid #2a2a2a;
    font-size: 12px; font-weight: 700; color: #f5c518 !important; text-decoration: none;
    flex-shrink: 0; transition: background .15s;
}
.vsai-insights-link:hover { background: rgba(245,197,24,0.08); color: #fde68a !important; text-decoration: none; }
.vsai-insights-link i { color: inherit !important; }
@media (max-width: 480px) {
    #vsai-panel { width: calc(100vw - 16px); right: 8px; bottom: 80px; height: 480px; }
    #vsai-fab { bottom: 16px; right: 16px; }
}
</style>

{{-- FAB Button --}}
<button id="vsai-fab" title="AI Business Assistant" aria-label="Open AI Assistant">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2a5 5 0 0 1 5 5c0 3-2 5-5 5s-5-2-5-5a5 5 0 0 1 5-5z"/>
        <path d="M12 12v3"/>
        <path d="M9 14c-2.5.5-4 2.5-4 5h4"/>
        <path d="M15 14c2.5.5 4 2.5 4 5h-4"/>
        <path d="M8 21c0 1.5 1.5 2.5 4 2.5s4-1 4-2.5"/>
        <circle cx="10" cy="7" r=".8" fill="#111" stroke="none"/>
        <circle cx="14" cy="7" r=".8" fill="#111" stroke="none"/>
    </svg>
    <span id="vsai-badge"></span>
</button>

{{-- Chat Panel --}}
<div id="vsai-panel" role="dialog" aria-label="AI Business Assistant">
    <div class="vsai-header">
        <div class="vsai-header-avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a5 5 0 0 1 5 5c0 3-2 5-5 5s-5-2-5-5a5 5 0 0 1 5-5z"/>
                <path d="M12 12v3"/>
                <path d="M9 14c-2.5.5-4 2.5-4 5h4"/>
                <path d="M15 14c2.5.5 4 2.5 4 5h-4"/>
                <path d="M8 21c0 1.5 1.5 2.5 4 2.5s4-1 4-2.5"/>
                <circle cx="10" cy="7" r=".8" fill="#111" stroke="none"/>
                <circle cx="14" cy="7" r=".8" fill="#111" stroke="none"/>
            </svg>
        </div>
        <div class="vsai-header-info">
            <h4>QPOS AI Assistant</h4>
            <p id="vsai-status">● Ready &mdash; works offline (local data)</p>
        </div>
        <div class="vsai-header-actions">
            <button class="vsai-header-btn" id="vsai-clear-btn" title="Clear chat">
                <i class="fas fa-trash-alt" style="font-size:12px;"></i>
            </button>
            <button class="vsai-header-btn" id="vsai-close-btn" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="vsai-quick-btns">
        <button class="vsai-quick-btn" data-q="How are my sales today?">📊 Today's Sales</button>
        <button class="vsai-quick-btn" data-q="What are my top selling products this month?">🏆 Top Products</button>
        <button class="vsai-quick-btn" data-q="Which items are low in stock?">⚠️ Low Stock</button>
        <button class="vsai-quick-btn" data-q="Show me outstanding customer dues">💰 Dues</button>
        <button class="vsai-quick-btn" data-q="Give me 3 actionable tips to improve my business this week">💡 Business Tips</button>
    </div>

    <div class="vsai-messages" id="vsai-messages">
        <div class="vsai-msg vsai-msg--bot">
            <div class="vsai-msg-avatar">🤖</div>
            <div class="vsai-bubble">
                <p>👋 Hello! I'm your <strong>QPOS Business Assistant</strong>.</p>
                <p>I answer from your <strong>live POS data</strong> inside the software — no API key needed, works offline. Try a quick button or ask about sales, stock, dues, or tips.</p>
            </div>
        </div>
    </div>

    <a href="{{ route('ai.insights') }}" class="vsai-insights-link" target="_blank">
        <i class="fas fa-chart-line"></i> View AI Insights Dashboard
        <i class="fas fa-external-link-alt" style="margin-left:auto;font-size:10px;opacity:.6;"></i>
    </a>

    <div class="vsai-input-area">
        <textarea id="vsai-input" placeholder="Ask about sales, stock, tips…" rows="1"></textarea>
        <button id="vsai-send" title="Send">
            <i class="fas fa-paper-plane" style="font-size:14px;"></i>
        </button>
    </div>
</div>

<script>
(function() {
    var fab    = document.getElementById('vsai-fab');
    var panel  = document.getElementById('vsai-panel');
    var input  = document.getElementById('vsai-input');
    var send   = document.getElementById('vsai-send');
    var msgs   = document.getElementById('vsai-messages');
    var badge  = document.getElementById('vsai-badge');
    var isOpen = false;
    var history = [];
    var unread  = 0;

    function togglePanel() {
        isOpen = !isOpen;
        panel.classList.toggle('vsai-open', isOpen);
        if (isOpen) {
            unread = 0;
            badge.style.display = 'none';
            badge.textContent = '';
            setTimeout(function(){ input.focus(); }, 250);
        }
    }

    fab.addEventListener('click', togglePanel);
    document.getElementById('vsai-close-btn').addEventListener('click', togglePanel);

    document.getElementById('vsai-clear-btn').addEventListener('click', function() {
        msgs.innerHTML = '';
        history = [];
        appendBot('Chat cleared. Ask me anything about your business! 😊');
    });

    // Quick prompt buttons
    document.querySelectorAll('.vsai-quick-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var q = this.getAttribute('data-q');
            sendMessage(q);
        });
    });

    // Auto-resize textarea
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            doSend();
        }
    });

    send.addEventListener('click', doSend);

    function doSend() {
        var text = input.value.trim();
        if (!text || send.disabled) return;
        sendMessage(text);
        input.value = '';
        input.style.height = 'auto';
    }

    function sendMessage(text) {
        appendUser(text);
        var typingEl = appendTyping();
        send.disabled = true;

        fetch('{{ route("ai.chat") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ message: text, history: history })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            typingEl.remove();
            var reply = data.reply || 'Sorry, I could not get a response.';
            var mode = data.mode || 'local';
            var status = document.getElementById('vsai-status');
            if (status) {
                if (mode === 'gemini') {
                    status.textContent = '● Online — powered by Gemini';
                } else if (mode === 'local_fallback') {
                    status.textContent = '● Local fallback — live POS data';
                } else {
                    status.textContent = '● Ready — works offline (local data)';
                }
            }
            appendBot(reply);
            history.push({ role: 'user', text: text });
            history.push({ role: 'model', text: reply });
            if (history.length > 20) history = history.slice(-20);
            if (!isOpen) {
                unread++;
                badge.textContent = unread;
                badge.style.display = 'flex';
            }
        })
        .catch(function() {
            typingEl.remove();
            appendBot('❌ Could not reach the server. Check your connection to QPOS and try again.');
        })
        .finally(function() {
            send.disabled = false;
            input.focus();
        });
    }

    function appendUser(text) {
        var div = document.createElement('div');
        div.className = 'vsai-msg vsai-msg--user';
        div.innerHTML = '<div class="vsai-msg-avatar">👤</div>'
            + '<div class="vsai-bubble">' + escHtml(text).replace(/\n/g,'<br>') + '</div>';
        msgs.appendChild(div);
        scrollBottom();
    }

    function appendBot(text) {
        var div = document.createElement('div');
        div.className = 'vsai-msg vsai-msg--bot';
        div.innerHTML = '<div class="vsai-msg-avatar">🤖</div>'
            + '<div class="vsai-bubble">' + formatMd(text) + '</div>';
        msgs.appendChild(div);
        scrollBottom();
        return div;
    }

    function appendTyping() {
        var div = document.createElement('div');
        div.className = 'vsai-msg vsai-msg--bot';
        div.innerHTML = '<div class="vsai-msg-avatar">🤖</div>'
            + '<div class="vsai-bubble"><div class="vsai-typing">'
            + '<div class="vsai-dot"></div><div class="vsai-dot"></div><div class="vsai-dot"></div>'
            + '</div></div>';
        msgs.appendChild(div);
        scrollBottom();
        return div;
    }

    function scrollBottom() {
        msgs.scrollTop = msgs.scrollHeight;
    }

    function escHtml(t) {
        return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function formatMd(text) {
        // Bold **text**
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Bullet points
        text = text.replace(/^\s*[\*\-]\s+(.+)$/gm, '<li>$1</li>');
        text = text.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
        // Line breaks
        text = text.replace(/\n/g, '<br>');
        return text;
    }
})();
</script>
