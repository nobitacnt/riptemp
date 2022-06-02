<?php
include_once('curl.php');

/**
 * Rip Temp Class
 * 
 * Lấy Temp của một trang web
 * 
 * @author		NST
 * @version		1.0
 * @copyright 	2011
 */
class ripTemp
{
    private $cURL;
    private $url;
    private $info;
    private $html;
    private $folder = 'ripTemp';
    private $cache = '_cache';
    private $files = array();
    
    /**
     * Construct ...
     */
    public function __construct()
    {
        $this->cURL =& new cURL();
    }
    
    /**
     * Get
     * 
     * Get html từ url, phân tích html
     * lấy ra các file cần download
     * 
     * @param string Url site cần rip temp
     */
    public function get($url)
    {
        /**
         * Khởi tạo ứng dụng
         */
        // Gán giá trị cho biến
        $this->url = $url;
        $this->info = $this->get_info($url);
        $this->folder .= '/'.$this->info['domain'];
        
        // Lấy nội dung file index trong cache
        $this->html = $this->cache_read($this->info['namelocal']);
        
        // Nếu không tồn tại index html trong cache
        if (!$this->html)
        {
            // Lấy nội dung từ url
            $this->html = $this->read($url);
            
            // Lưu file vào cache
            $this->cache_write($this->info['namelocal'], $this->html);
        }
        
        // Tạo file index html
        $this->write($this->info['namelocal'], $this->html);
        
        
        /**
         * Lấy danh sách các file cần Download
         */
        $js = $this->get_js();
        $css = $this->get_css();
        $img = $this->get_img();
        $img_css_index = $this->get_img_css($this->url, $this->info['namelocal']); // Các file img trong tab <style> ở index
        $files = array($js, $css, $img, $img_css_index);
        $this->get_files($files);
		//print_r($css);
		//exit();
        
        $result['folder'] = $this->info['domain'];
        $result['filename'] = $this->info['namelocal'];
        $result['files'] = $this->files;
        return $result;
    }

    /**
     * Get Js
     * 
     * Lấy các file js
     */
    private function get_js()
    {
        $jss = $this->get_component($this->html, 'js');
		
        if (!is_array($jss)) return FALSE;

        $result = array();
        foreach ($jss as $js)
        {
            $js0 = $js;
            $js = str_replace('\\/', '/', $js);
            $row = array();
            
            // Kiểm tra liên kết có phải la tuyệt đối hay không?
            if ($this->is_http($js))
            {
                $info = $this->get_info($js);
                
                $row['url'] = $js;
                $row['local'] = $info['domain'].'/'.$info['url'];
            }
            else
            {
                $js = $this->get_url($this->url, $js);
                $info = $this->get_info($js);
                
                $row['url'] = $js;
                $row['local'] = $info['url'];
            }
            $result[] = $row;
            
            // Down file
            //$this->down($row['url'], $row['local']);
            
            // Update lại đường dẫn của file trong index html
            $this->html = $this->replace_link($js0, $row['local'], $this->html);
        }
        
        // Cập nhật lại html của index html
        $this->write($this->info['namelocal'], $this->html);
        
        return $result;
    }

    /**
     * Get Js
     * 
     * Lấy các file css
     */
    private function get_css()
    {
        $style = $this->get_component($this->html, 'css');
		
        if (!is_array($style)) return FALSE;
        
        $result = array();
        foreach ($style as $css)
        {
            $css0 = $css;
            $css = str_replace('\\/', '/', $css);
            $row = array();
            
            // Kiểm tra liên kết có phải la tuyệt đối hay không?
            if ($this->is_http($css))
            {
                $info = $this->get_info($css);
                
                $row['url'] = $css;
                $row['local'] = $info['domain'].'/'.$info['url'];
            }
            else
            {
                $css = $this->get_url($this->url, $css);
                $info = $this->get_info($css);
                
                $row['url'] = $css;
                $row['local'] = $info['url'];
            }
            
            // Lấy các file img trong css
            $row['img'] = $this->get_img_css($row['url'], $row['local']);
            if (!is_array($row['img']))
            {
                unset($row['img']);
            }
            
            // Update lại đường dẫn của file trong index html
            $this->html = $this->replace_link($css0, $row['local'], $this->html);
            
            $result[] = $row;
        }
        
        // Cập nhật lại html của index html
        $this->write($this->info['namelocal'], $this->html);
        
        return $result;
    }
    
