const initialiseStorefrontAdvisor = () => {
    const widget = document.querySelector('[data-storefront-advisor]');
    if (!widget || widget.dataset.ready) return;
    widget.dataset.ready = 'true';
    const trigger = widget.querySelector('#maisonAiTrigger');
    const panel = widget.querySelector('#maisonAiPanel');
    const close = widget.querySelector('#maisonAiCloseBtn');
    const form = widget.querySelector('#maisonAiForm');
    const input = widget.querySelector('#maisonAiInput');
    const messages = widget.querySelector('#maisonAiMessages');
    const typing = widget.querySelector('#maisonAiTyping');
    const setOpen = (open) => { panel.classList.toggle('is-collapsed', !open); panel.setAttribute('aria-hidden', String(!open)); trigger.setAttribute('aria-expanded', String(open)); if (open) window.setTimeout(() => input.focus(), 100); };
    const append = (text, role) => { const item = document.createElement('div'); item.className = `maison-ai-msg maison-ai-msg--${role}`; const bubble = document.createElement('div'); bubble.className = 'maison-ai-msg__bubble'; bubble.textContent = text; item.append(bubble); messages.append(item); messages.scrollTop = messages.scrollHeight; };
    const appendResults = (result) => { const group = document.createElement('div'); group.className = 'maison-ai-results'; [...(result.products ?? []), ...(result.promotions ?? [])].forEach((item) => { const link = document.createElement('a'); link.href = item.url; link.textContent = item.price_label ? `${item.name} · ${item.price_label}` : `${item.name} · ${item.code}`; group.append(link); }); if (group.childElementCount) messages.append(group); };
    const send = async (message) => { const token = document.querySelector('meta[name="csrf-token"]')?.content; append(message, 'user'); input.value = ''; typing.classList.remove('is-hidden'); try { const response = await fetch(widget.dataset.replyUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ message }) }); const result = await response.json(); if (!response.ok) throw new Error(result.message || 'Không thể tư vấn lúc này.'); append(result.reply, 'bot'); appendResults(result); } catch (error) { append(error.message || 'Không thể tư vấn lúc này.', 'bot'); } finally { typing.classList.add('is-hidden'); } };
    trigger.addEventListener('click', () => setOpen(panel.classList.contains('is-collapsed')));
    close.addEventListener('click', () => setOpen(false));
    form.addEventListener('submit', (event) => { event.preventDefault(); const message = input.value.trim(); if (message) send(message); });
    widget.querySelectorAll('[data-question]').forEach((chip) => chip.addEventListener('click', () => send(chip.dataset.question)));
    widget.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !panel.classList.contains('is-collapsed')) setOpen(false); });
};

initialiseStorefrontAdvisor();
