(function($){
  $(document).ready(function(){
    
    $('#ctsearch-form').bind('submit', function(){
      var params = new Array();
      
      $.each($(this).serializeArray(), function(i, field){
        if(field.name == 'submit' || field.name == 'reset') return;
        if(field.name == 'l' && field.value == '') {
          // Empty search phrase -- will cause search to use default term(s)
             return;
        }
        params.push(field.name + '=' + encodeURIComponent(field.value));
      });

      do_search(params.join('&'));
      return false;
    });
    
    $('#ctsearch-form-reset').bind('click', function(){
      $(':input[name=l]', $(this).parent().parent()).val('');
      window.location.reload();
    });
    
    do_search();
  });
  
  /*
   * Executes the AJAX job search.
   */
  function do_search(qs) {
    var url = ctsearch.ajax_url;
    var tags=ctsearch.tags;
    var jobsearch=ctsearch.jobsearch;

    if(qs == null && tags && jobsearch){
        url += '&l=' + tags;
    }
 
  	if(qs != null && qs != undefined) {
	    url += '&' + qs;
  	}
 
  	$('#ctsearch-wrap').html('<div class="loading">Loading ...</div>');
  	
    $.ajax({url: url, action: 'search', success: function(res){
        $('#ctsearch-wrap').html(res);
        // Get the listeners for the pagination.
        $('#ctsearch-pager a.page-numbers').each(function(){
        	$(this).bind('click', function(){
        		var href = $(this).attr('href');
        		var qs = null;
        		if(href.indexOf('?') != -1) {
        			qs = href.substring(href.indexOf('?')+1);
        		}
        		do_search(qs);
        		return false;
        	});
        });
      },
      error: function(){
        $('#ctsearch-wrap').html('<div class="error">There was an error processing your search. Please try again later.</div>');
      }
    });
 
  }
  
  
})(jQuery);