    /**
     * Get Img Css
     * 
     * Lấy các file image trong css
     * và cập nhật lại contents của css
     * 
     * @param srting	url của css
     * @param string	url local của css
     */
    private function get_img_css($css, $local)
    {
        // Down file css về folder cache
        $this->cache_down($css, $local);
        
        $html = ($css == $this->url) ? $this->html : $this->cache_read($local);
        $components = $this->get_component($html, 'img_css');

        if (!is_array($components)) return FALSE;
        
        $result = array();
        foreach ($components as $type => $files)
        {
            foreach ($files as $file)
            {
                $file0 = $file;
                $file = str_replace('\\/', '/', $file);
                $row = array();
                
                // Kiểm tra liên kết có phải la tuyệt đối hay không?
                if ($this->is_http($file))
                {
                    $dir = $this->get_info($local);
                    $dir = ($dir['path']) ? $dir['path'].'/' : '';
                    $dir .= 'local/';
                    
                    $file_info = $this->get_info($file);
                    $file_info = $this->get_info($dir.$file_info['basename']);
                    $file_href = 'local/'.$file_info['basename'];
                    
                    $row['url'] = $file;
                    $row['local'] = $file_info['url'];
                }
                else 
                {
                    $file_href = ltrim($file, '/');
                    $row['url'] = $this->get_url($css, $file);
                    $row['local'] = $this->get_url($local, $file);
                }
                            
                // Nếu là file css thì lấy các file image trong file đó [@import css]
                if ($type == 'css')
                {
                    $row['img'] = $this->get_img_css($row['url'], $row['local']);
                    if (!is_array($row['img']))
                    {
                        unset($row['img']);
                    }
                }
                
                $result[] = $row;
                
                // Dowload file
                //$this->down($row['url'], $row['local']);
                
                // Update lại đường dẫn của file trong file css
                $html = $this->replace_link($file0, $file_href, $html);
            }
        }

        // Cập nhật lại contents
        $this->write($local, $html);
        
        return $result;
    }
    
    /**
     * Get Img
     * 
     * Lấy các file img trong index html
     */
    private function get_img()
    {
        $html = $this->html;
        $local = $this->info['namelocal'];
        $imgs = $this->get_component($html, 'img');

        if (!is_array($imgs)) return FALSE;
    
        $result = array();
        foreach ($imgs as $img)
        {
            $img0 = $img;
            $img = str_replace('\\/', '/', $img);
            $row = array();
            
            // Kiểm tra liên kết có phải la tuyệt đối hay không?
            if ($this->is_http($img))
            {
                $dir = $this->get_info($local);
                $dir = ($dir['path']) ? $dir['path'].'/' : '';
                $dir .= 'local/';
                
                $img_info = $this->get_info($img);
                $img_info = $this->get_info($dir.$img_info['basename']);
                $img_href = 'local/'.$img_info['basename'];
                
                $row['url'] = $img;
                $row['local'] = $img_info['url'];
            }
            else 
            {
                $img_href = $img;
                $row['url'] = $this->get_url($this->url, $img);
                $row['local'] = $this->get_url($local, $img);
            }
            $result[] = $row;
            
            // Dowload file
            //$this->down($row['url'], $row['local']);
            
            // Update lại đường dẫn của img trong html
            $html = $this->replace_link($img0, $img_href, $html);
        }
        
        // Cập nhật lại contents
        $this->write($local, $html);
        
        return $result;
    }
    
   /**
     * Get Component
     * 
     * Lấy các thành phần trong html
     * 
     * @param string	html
     * @param string	tên thành phần muốn lấy
     */
    private function get_component($html, $component)
    {
        $match = array();
        $result = array();
        
        switch ($component)
        {
            case 'js':
                if (preg_match_all('/<script[^>]*? src=([^>]+?)[\s>]/is', $html, $match))
                {
                   $result = $match[1];
                }
                break;
                
            case 'css':
                if (preg_match_all('/<link[^>]*? rel=["\']?stylesheet["\']?[^>]*?>/is', $html, $match))
                {
                    foreach ($match[0] as $html_row)
                    {
                        if (preg_match('/<link[^>]*? href=([^>]+?)(\s|\/>|>)/is', $html_row, $match))
                        {
                            $result[] = $match[1];
                        }
                    }
                }
                break;
                
            case 'img_css':
                $html = preg_replace('/@import url\(([^\)]+?)\);/is', '@import ${1};', $html);
                
                if (preg_match_all('/@import (.+?);/is', $html, $match))
                {
                    $result['css'] = $match[1];
                }
                
                if (preg_match_all('/url\(([^\)]+?)\)/is', $html, $match))
                {
                    $result['img'] = $match[1];
                }
                break;
                
            case 'img':
                if (preg_match_all('/["\']([^"\']+?)\.(jpg|png|gif|bmp|swf|xml)["\']/is', $html, $match))
                {
                    $result = $match[0];
                }
                break;
        }
        
        if (count($result))
        {
            $result = $this->trim_array($result);
            return $result;
        }
        
        return FALSE;
    }

