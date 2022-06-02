var _folder_temp = 'ripTemp',
	_folder = '',
	_filename = '',
	_files,
	_file_loading = 0;

jQuery(document).ready(function()
{
	Cufon.replace('.cufon');
	
	var _s = $('#frmSearch');
	_s.submit(function()
	{
		ripTemp(_s);
		return false;
	});
	_s.find('.search_btn').click(function()
	{
		ripTemp(_s);
		return false;
	});
});


/**
 * Thực hiện ripTemp
 */
function ripTemp(_t)
{
	// remove html cũ
	temp_remove_progress();
	temp_remove_stats();
	temp_remove_result();
	
	var key = _t.find('input[name=key]').val();
	var field = 'ripTemp';
	
	_t.nstUI({
		method:	'loadAjax',
		loadAjax:{
			url: 'index.php?act=get&url='+key,
			field: {load: field+'_load', show: field+'_show'},
			datatype: 'html',
			callback: function(data, settings)
			{
				var data_json = str_to_json(data);
				if (data_json)
				{
					_folder = data_json.folder;
					_filename = data_json.filename;
					_files = data_json.files;
					_file_loading = 0;
					file_download(_file_loading);
				}
				else
				{
					alert('Có lỗi xẩy ra trong quá trình xử lý\nHãy thử lại');
				}
			},
		}
	});
}

/**
 * Download file
 */
function file_download(f)
{
	var url = _files[f].url;
	var local = _folder + '/' + _files[f].local;
	
	// Hiển thị loading file
	temp_set_load(url);
	
	$(this).nstUI({
		method:	'loadAjax',
		loadAjax:{
			url: 'index.php?act=down&url='+url+'&local='+local,
			field: '',
			datatype: 'html',
			callback: function(data, settings)
			{
				// Remove loading file
				temp_set_load('');
				
				// Chuyển data sang dạng json
				var data_json = str_to_json(data);
				
				// Hiển thị trạng thái file và cập nhật progress
				if (data_json && data_json.complete == true)
				{
					_files[f].complete = true;
					temp_set_result(_file_loading, url, local, 'complete');
					temp_set_progress();
				}
				else
				{
					_files[f].complete = false;
					temp_set_result(_file_loading, url, local, 'error');
				}
				
				// Download file tiếp theo
				var file_next = file_get_next_download(_file_loading+1);
				if (file_next != -1)
				{
					_file_loading = file_next;
					file_download(_file_loading);
				}
				else
				{
					// Hiển thị thống kê
					temp_set_stats();
				}
			},
		}
	});
}

/**
 * Download lại các file bị lỗi
 */
function file_reload()
{
	var file = file_get_next_download(0);
	if (file != -1)
	{
		_file_loading = file;
		file_download(_file_loading);
	}
}

/**
 * Lấy file download tiếp theo
 */
function file_get_next_download(f)
{
	if (f >= _files.length || f<0)
	{
		return -1;
	}
	
	var i;
	for(i=f; i<_files.length; i++)
	{
		if (!_files[i].complete)
		{
			return i;
		}
	}
	return -1;
}

/**
 * 
 * Lấy tổng số file
 */
function file_get_total(type)
{
	var f = 0, total_complete = 0;
	for(f=0; f<_files.length; f++)
	{
		if (_files[f].complete)
		{
			total_complete ++
		}
	}
	
	switch (type)
	{
		case 'complete':
			return total_complete;
		case 'error':
			return _files.length-total_complete;
		default:
			return _files.length;
	}
}

/**
 * Set file loading
 */
function temp_set_load(url)
{
	var html = '';
	if (url)
	{
		html = $('#temp_html').find('div[name=loading]').html();
		html = html.replace(/{file.URL}/g, url);
	}
	$('#files_html').find('div[name=loading]').html(html);
}

/**
 * Hiển thị file sau khi load
 */
function temp_set_result(id, url, local, status)
{
	var html = $('#temp_html').find('div[name=result]').html();
	html = html.replace(/{file.ID}/g, id);
	html = html.replace(/{file.URL}/g, url);
	html = html.replace(/{file.LOCAL}/g, local);
	html = html.replace(/{file.FOLDER}/g, _folder_temp);
	html = html.replace(/{file.STATUS}/g, status);
	
	var _result = $('#files_html').find('div[name=result]');
	var _valid = _result.find('div[file='+id+']');
	if (_valid.html())
	{
		_valid.html(html);
	}
	else
	{
		_result.prepend(html);
	}
}

/**
 * Xóa danh sách các file đã load
 */
function temp_remove_result()
{
	$('#files_html').find('div[name=result]').html('');
}


/**
 * Hiển thị thống kê file
 */
function temp_set_stats()
{
	var complete = file_get_total('complete');
	var error = _files.length - complete;
	
	var _temp = $('#temp_html').find('div[name=stats]');
	var html = _temp.html();
	html = html.replace(/{file.TOTAL}/g, _files.length);
	html = html.replace(/{file.ERROR}/g, error);
	html = html.replace(/{file.COMPLETE}/g, complete);
	html = html.replace(/{url.RELOAD}/g, complete);
	html = html.replace(/{url.RESULT}/g, _folder_temp+'/'+ _folder+'/'+_filename);

	var _result = $('#files_html').find('div[name=stats]');
	_result.html(html);
	if (!error)
	{
		_result.find('div[name=btn_error]').html('');
	}
	_result.hide().fadeIn();
}

/**
 * Xóa phần thống kê file
 */
function temp_remove_stats()
{
	$('#files_html').find('div[name=stats]').html('');
}

/**
 * Cập nhật progress
 */
function temp_set_progress()
{
	var percent = file_get_total('complete')*100/_files.length;
	percent = percent.toFixed(0);
	$('.progress').find('span').css('width', percent+'%');
}

/**
 * Xóa progress
 */
function temp_remove_progress()
{
	$('.progress').find('span').css('width', '0%');
}


/**
 * Chuyển biến từ str sang json
 */
function str_to_json(str)
{
	if (!str.length) return false;
	
	var start = str[0];
	var end = str[str.length-1];
	if ((start == '{' && end == '}') || (start == '[' && end == ']'))
	{
		var json = eval('(' + str + ')');
		return json;
	}
	return false
}
	