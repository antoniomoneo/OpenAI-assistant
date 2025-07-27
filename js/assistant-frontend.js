jQuery(function($){
  $('.oa-assistant-chat').each(function(){
    var w=$(this),
        slug=w.attr('data-slug'),
        ajaxUrl=w.attr('data-ajax'),
        nonce=w.attr('data-nonce'),
        debug=w.data('debug')==1,
        msgs=w.find('.oa-messages'),
        debugLog=w.find('.oa-debug-log'),
        input=w.find('input[name="user_message"]');
    function sendMessage(text){
      if(!text) return;
      msgs.append('<div class="msg user">'+text+'</div>');
      input.val('').focus();
      $.post(ajaxUrl,{
        action:'oa_assistant_chat',
        nonce:nonce,
        slug:slug,
        message:text
      }).done(function(res){
        msgs.append('<div class="msg bot">'+(res.success?res.data.reply:res.data)+'</div>');
        if(debug && res.success && res.data.debug){
          debugLog.text(debugLog.text()+res.data.debug+'\n');
        }
      }).fail(function(){
        msgs.append('<div class="msg error">Error al enviar</div>');
      });
      msgs[0].scrollTop = msgs[0].scrollHeight;
    }
    w.find('.oa-form').on('submit', function(e){e.preventDefault(); sendMessage(input.val().trim());});
    input.on('keypress', function(e){ if(e.which===13){e.preventDefault(); sendMessage(input.val().trim());}});
  });
});
