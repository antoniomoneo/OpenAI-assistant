// Admin JS for OpenAI Assistant v2.9.25
jQuery(function($){
  const $tbody = $('.oa-assistants-table tbody');
  const template = $('#oa-row-template').html();

  function nextIndex(){
    let max = -1;
    $tbody.find('tr').each(function(){
      const i = parseInt($(this).data('index'), 10);
      if(!isNaN(i) && i > max) max = i;
    });
    return max + 1;
  }

  $('.oa-add-assistant').on('click', function(e){
    e.preventDefault();
    const i = nextIndex();
    const row = template.replace(/__i__/g, i);
    $tbody.append(row);
  });

  $tbody.on('click', '.oa-remove-assistant', function(e){
    e.preventDefault();
    $(this).closest('tr').remove();
  });
});
