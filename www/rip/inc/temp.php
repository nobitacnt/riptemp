<?php

/**
 * Template Class
 * 
 * Tải và xử lý các file template
 */
class Template
{
	private $ext = ".html";
	private $folder = 'temp';
	private $cache_tpl = array(); // Biến lưu html của các file temp đã get
	
	/**
	 * Get Template File
	 * 
	 * Lấy html của file temp
	 * 
	 * @access	public
	 * @param	string	tên file temp
	 * @return	string
	 */
	public function get($filename)
	{
		$url = $this->folder.'/'.$filename.$this->ext;
		
		if (!file_exists($url))
		{
			exit("Không tìm thấy file : <b>".$url."</b>");
		}
		
		if (isset($this->cache_tpl['file_'.$filename]))
		{
		    $file_content = $this->cache_tpl['file_'.$filename];
		}
		else
		{
			$this->cache_tpl['file_'.$filename] = $file_content = file_get_contents($url);
		}
		
		return $file_content;
	}
	
	/**
	 * Get Block
	 * 
	 * Lấy html của một block
	 * 
	 * @access	public
	 * @param	string	html
	 * @param 	string	tên block
	 * @return	string
	 */
	public function get_block($html, $block)
	{
	    $matchs = array();
	    preg_match('/<!-- BLOCK '.$block.' -->(.*?)<!-- #BLOCK '.$block.' -->/s', $html, $matchs);
	    
	    if (isset($matchs[1]))
	    {
	        $matchs[1] = stripslashes($matchs[1]);
	        return $matchs[1];
	    }
	    
	    return '';
	}
	
	/**
	 * Set Block
	 * 
	 * Gán giá trị cho một block
	 * 
	 * @access	public
	 * @param 	string	html
	 * @param 	string 	mảng chứa tên block và giá trị muốn gán
	 * @return	string
	 */
	public function set_block($html, $arr)
	{
	    if (!is_array($arr))
	    {
	        return $html;
	    }
	    
		foreach ($arr as $block => $val)
		{
			$html = preg_replace('/<!-- BLOCK '.$block.' -->(.*?)<!-- #BLOCK '.$block.' -->/s', $val, $html);
		}
		
		return $html;
	}
	
	/**
	 * Set Value
	 * 
	 * Gán giá trị cho các biến
	 * 
	 * @access	public
	 * @param 	string	html
	 * @param 	string 	mảng chứa tên biến và giá trị muốn gán
	 * @return	string
	 */
	public function set_value($html, $arr)
	{
		if (!is_array($arr))
	    {
	        return $html;
	    }
	    
		foreach ($arr as $param => $val)
		{
		    $html = str_replace('{'.$param.'}', $val, $html);
		}
		
		return $html;
	}
	
	/**
	 * Get All Block
	 * 
	 * Lấy tất cả các block có trong html
	 * 
	 * @access	public
	 * @param 	string	html
	 * @return	string
	 */
	public function get_all_block($html)
	{
	    $matchs = array();
		preg_match_all('/<!-- BLOCK (.*?) -->(.*?)<!-- #BLOCK (.*?) -->/s', $html, $matchs, PREG_SET_ORDER);
		
		$blocks = array();
		foreach ($matchs as $k => $v)
		{
		    if ($v[1] != $v[3])
		    {
		        continue;
		    }
		    $blocks[$v[1]] = $v[2];
		}
		
		return $blocks;
	}
	
	/**
	 * Show Html
	 * 
	 * In html ra màn hình
	 * 
	 * @access	public
	 * @param	string	module html
	 * @param	string	tên layout
	 * @return	null
	 */
	public function show($html)
	{
	    // Gọi các Widget trong layout
		$html = preg_replace('#<!-- WIDGET (.*?)\((.*?)\) -->#se', '$this->call_widget("\\1", "\\2");', $html);
		
		// Loại bỏ các tab block không dùng đến
		$html = preg_replace('/<!-- BLOCK (.*?) -->(.*?)<!-- #BLOCK (.*?) -->/s', '${2}', $html);
		
		// Set giá trị của các biến global
		$html = $this->set_value($html,
            array(
           		'site.TEMP'    => $this->folder,
            )
        );
        
		echo $html;
	}
	
	/**
	 * Call Widget
	 * 
	 * Gọi các Widget trong layout
	 * 
	 * @access	private
	 * @param 	string	tên của method
	 * @param 	string	biến truyền cho method
	 * @return	string
	 */
	private function call_widget($method, $args = '')
	{
		$args = trim(stripslashes($args));
		
		if ($args)
		{
		    $cmd = "return ".$method."(".$args.");";
		}
		else
		{
		    $cmd = "return ".$method."();";
		}
		
		$result = eval($cmd);
		
		return $result;
	}
}
?>