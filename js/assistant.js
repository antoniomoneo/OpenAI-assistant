// Admin JS for OpenAI Assistant v3
jQuery(function($){
  $('.oa-recover-key').on('click', function(e){
    e.preventDefault();
    $.post(oaAssistant.ajax_url, {
      action: 'oa_assistant_send_key',
      nonce: oaAssistant.nonce
    }).done(function(res){
      alert(res.success ? 'Email enviado' : 'Error: ' + res.data);
    }).fail(function(){
      alert('Error al enviar');
    });
  });
});
