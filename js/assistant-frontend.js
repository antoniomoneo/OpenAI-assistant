jQuery(function($){
  $('.oa-assistant-chat').each(function(){
    var w=$(this),
        slug=w.attr('data-slug'),
        ajaxUrl=w.attr('data-ajax'),
        nonce=w.attr('data-nonce'),
        debug=w.data('debug')==1,
        msgs=w.find('.oa-messages'),
        debugLog=w.find('.oa-debug-log'),
        input=w.find('input[name="user_message"]'),
        threadKey='oa_thread_'+slug,
        threadId=localStorage.getItem(threadKey);
    if(threadId==='null' || threadId==='undefined'){ threadId=null; localStorage.removeItem(threadKey); }

    function renderMarkdown(t){
      var h=t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      h=h.replace(/^### (.*)$/gm,'<h3>$1</h3>');
      h=h.replace(/^## (.*)$/gm,'<h2>$1</h2>');
      h=h.replace(/^# (.*)$/gm,'<h1>$1</h1>');
      h=h.replace(/\*\*([^*]+)\*\*/g,'<strong>$1</strong>');
      h=h.replace(/\*([^*]+)\*/g,'<em>$1</em>');
      h=h.replace(/(?:\r\n|\r|\n)/g,'<br>');
      return h;
    }
    function scrollToBottom(){ msgs[0].scrollTop = msgs[0].scrollHeight; }
    function sendMessage(text){
      if(!text) return;
      msgs.append('<div class="msg user"><div class="msg-label">Tu dijiste</div><div class="msg-bubble">'+text+'</div></div>');
      var loader=$('<div class="msg bot loading"><div class="msg-label">Aura dijo</div><div class="msg-bubble"></div></div>').appendTo(msgs);
      input.val('').focus();
      scrollToBottom();
      fetch(ajaxUrl,{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:$.param({action:'oa_assistant_chat',nonce:nonce,slug:slug,message:text,thread_id:threadId||'',stream:1})
      }).then(function(res){
        if(!res.body){throw new Error('No stream');}
        var reader=res.body.getReader();
        var dec=new TextDecoder();
        var buf='',full='';
        function read(){
          reader.read().then(function(r){
            if(r.done){
              if(threadId) localStorage.setItem(threadKey, threadId);
              else localStorage.removeItem(threadKey);
              loader.removeClass('loading');
              scrollToBottom();
              return;
            }
            buf+=dec.decode(r.value,{stream:true});
            var lines=buf.split('\n');
            buf=lines.pop();
            lines.forEach(function(line){
              line=line.trim();
              if(!line||line==='[DONE]') return;
              if(line.indexOf('data: ')===0){
                try{
                  var obj=JSON.parse(line.slice(6));
                  var delta=obj.data&&obj.data.delta&&obj.data.delta.content?obj.data.delta.content[0].text.value:(obj.delta&&obj.delta.content?obj.delta.content[0].text.value:'');
                  if(delta){ full+=delta; loader.find('.msg-bubble').html(renderMarkdown(full)); }
                  if(obj.data&&obj.data.id){
                    threadId=obj.data.thread_id||threadId;
                    if(threadId) localStorage.setItem(threadKey, threadId);
                  }
                }catch(e){}
              }
            });
            read();
          });
        }
        read();
      }).catch(function(){
        loader.remove();
        msgs.append('<div class="msg error">Error al enviar</div>');
        scrollToBottom();
      });
    }
    w.find('.oa-form').on('submit', function(e){e.preventDefault(); sendMessage(input.val().trim());});
    input.on('keypress', function(e){ if(e.which===13){e.preventDefault(); sendMessage(input.val().trim());}});
  });
});
