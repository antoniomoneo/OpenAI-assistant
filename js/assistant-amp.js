(function(){
  const container = document.currentScript.closest('.oa-assistant-chat');
  if(!container) return;
  const slug  = container.getAttribute('data-slug');
  const ajax  = container.getAttribute('data-ajax');
  const nonce = container.getAttribute('data-nonce');
  const messages = container.querySelector('.oa-messages');
  const form = container.querySelector('.oa-form');
  const input = form.querySelector('input[name="user_message"]');

  function appendMessage(text, cls){
    const div = document.createElement('div');
    div.className = 'msg ' + cls;
    div.textContent = text;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
  }

  function appendLoading(){
    const div = document.createElement('div');
    div.className = 'msg loading';
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
        body:new URLSearchParams({action:'oa_assistant_chat', nonce:nonce, slug:slug, message:text})
      });
      const data = await resp.json();
      if(data.success){
        appendMessage(data.data.reply, 'bot');
      }else{
        appendMessage(data.data, 'error');
      }
    } catch(e){
      appendMessage('Error al enviar', 'error');
    } finally {
      loader.remove();
    }
  }

  form.addEventListener('submit', function(ev){
    ev.preventDefault();
    sendMessage(input.value.trim());
  });
})();
