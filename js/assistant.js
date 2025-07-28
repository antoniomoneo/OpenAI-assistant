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

  function slugify(text){
    return text.toString().toLowerCase().trim()
      .replace(/[^\w\- ]+/g,'')
      .replace(/\s+/g,'-');
  }
  var slugInput = $('#oa_slug');
  $('#oa_name').on('input', function(){
    if(!slugInput.val()){
      slugInput.val(slugify($(this).val()));
    }
  });

  $('.oa-copy-slug').on('click', function(){
    var text = $(this).data('slug');
    if(!text) return;
    navigator.clipboard.writeText(text).then(function(){
      alert('Copiado');
    });
  });
});
