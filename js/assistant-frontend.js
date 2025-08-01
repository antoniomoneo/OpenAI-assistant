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
    function scrollToBottom(){ msgs[0].scrollTop = msgs[0].scrollHeight; }
    function sendMessage(text){
      if(!text) return;
      msgs.append('<div class="msg user">'+text+'</div>');
      var loader=$('<div class="msg loading"></div>').appendTo(msgs);
      input.val('').focus();
      scrollToBottom();
      $.post(ajaxUrl,{
        action:'oa_assistant_chat',
        nonce:nonce,
        slug:slug,
        message:text,
        thread_id:threadId||''
      }).done(function(res){
        loader.remove();
        if(res.success && res.data.thread_id){
          threadId=res.data.thread_id;
          localStorage.setItem(threadKey,threadId);
        }
        msgs.append('<div class="msg bot">'+(res.success?res.data.reply:res.data)+'</div>');
        if(debug && res.success && res.data.debug){
          debugLog.text(debugLog.text()+res.data.debug+'\n');
        }
        scrollToBottom();
      }).fail(function(){
        loader.remove();
        msgs.append('<div class="msg error">Error al enviar</div>');
        scrollToBottom();
      });
    }
    w.find('.oa-form').on('submit', function(e){e.preventDefault(); sendMessage(input.val().trim());});
    input.on('keypress', function(e){ if(e.which===13){e.preventDefault(); sendMessage(input.val().trim());}});
  });
});
