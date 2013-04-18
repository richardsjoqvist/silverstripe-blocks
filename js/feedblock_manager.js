(function($) {

	$(document).ready(function() {
		setTimeout(function(){
			$('a.feedblock-refresh-link').click(function(e){
				e.preventDefault();
				e.stopPropagation();
				var obj = $(this);
				var url = obj.attr("href");
				feedblock_seticon(obj, 'loading.gif');
				$.ajax({
					url: url,
					success: function(data) {
						var iconobj = $(obj);
						if(data+'' == '1') {
							feedblock_seticon(iconobj, 'check.png');
						}
						else {
							feedblock_seticon(iconobj, 'error.png');
							alert("Could not refresh cache - please try again.");
						}
					},
					error: function(data) {
						var iconobj = $(obj);
						feedblock_seticon(iconobj, 'error.png');
						alert("Could not refresh cache - please try again.");
					}
				});

				return false;
			});
		},1000);
	});


	function feedblock_seticon(obj, icon) {
		var bi = obj.css('background-image');
		var bi_pattern = /(.*)(\/i\/)(.*)/;
		var bi = bi_pattern.exec(bi);
		obj.css('background-image',bi[1]+bi[2]+icon+')');
	}

})(jQuery);