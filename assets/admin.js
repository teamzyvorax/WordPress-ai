(function () {
  'use strict';
  const form = document.getElementById('zyvora-chat-form');
  if (!form) return;
  const prompt = document.getElementById('zyvora-prompt');
  const log = document.getElementById('zyvora-chat-log');
  const button = form.querySelector('button[type="submit"]');

  function message(role, text) {
    const item = document.createElement('div');
    item.className = 'zyvora-message ' + (role === 'user' ? 'zyvora-user' : 'zyvora-assistant');
    const heading = document.createElement('strong');
    heading.textContent = role === 'user' ? 'YOU' : 'ZYVORA AI';
    const body = document.createElement('p');
    body.textContent = text;
    item.append(heading, body);
    log.appendChild(item);
    log.scrollTop = log.scrollHeight;
    return body;
  }

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const text = prompt.value.trim();
    if (!text || button.disabled) return;
    message('user', text);
    prompt.value = '';
    button.disabled = true;
    button.textContent = 'Thinking…';
    const pending = message('assistant', 'Connecting to your AI provider…');
    const data = new FormData();
    data.append('action', 'zyvora_ai_chat');
    data.append('nonce', ZyvoraAI.nonce);
    data.append('prompt', text);
    try {
      const response = await fetch(ZyvoraAI.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.data && result.data.message ? result.data.message : 'Request failed.');
      pending.textContent = result.data.answer;
    } catch (error) {
      pending.textContent = 'Error: ' + error.message;
    } finally {
      button.disabled = false;
      button.textContent = 'Send message ↗';
      log.scrollTop = log.scrollHeight;
      prompt.focus();
    }
  });
})();