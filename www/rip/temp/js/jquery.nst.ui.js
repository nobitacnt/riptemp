(function($){
	$.fn.nstUI = function(user_setting)
	{
		var m_default = {
			method:	'',
			loadAjax: {url: '', data: '', field: {load:'', show:''}, datatype: 'html', callback: function(){}},
		};

		var m_settings = $.extend({}, m_default, user_setting);
		
		return $(this).each(function()
		{
			var _t = $(this);
			var _f = $(this).find('form');
			var submitFlag = false;
			
			switch (m_settings.method)
			{
				case 'loadAjax':
					loadAjaxHandle();
					break;
				default:
					alert("Method " + m_settings.method + " does not exist!");
					break;
			}
			
			function loadAjaxHandle()
			{
				var url = m_settings.loadAjax.url;
				var field = m_settings.loadAjax.field;
				
				if (!url) return false;
				
				loader('show', field.load);
				
				$.post(url, m_settings.loadAjax.data,
					function(data)
					{
						loader('hide', field.load);
						loader('result', field.show, data);
						
						if (typeof m_settings.loadAjax.callback == "function")
						{
							m_settings.loadAjax.callback.call(this, data, m_settings.loadAjax);
						}
					},
					m_settings.loadAjax.datatype)
				.error(function()
				{
					loader('hide', field.load);
					loader('error', field.show, url);
				});
				
				return false;
			}

			function loader(run, field, data)
			{
				if (!field) return;
				
				switch(run)
				{
					case 'hide':
						$("#"+field).hide().fadeOut('fast');
						break;
					case 'result':
						$("#"+field).html(data).hide().fadeIn('fast');
						break;
					case 'error':
						$("#"+field).html('Không tìm thấy liên kết: <b>'+data+'</b>').hide().fadeIn('fast');
						break;
					default:
						$("#"+field).html('<div id="loader">Working ...</div>').hide().fadeIn();
						break;
				}
			}
		});
	}
})(jQuery);