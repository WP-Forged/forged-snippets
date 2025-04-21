jQuery(function($){
  $('#sf_select_all').on('click', function(){
    $('input[name="bulk_ids[]"]').prop('checked', this.checked);
  });
  $('.sf-toggle-btn').on('click', function(){
    var btn   = $(this),
        id    = btn.data('id'),
        nonce = btn.data('nonce');
    $.post(ajaxurl, { action:'sf_toggle', id:id, nonce:nonce })
      .done(function(res){
        if ( res.success ) {
          btn.toggleClass('active inactive');
        } else {
          alert('Error toggling status');
        }
      })
      .fail(function(){
        alert('Server error');
      });
  });
  if ( $('#code').length && typeof wp !== 'undefined' && wp.codeEditor ) {
    var cfg = wp.codeEditor.defaultSettings
      ? $.extend({}, wp.codeEditor.defaultSettings)
      : {};
    cfg.codemirror = $.extend({}, cfg.codemirror, {
      mode: 'htmlmixed',
      lineNumbers: true,
      autoCloseTags: true,
      indentUnit: 4,
      tabSize: 4,
      indentWithTabs: false
    });
    wp.codeEditor.initialize($('#code'), cfg);
  } else {
    $('#code').css({ fontFamily:'monospace', height:'200px' });
  }
});
