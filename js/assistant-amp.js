(function(){
  const container = document.currentScript.closest('.oa-assistant-chat');
  if(!container) return;
  const slug  = container.getAttribute('data-slug');
  const ajax  = container.getAttribute('data-ajax');
  const nonce = container.getAttribute('data-nonce');
  const threadKey = 'oa_thread_' + slug;
  let threadId = localStorage.getItem(threadKey);
  if(threadId === 'null' || threadId === 'undefined'){ threadId = null; localStorage.removeItem(threadKey); }
  const messages = container.querySelector('.oa-messages');
  const form = container.querySelector('.oa-form');
  const input = form.querySelector('input[name="user_message"]');

  function renderMarkdown(t){
    let h = t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    h = h.replace(/^### (.*)$/gm,'<h3>$1</h3>');
    h = h.replace(/^## (.*)$/gm,'<h2>$1</h2>');
    h = h.replace(/^# (.*)$/gm,'<h1>$1</h1>');
    h = h.replace(/\*\*([^*]+)\*\*/g,'<strong>$1</strong>');
    h = h.replace(/\*([^*]+)\*/g,'<em>$1</em>');
    h = h.replace(/(?:\r\n|\n|\r)/g,'<br>');
    return h;
  }

  function appendMessage(text, cls){
    const div = document.createElement('div');
    div.className = 'msg ' + cls;
    if(cls === 'user'){
      div.innerHTML = '<div class="msg-label">Tu dijiste</div><div class="msg-bubble"></div>';
      div.querySelector('.msg-bubble').textContent = text;
    }else if(cls === 'bot'){
      div.innerHTML = '<div class="msg-label">Aura dijo</div><div class="msg-bubble">'+renderMarkdown(text)+'</div>';
    }else{
      div.textContent = text;
    }
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
    return div;
  }

  function appendLoading(){
    const div = document.createElement('div');
    div.className = 'msg bot loading';
    div.innerHTML = '<div class="msg-label">Aura dijo</div><div class="msg-bubble"></div>';
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
    return div;
  }

  async function sendMessage(text){
    if(!text) return;
    appendMessage(text, 'user');
    const loader = appendLoading();
    input.value = '';
    try {
      const resp = await fetch(ajax, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({action:'oa_assistant_chat', nonce:nonce, slug:slug, message:text, thread_id:threadId||'', stream:1})
      });
      if(!resp.body) throw new Error();
      const reader = resp.body.getReader();
      const dec = new TextDecoder();
      let buf = '', full = '';
      while(true){
        const {done, value} = await reader.read();
        if(done) break;
        buf += dec.decode(value, {stream:true});
        const lines = buf.split('\n');
        buf = lines.pop();
        for(const line of lines){
          const l = line.trim();
          if(!l || l === '[DONE]') continue;
          if(l.indexOf('data: ') === 0){
            try{
              const obj = JSON.parse(l.slice(6));
              const delta = obj.data && obj.data.delta && obj.data.delta.content ? obj.data.delta.content[0].text.value : (obj.delta && obj.delta.content ? obj.delta.content[0].text.value : '');
              if(delta){ full += delta; loader.querySelector('.msg-bubble').innerHTML = renderMarkdown(full); }
              if(obj.data && obj.data.id){
                threadId = obj.data.thread_id || threadId;
                if(threadId) localStorage.setItem(threadKey, threadId);
              }
            }catch(e){}
          }
        }
      }
      if(threadId) localStorage.setItem(threadKey, threadId);
      else localStorage.removeItem(threadKey);
      loader.classList.remove('loading');
    } catch(e){
      loader.remove();
      appendMessage('Error al enviar', 'error');
    }
  }

  form.addEventListener('submit', function(ev){
    ev.preventDefault();
    sendMessage(input.value.trim());
  });
})();