    /**
     * Trim Array
     * 
     * Loại bỏ những kí tự ở 2 đầu giá trị của array
     * 
     * @param array		array cần xử lý
     * @param string	danh sách kí tự sẽ xóa
     */
    private function trim_array($arr, $char = '"\'')
    {
        if (!is_array($arr)) return $arr;
        
        foreach ($arr as $k => $v)
        {
            $arr[$k] = (is_array($v)) ? $this->trim_array($v, $char) :  trim($v, $char);
        }
        
        return $arr;
    }
    
    /**
     * Get Files
     * 
     * Thống kê lại danh sách các file cần download
     * 
     * @param array mảng chứa file
     */
    private function get_files($arr)
    {
        if (!is_array($arr)) return FALSE;
        
        foreach ($arr as $k => $f)
        {
            if (is_array($f))
            {
                $this->get_files($f);
            }
            elseif ($f && $k == 'url' && isset($arr['local']))
            {
                $file['url'] = $f;
                $file['local'] = $arr['local'];
                if ($this->in_files($file) == FALSE)
                {
                    $this->files[] = $file;
                }
            }
        }
    }
    
    /**
     * In Files
     * 
     * Kiểm tra xem một file đã có trong $this->files chưa?
     * 
     * @param array file cần kiểm tra
     */
    private function in_files($file)
    {
        foreach ($this->files as $k => $f)
        {
            if ($file == $f) return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Down Load
     * 
     * Down file về máy
     * 
     * @param	string	url file cần down
     * @param	string	thư mục chứa file khi down về
     * @param	bool	replace file cũ hay không?
     */
    public function down($from, $to = '', $replace = FALSE)
    {
        if ($to == '')
        {
            $to = $this->get_info($from);
            $to = $to['url'];
        }
        
        // Lấy url chuẩn của $to
        $arr = split('\?', $to);
        $to = $arr[0];
        
        $local = $this->folder.'/'.$to;
        if ($replace === FALSE && file_exists($local)) return TRUE;
        
        $content = $this->read($from, FALSE);
        if ($content)
        {
            $this->write($to, $content);
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * Read File
     * 
     * Lấy nọi dung của một file
     * 
     * @param string đường dẫn đến file
     * @param string có giải mã code html không?
     */
    private function read($url, $decode = TRUE)
    {
        $content = '';
        
        // Nếu là file từ trang web khác
        if ($this->is_http($url))
        {
            $url = preg_replace('/^https/is', 'http', $url);
			$url = str_replace(' ', '%20', $url);
            $content = $this->cURL->get($url);
            if (!$content || preg_match('/(404 Not Found|The resource cannot be displayed)/is', $content))
            {
                $content = file_get_contents($url);
            }
        }
        // Nếu là file trên máy
        else
        {
            // Lấy url chuẩn của file trên máy
            $arr = split('\?', $url);
            $url = $arr[0];
            
            $url = $this->folder.'/'.$url;
            if (file_exists($url))
            {
                $content = file_get_contents($url);
            }
        }
        
        // Giải mã html
        if ($decode === TRUE)
        {
            $content = htmlspecialchars_decode($content);
        }
        
        return $content;
    }
    
    /**
     * Write File
     * 
     * Ghi nội dung vào một file
     * 
     * @param string url file
     * @param string nội dung file
     */
    private function write($url, $data)
    {
        $arr = split('\?', $url);
        $url = $arr[0];
        $url = $this->folder.'/'.$url;
        $info = $this->get_info($url);
        
        // Tạo thư mục chứa file
        $this->create_dir($info['path']);
        
        // Lưu file
     	$fp = fopen($url, "w");
    	flock($fp, 2);
    	fwrite($fp, $data);
    	flock($fp, 1);
    	fclose($fp);
    }
    
    /**
     * Cache Function
     */
    private function cache_read($url, $decode = TRUE)
    {
        $url = $this->cache.'/'.$url;
        return $this->read($url, $decode);
    }
    
    private function cache_write($url, $data)
    {
        $url = $this->cache.'/'.$url;
        return $this->write($url, $data);
    }
    
    private function cache_down($from, $to = '')
    {
        $to = ($to) ? $this->cache.'/'.$to : $to;
        $this->down($from, $to, FALSE);
    }
    

    /**
     * Create Dir
     * 
     * Tạo thư mục
     * 
 	 * @param	string	tên cây thư mục
     */
    private function create_dir($path)
    {
        if (!$path) return FALSE;
        
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');
        $arr = split('/', $path);
        $dir = '';
        
        foreach ($arr as $folder)
        {
            $dir .= $folder.'/';
            if (!file_exists($dir))
            {
                mkdir($dir);
            }
        }
    }
    
    /**
     * Get Url
     * 
     * Lấy đường dẫn đầy đủ của file
     * 
     * @param	string	url chính
     * @param	string	url liên kết
     */
    private function get_url($base_url, $href)
    {
        $info = $this->get_info($base_url);
        $path = $info['path'];
        $href = str_replace('//', '/', $href);
        
        $arr = split('/', $path);
        $arr_href = split('/', $href);
        
        // Duyệt qua từng folder trong href
        foreach ($arr_href as $folder)
        {
            // Nếu href mở đầu bằng /
            if ($folder == '')
            {
                // Nếu base_url là http
                if ($info['domain'])
                {
                    $url = $info['http'].'://'.$info['domain'].'/'.ltrim($href, '/');
                }
                // Neu base_url la local
                else 
                {
                    $url = $info['path'].'/'.ltrim($href, '/');
                }
                return $url;
            }
            
            // Nếu mở đầu bằng ../
            elseif ($folder == '..')
            {
                // Loại bỏ thư mục cuối cùng trong base_url nếu base url còn tồn tại
                if (count($arr)>=1)
                {
                    array_pop($arr);
                }
                
                // Loại bỏ thư mục đầu tiên của $href
                array_shift($arr_href);
            }
            
            // Nếu mở đầu bằng ./
            elseif ($folder == '.')
            {
                // Loại bỏ thư mục đầu tiên của $href
                array_shift($arr_href);
            }
            
            // Còn không thì dừng vòng lặp
            else
            {
                break;
            }
        }
        
        $domain = ($info['domain']) ? $info['http'].'://'.$info['domain'].'/' : '';
        $path = (count($arr)) ? implode('/', $arr).'/' : '';
        $url = $domain . $path . implode('/', $arr_href);
        return $url;
    }
    
    /**
     * Is http
     * 
     * Kiểm tra liên kết có phải dạng http hay không?
     * 
     * @param string liên kết cần kiểm tra
     */
    private function is_http($url)
    {
        if (preg_match('/^([\w\d]+?):\/\//is', $url)) return TRUE;
        else return FALSE;
    }
    
    /**
     * Get Url Info
     * 
     * Lấy thông tin file từ url
     * 
     * @param	string	url file
     */
    private function get_info($url)
    {
        // Nếu url có dạng http
        if ($this->is_http($url))
        {
            // Thêm kí tự / vào sau url nếu url chỉ là domain (http://domain.ext)
            if (!preg_match('/:\/\/([^\/]+?)\//is', $url))
            {
                $url .= '/';
            }
            
            $match = array();
            preg_match('/^([\w\d]+?):\/\/([^\/]+?)\/(.*?)$/is', $url, $match);
            $info['http'] = $match[1];
            $info['domain'] = $match[2];
            $pathinfo = pathinfo($match[3]);
        }
        // Nếu là liên kết thường
        else 
        {
            $info['http'] = '';
            $info['domain'] = '';
            $pathinfo = pathinfo($url);
        }

        $info['path'] = ($pathinfo['dirname'] != '.') ? $pathinfo['dirname'] : '';
        $info['basename'] = $pathinfo['basename'];
        $info['filename'] = $pathinfo['filename'];
        $info['ext'] = $pathinfo['extension'];
        $info['url'] = ($info['path']) ? $info['path'].'/'.$pathinfo['basename'] : $pathinfo['basename'];
        $info['namelocal'] = ($info['path']) ? str_replace('/', '_', $info['path']).'_' : '';
        $info['namelocal'] .= ($info['filename']) ? $info['filename'].'.html' : 'index.html';
        
        return $info;
    }
    
    /**
     * Replace Link
     * 
     * Thay thế liên kết trong html
     * 
     * @param string liên kết cũ
     * @param string liên kết mới
     * @param string html
     */
    private function replace_link($link_old, $link_new, $html)
    {
        if (strpos($link_old, '\\/'))
        {
            $link_new = str_replace('/', '\\/', $link_new);
        }
        $html = str_replace($link_old, $link_new, $html);
        return $html;
    }
}
     