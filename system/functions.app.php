<?php
/**
 * WeCenter Framework
 *
 * An open source application development framework for PHP 5.2.2 or newer
 *
 * @package		WeCenter Framework
 * @author		WeCenter Dev Team
 * @copyright	Copyright (c) 2011 - 2014, WeCenter, Inc.
 * @license		http://www.wecenter.com/license/
 * @link		http://www.wecenter.com/
 * @since		Version 1.0
 * @filesource
 */

/**
 * WeCenter APP 函数类
 *
 * @package		WeCenter
 * @subpackage	App
 * @category	Libraries
 * @author		WeCenter Dev Team
 */


/**
 * 获取头像地址
 * 举个例子：$uid=12345，那么头像路径很可能(根据您部署的上传文件夹而定)会被存储为/uploads/000/01/23/45_avatar_min.jpg
 *
 * @param  int
 * @param  string
 * @return string
 */
function get_avatar_url($uid, $size = 'min')
{
	$uid = intval($uid);
	if (!$uid)
	{
		return G_STATIC_URL . '/common/avatar-' . $size . '-img.png';
	}
	static $avatar_file_arr;
	foreach (AWS_APP::config()->get('image')->avatar_thumbnail as $key => $val)
	{
	    if(get_hook_info('osd')['state']==1 and get_plugins_config('osd')['base']['status']!='no')
	    {
	    	if($size==$key)
	    	{
	    	    if(!$avatar_file_arr[$uid])
                {
                    $avatar_file_arr[$uid] = AWS_APP::model('account')->fetch_one('users','avatar_file',"uid=$uid");
                }
	    	    $url = $avatar_file_arr[$uid];
                if(strstr($url, 'aliyuncs') && file_get_contents($url))
                {
                    $url=$url.'?x-oss-process=image/resize,m_fixed,h_'.$val['h'].',w_'.$val['w'].'#';
                }
                elseif(strstr($url, 'myqcloud') && file_get_contents($url))
                {
                    $url = $url . '?imageView2/1/w/' . $val['w'] . '/h/' . $val['h'];
                }else{
                    continue;
                }
                return $url;
	    	}
	    }
		$all_size[] = $key;
	}

	$size = in_array($size, $all_size) ? $size : $all_size[0];
	$uid = sprintf("%09d", $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);

	if (file_exists(get_setting('upload_dir') . '/avatar/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . '_avatar_' . $size . '.jpg'))
	{
		return get_setting('upload_url') . '/avatar/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . '_avatar_' . $size . '.jpg?'.rand(1, 999);
	}
	else
	{
		if($info = AWS_APP::model('account')->fetch_row('users_weixin','uid='.$uid))
		{
		    if(http_type()=='https://')
            {
                $info['headimgurl'] = str_replace('http://','https://',$info['headimgurl']);
            }
			return $info['headimgurl'];
		}
		if($info=AWS_APP::model('account')->fetch_row('users_qq','uid='.$uid))
		{
            if(http_type()=='https://')
            {
                $info['figureurl'] = str_replace('http://','https://',$info['figureurl']);
            }
		    return $info['figureurl'];
		}
		return G_STATIC_URL . '/common/avatar-' . $size . '-img.png';
	}
}

function http_type()
{
	$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
	return $http_type;
}
/**
 * 附件url地址，实际上是通过一定格式编码指配到/app/file/main.php中，让download控制器处理并发送下载请求
 * @param  string $file_name 附件的真实文件名，即上传之前的文件名称，包含后缀
 * @param  string $url 附件完整的真实url地址
 * @return string 附件下载的完整url地址
 */
function download_url($file_name, $url)
{
	return get_js_url('/file/download/file_name-' . base64_encode($file_name) . '__url-' . base64_encode($url));
}
//自定义xml验证函数xml_parser()
function xml_parser($str){
    $xml_parser = xml_parser_create();
    if(!xml_parse($xml_parser,$str,true)){
      xml_parser_free($xml_parser);
      return false;
    }else {
      return (json_decode(json_encode(simplexml_load_string($str)),true));
    }
}
// 检测当前操作是否需要验证码
function human_valid($permission_tag)
{
	if (! is_array(AWS_APP::session()->human_valid))
	{
		return FALSE;
	}

	if (! AWS_APP::session()->human_valid[$permission_tag] or ! AWS_APP::session()->permission[$permission_tag])
	{
		return FALSE;
	}

	foreach (AWS_APP::session()->human_valid[$permission_tag] as $time => $val)
	{
		if (date('H', $time) != date('H', time()))
		{
			unset(AWS_APP::session()->human_valid[$permission_tag][$time]);
		}
	}

	if (sizeof(AWS_APP::session()->human_valid[$permission_tag]) >= AWS_APP::session()->permission[$permission_tag])
	{
		return TRUE;
	}
	return FALSE;
}

/**
 * 插件执行方法
 * @param string $plugin 插件名称
 * @param string $method 插件方法
 * @param array $params 插件参数
 * @return mixed
 * @throws \Zend_Exception
 */
function hook($plugin,$method,$params = [])
{
	$plugin_class=new AWS_PLUGIN();
    return $plugin_class::trigger($plugin,$method,$params);
}

/**
 * 钩子执行方法
 * @param string $name 钩子名称
 * @param array $params 执行参数
 * @return array|mixed
 * @throws \Zend_Exception
 */
function run_hook($name = '', $params = [])
{
    $plugin_class = new AWS_PLUGIN();
    return $plugin_class::listen($name, $params);
}

/**
 * 获取插件地址
 * @param string $plugins 插件名称
 * @param string $method 插件方法
 * @param array $param 插件参数
 * @param string $scene  auto 自动识别，index前台插件地址，admin，后台插件地址
 * @return string
 */
function get_plugins_url($plugins,$method,$param=[],$scene = 'auto')
{
    $param_url = array();
    $param_url[] = 'p-'.$plugins;
    $param_url[] = 'a-'.$method;
    foreach ($param as $k=>$v)
    {
        $param_url[] = $k.'-'.$v;
    }

    switch ($scene)
    {
        case 'index':
            return get_js_url('/plugins/'.implode('__',$param_url));
            break;
        case 'admin':
            return get_js_url('/admin/plugin/doact/'.implode('__',$param_url));
            break;
        case 'auto':
            if($_GET['app']!='admin')
            {
                return get_js_url('/plugins/'.implode('__',$param_url));
            }else{
                return get_js_url('/admin/plugin/doact/'.implode('__',$param_url));
            }
            break;
    }
}

function recurse_copy($src,$dst)
{
    // 原目录，复制到的目录
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

/**
 * 获取插件文件路径
 * @param $plugin_name
 * @return bool|string
 */
function get_plugins_class_file($plugin_name)
{
    $class_file=ADDON_PATH .'wc_'.$plugin_name.'/'.$plugin_name.'.php';
    if (@file_exists($class_file))
    {
        return $class_file;
    }else{
        return false;
    }
}

/**
 * 获取插件配置完整配置
 * @param $plugin
 * @return mixed
 */
function get_hook_config($plugin)
{
    $info = get_hook_info($plugin);
    return $info['config'];
}

/**
 * 获取插件配置只包含插件配置键值对
 * @param $plugin
 * @return array
 */
function get_plugins_config($plugin)
{
    $info = get_hook_info($plugin);
    $config = array();
    if($info['config']['group'])
    {
        foreach ($info['config']['group'] as $key => $value)
        {
            foreach ($value['config'] as $k1=>$v1)
            {
                $config[$key][$k1] = $v1['value'];
            }
        }
    }else{
        foreach ($info['config'] as $key => $value)
        {
            $config[$key] = $value['value'];
        }
    }
    return $config;
}

/**
 * 获取插件信息
 * @param $plugin
 * @return mixed
 */
function get_hook_info($plugin)
{
    static $pluginInfos;
    if (!$pluginInfos)
    {
        $pluginInfos = AWS_APP::model('plugin')->getList(true);
        if (!empty($pluginInfos))
        {
            foreach ($pluginInfos as $key => $val) {
                $pluginInfos[$key]['config'] = json_decode($val['config'], true);
            }

            $pluginInfos = array_column($pluginInfos, null, "name");
        }
        $pluginInfos['fetched'] = true;
    }
    return $pluginInfos[$plugin];
}

/**
 * @param $q
 * @param string $string  要分割的字符串
 * @param int $length  指定的长度
 * @param String $end  在分割后的字符串块追加的内容
 * @param bool $once
 * @return string
 */
function mb_chunk_split($q,$string, $length, $end='\r\n', $once = false)
{
    $array = array(); 
    $str_len = mb_strlen($string);
    $b=0;
    while($str_len){
        $array[] = mb_substr($string, 0, $length, "utf-8"); 
        if($once) 
            return $array[0] . $end; 
        $string = mb_substr($string, $length, $str_len, "utf-8");
        $str_len = mb_strlen($string);
    } 
	 foreach ($array as $key => $value) {
	 	if(stripos($value,$q)){
	 		$b=$key;
	 	    break;
	 	}
	 }
	 $data=array_slice($array,$b,3);
	 return count($data)>1?implode(" ", $data).'...':implode(" ", $data);
}

/**
 * @param $str
 * @param int $start
 * @param $length
 * @param string $charset
 * @param string $suffix
 * @return string
 */
function substr_text($str, $start=0, $length, $charset="utf-8", $suffix="...")
{
    if(function_exists("mb_substr"))
    {
        return mb_substr($str, $start, $length, $charset).$suffix;
    }elseif(function_exists('iconv_substr'))
    {
        return iconv_substr($str,$start,$length,$charset).$suffix;
    }
    $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    return $slice.$suffix;
}

function set_human_valid($permission_tag)
{
	if (! is_array(AWS_APP::session()->human_valid))
	{
		return FALSE;
	}
	AWS_APP::session()->human_valid[$permission_tag][time()] = TRUE;
	return count(AWS_APP::session()->human_valid[$permission_tag]);
}

/**
 * 根据用户ID获取用户名
 * @param int $uid 用户id
 * @return mixed
 */
function get_user_name_by_uid($uid)
{
	return AWS_APP::model('account')->get_name($uid);
}

/**
 * 仅附件处理中的preg_replace_callback()的每次搜索时的回调
 * @param  array $matches preg_replace_callback()搜索时返回给第二参数的结果
 * @return string  取出附件的加载模板字符串
 */
function parse_attachs_callback($matches)
{
	if ($attach = AWS_APP::model('publish')->get_attach_by_id($matches[1]))
	{
		TPL::assign('attach', $attach);

		return TPL::output('question/ajax/load_attach', false);
	}
}

function parse_imgs_callback($matches)
{
	if ($matches[1])
	{
		TPL::assign('attach', $matches[1]);
		return TPL::output('question/ajax/load_img', false);
	}
}

/**
 * 获取话题图片指定尺寸的完整url地址
 * @param  mixed $size
 * @param  mixed $pic_file 某一尺寸的图片文件名
 * @return string|bool           取出主题图片或主题默认图片的完整url地址
 * @throws \Zend_Exception
 */
function get_topic_pic_url($size = null, $pic_file = null)
{
    if(get_hook_info('osd')['state']==1 and $status=get_hook_config('osd')['group']['base']['config']['status']['value']!='no')
    {
        if(strstr($pic_file, 'aliyuncs') and file_exists($pic_file))
        {
            $url=$pic_file.'?x-oss-process=image/resize,m_fixed,h_'.AWS_APP::config()->get('image')->topic_thumbnail[$size]['h'].',w_'.AWS_APP::config()->get('image')->topic_thumbnail[$size]['w'].'#';
            return $url;
        }
        if(strstr($pic_file, 'myqcloud') and file_exists($pic_file))
        {
            $url=$pic_file.'?imageView2/1/w/'.AWS_APP::config()->get('image')->topic_thumbnail[$size]['w'].'/h/'.AWS_APP::config()->get('image')->topic_thumbnail[$size]['h'];
            return $url;
        }
    }

	if ($sized_file = AWS_APP::model('topic')->get_sized_file($size, $pic_file)  and file_exists(get_setting('upload_dir') . '/topic/' . $sized_file))
	{
		return get_setting('upload_url') . '/topic/' . $sized_file;
	}

	if (! $size)
	{
		return G_STATIC_URL . '/common/topic-max-img.png';
	}

	return G_STATIC_URL . '/common/topic-' . $size . '-img.png';
}

/**
 * 获取专题图片指定尺寸的完整url地址
 * @param mixed $size 三种图片尺寸 max(100px)|mid(50px)|min(32px)
 * @param null $pic_file 某一尺寸的图片文件名
 * @return bool|string 取出专题图片的完整url地址
 * @throws \Zend_Exception
 */
function get_feature_pic_url($size = null, $pic_file = null)
{
	if (! $pic_file)
	{
		return false;
	}
	else
	{
		if ($size)
		{
			$pic_file = str_replace(AWS_APP::config()->get('image')->feature_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->feature_thumbnail['min']['h'], AWS_APP::config()->get('image')->feature_thumbnail[$size]['w'] . '_' . AWS_APP::config()->get('image')->feature_thumbnail[$size]['h'], $pic_file);
		}
	}

	return get_setting('upload_url') . '/feature/' . $pic_file;
}

/**
 * 获取顶级域名
 * @return mixed|string
 */
function get_host_top_domain()
{
	$host = strtolower($_SERVER['HTTP_HOST']);
	if (strpos($host, '/') !== false)
	{
		$parse = @parse_url($host);
		$host = $parse['host'];
	}
	$top_level_domain_db = array('com', 'edu', 'gov', 'int', 'mil', 'net', 'org', 'biz', 'info', 'pro', 'name', 'coop', 'aero', 'xxx', 'idv', 'mobi', 'cc', 'me', 'jp', 'uk', 'ws', 'eu', 'pw', 'kr', 'io', 'us', 'cn');
	foreach ($top_level_domain_db as $v)
	{
		$str .= ($str ? '|' : '') . $v;
	}
	$matchstr = "[^\.]+\.(?:(" . $str . ")|\w{2}|((" . $str . ")\.\w{2}))$";
	if (preg_match('/' . $matchstr . '/ies', $host, $matchs))
	{
		$domain = $matchs['0'];
	}else
	{
		$domain = $host;
	}
	return $domain;
}

function parse_link_callback($matches)
{
	if (preg_match('/^(?!http).*/i', $matches[1]))
	{
		$url = 'http://' . $matches[1];
	}
	else
	{
		$url = $matches[1];
	}

	if (stristr($url, 'http://%'))
	{
		return false;
	}

	if (stristr($url, 'http://&'))
	{
		return false;
	}

	if (!preg_match('#^(http|https)://(?:[^<>\"]+|[a-z0-9/\._\- !&\#;,%\+\?:=]+)$#iU', $url))
	{
		return false;
	}

	if (is_inside_url($url))
	{
		return '<a href="' . $url . '">' . FORMAT::sub_url($matches[1], 50) . '</a>';
	}
	else
	{
		return '<a href="' . $url . '" rel="nofollow" target="_blank">' . FORMAT::sub_url($matches[1], 50) . '</a>';
	}
}

function parse_link_callback_bbcode($matches)
{
	if (preg_match('/^(?!http).*/i', $matches[1]))
	{
		$url = 'http://' . $matches[1];
	}
	else
	{
		$url = $matches[1];
	}

	if (stristr($url, 'http://%'))
	{
		return false;
	}

	if (stristr($url, 'http://&'))
	{
		return false;
	}

	if (!preg_match('#^(http|https)://(?:[^<>\"]+|[a-z0-9/\._\- !&\#;,%\+\?:=]+)$#iU', $url))
	{
		return false;
	}

	return '[url=' . $url . ']' . FORMAT::sub_url($matches[1], 50) . '[/url]';
}

function is_inside_url($url)
{
	if (!$url)
	{
		return false;
	}

	if (preg_match('/^(?!http).*/i', $url))
	{
		$url = 'http://' . $url;
	}

	$domain = get_host_top_domain();

	if (preg_match('/^http[s]?:\/\/([-_a-zA-Z0-9]+[\.])*?' . $domain . '(?!\.)[-a-zA-Z0-9@:;%_\+.~#?&\/\/=]*$/i', $url))
	{
		return true;
	}
	return false;
}

function get_weixin_rule_image($image_file, $size = '')
{
	return AWS_APP::model('weixin')->get_weixin_rule_image($image_file, $size);
}

function import_editor_static_files()
{
	TPL::import_js('js/editor/ckeditor/ckeditor.js');
	TPL::import_js('js/editor/ckeditor/adapters/jquery.js');
}

function get_chapter_icon_url($id, $size = 'max', $default = true)
{
	if (file_exists(get_setting('upload_dir') . '/chapter/' . $id . '-' . $size . '.jpg'))
	{
		return get_setting('upload_url') . '/chapter/' . $id . '-' . $size . '.jpg';
	}
	else if ($default)
	{
		return G_STATIC_URL . '/common/help-chapter-' . $size . '-img.png';
	}

	return false;
}

function base64_url_encode($parm)
{
	if (!is_array($parm))
	{
		return false;
	}

	return strtr(base64_encode(json_encode($parm)), '+/=', '-_,');
}

function base64_url_decode($parm)
{
	return json_decode(base64_decode(strtr($parm, '-_,', '+/=')), true);
}

function remove_assoc($from, $type, $id)
{
	if (!$from OR !$type OR !is_digits($id))
	{
		return false;
	}

	return $this->query('UPDATE ' . $this->get_table($from) . ' SET `' . $type . '_id` = NULL WHERE `' . $type . '_id` = ' . $id);
}

/**
 * 字符串截取，支持中文和其他编码（可去除HTML标签之后再截取）
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param int $start 开始位置
 * @param int $length 截取长度
 * @param string $charset 编码格式
 * @param bool $suffix 截断显示字符
 * @param bool $strip_tags 是否去除HTML标签
 * @return string
 */
function chinese_msubstr($str, $start=0, $length=20, $charset="utf-8", $suffix=true,$strip_tags=true) {
    if($strip_tags)
    {
        $str = strip_tags($str);
    }
    if(function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        $slice = iconv_substr($str,$start,$length,$charset);
        if(false === $slice) {
            $slice = '';
        }
    }else{
        $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
 
    if($suffix==true)
    {
        if(function_exists('mb_strlen'))
        {
            if(mb_strlen($str,$charset)>$length)
                $slice = $slice.'...';
        }
        else
        {$slice = 'mb_strlen未安装！';}
    }
    return  $slice;
}

function get_topic_btn ($cate, $name = 'children', $pid = 0) {
    $arr = array();
    foreach ($cate as $v) {
        if ($v['parent_id'] == $pid) {
            $v[$name] = get_topic_btn($cate, $name, $v['topic_id']);
            $arr[] = $v;
        }
    }
    return $arr;
}

/**
 * 替换内容中的图片 添加域名
 * @param  string $content 要替换的内容
 * @param  string $strUrl 内容中图片要加的域名
 * @param int $osd
 * @return string|string[]|null
 */
function replacePicUrl($content = null, $strUrl = null,$osd=0)
{
    if ($strUrl) {  
        //提取图片路径的src的正则表达式 并把结果存入$matches中    
        preg_match_all("/<img(.*)src=\"([^\"]+)\"[^>]+>/isU",$content,$matches);  
        $img = "";    
        if(!empty($matches)) {    
        //注意，上面的正则表达式说明src的值是放在数组的第三个中    
        $img = $matches[2];    
        }else {    
           $img = "";    
        }  
          if (!empty($img)) {    
                $patterns= array();    
                $replacements = array();    
                foreach($img as $imgItem){    
                    $final_imgUrl = $strUrl.$imgItem;    
                    // $final_imgUrl=AWS_APP::model('api')->check_img($final_imgUrl);
                    if($osd==1){
	                	if(strstr($imgItem, 'files_user')){
	                    	$replacements[] = $final_imgUrl;    
	                	}else{
	                    	$replacements[] = $imgItem;    
	                	}
                    		$img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";    
		                    $patterns[] = $img_new;   
                    }elseif($osd==2){
                    		if(strstr($imgItem, 'aliyuncs.com')){
                    			// $imgItem=preg_replace("http:\/\/[^\/]*",'',$imgItem);
                    			$_imgItem=parse_url($imgItem)['path'];
	                    		$replacements[] = $_imgItem;    
		                    	$img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";    
		                    	$patterns[] = $img_new;    

                    		}
                    }else{
	                    	$replacements[] = $final_imgUrl;    
		                    $img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";    
		                    $patterns[] = $img_new;    

                    }
                }    
                //让数组按照key来排序    
                ksort($patterns);    
                ksort($replacements);
                //替换内容    
                $vote_content = preg_replace($patterns, $replacements, $content);
                return $vote_content;  
        }else {  
            return $content;  
        }                     
    } else {  
        return $content;  
    }  
}

/**
 * 替换内容中的视频 添加域名
 * @param null $content
 * @param null $strUrl
 * @param bool $osd
 * @return string|string[]|null
 */
function replaceVideoUrl($content = null, $strUrl = null,$osd=false)
{
    if ($strUrl) {  
        //提取图片路径的src的正则表达式 并把结果存入$matches中    
        preg_match_all('/<video[^>]*src=[\'"]?([^>\'"\s]*)[\'"]?[^>]*>/i',$content,$matches);  
        $img = "";    
        if(!empty($matches)) {    
        //注意，上面的正则表达式说明src的值是放在数组的第三个中    
        $img = $matches[1];    
        }else {    
           $img = "";    
        }  
          if (!empty($img)) {    
                $patterns= array();    
                $replacements = array();    
                foreach($img as $imgItem){    
                    $final_imgUrl = $strUrl.$imgItem;
                    if($osd==1){
	                	if(strstr($imgItem, 'files_user')){
	                    	$replacements[] = $final_imgUrl;    
	                	}else{
	                    	$replacements[] = $imgItem;    
	                	}
                        $img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";
                        $patterns[] = $img_new;
                    }elseif($osd==2){
                        if(strstr($imgItem, 'aliyuncs.com')){
                            $_imgItem=parse_url($imgItem)['path'];
                            $replacements[] = $_imgItem;
                            $img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";
                            $patterns[] = $img_new;
                        }
                    }else{
                        $replacements[] = $final_imgUrl;
                        $img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";
                        $patterns[] = $img_new;
                    }
                }
                //让数组按照key来排序    
                ksort($patterns);    
                ksort($replacements);
                //替换内容    
                $vote_content = preg_replace($patterns, $replacements, $content);
                return $vote_content;  
        }else {  
            return $content;  
        }                     
    } else {  
        return $content;  
    }  
}

/**
 * 替换内容中的文件 添加域名
 * @param null $content
 * @param null $strUrl
 * @param bool $osd
 * @return string|string[]|null
 */
function replaceFileUrl($content = null, $strUrl = null,$osd=false) {  
    if ($strUrl) {  
        //提取图片路径的src的正则表达式 并把结果存入$matches中    
        preg_match_all('/<a[^>]*href=[\'"]?([^>\'"\s]*)[\'"]?[^>]*>/i',$content,$matches);  
        $img = "";    
        if(!empty($matches)) {    
        //注意，上面的正则表达式说明src的值是放在数组的第三个中    
        $img = $matches[1];    
        }else {    
           $img = "";    
        }  
          if (!empty($img)) {    
                $patterns= array();    
                $replacements = array();    
                foreach($img as $imgItem){    
                    $final_imgUrl = $strUrl.$imgItem;    
                    // $final_imgUrl=AWS_APP::model('api')->check_img($final_imgUrl);
                    if($osd==1){
	                	if(strstr($imgItem, 'files_user')){
	                    	$replacements[] = $final_imgUrl;    
	                	}else{
	                    	$replacements[] = $imgItem;    
	                	}
                    		$img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";    
		                    $patterns[] = $img_new;   
                    }elseif($osd==2){
                    		if(strstr($imgItem, 'aliyuncs.com')){
                    			// $imgItem=preg_replace("http:\/\/[^\/]*",'',$imgItem);
                    			$_imgItem=parse_url($imgItem)['path'];
	                    		$replacements[] = $_imgItem;    
		                    	$img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";    
		                    	$patterns[] = $img_new;
                    		}
                    }else{
                        $replacements[] = $final_imgUrl;
                        $img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";
                        $patterns[] = $img_new;
                    }
                }
                //让数组按照key来排序    
                ksort($patterns);    
                ksort($replacements);
                //替换内容    
                $vote_content = preg_replace($patterns, $replacements, $content);
                return $vote_content;  
        }else {  
            return $content;  
        }                     
    } else {  
        return $content;  
    }
}

/*解析微信模板消息模板内容*/
function parse_wx_template_msg($data='',$title='',$content='',$time='',$answer_content='')
{
    return str_replace(['$title','$content','$time','$answer_content'],[$title,$content,$time,$answer_content],$data);
}

/**
 * 移除内容中的非安全字符
 * @param $html
 * @param bool $remove 是否移除
 * @return mixed
 */
function remove_xss($html,$remove=false)
{
    //自动提取内容中的网址为a标签
    if(get_setting('enable_auto_link')=='Y'){
        $html= FORMAT::parse_auto_link($html);
    }
    if(get_setting('enable_remove_xss')=='Y' || $remove)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('AutoFormat.RemoveEmpty', true);
        //可自定义过滤规则，适合二次开发使用
        $purifier = new HTMLPurifier($config);
        $html = $purifier->purify($html);
    }
    return $html;
}

//删除指定文件夹以及文件夹下的所有文件
function deldir($dir) {
    //先删除目录下的文件：
    $dh = opendir($dir);
    while ($file = readdir($dh)) 
    {
        if($file != "." && $file != "..") 
        {
            $fullpath = $dir."/".$file;

            if(!is_dir($fullpath)) 
            {
                unlink($fullpath);
            } else {
                deldir($fullpath);
            }
        }
    }
    closedir($dh);
    //删除当前文件夹：
    if(rmdir($dir)) 
    {
        return true;
    } else 
    {
        return false;
    }
}

/**
 * 生成支付订单号
 * @param  integer
 * @return string
 */
function fetch_order()
{
    $order = 'DH'.date('YmdHis').time().rand(10000,99999);
    return $order;
}

/**
 * 过滤前后空格等多种字符
 * @param string $str 字符串
 * @param array $arr 特殊字符的数组集合
 * @return string
 */
function trim_space($str, $arr = array())
{
    if (empty($arr)) {
        $arr = array(' ', '　');
    }
    foreach ($arr as $key => $val) {
        $str = preg_replace('/(^'.$val.')|('.$val.'$)/', '', $str);
    }
    return $str;
}

/**
 * 过滤Html标签
 * @param     string  $string  内容
 * @return    string
 */
function checkStrHtml($string)
{
    $string = trim_space($string);
    if(is_numeric($string)) return $string;
    if(!isset($string) or empty($string)) return '';
    $string = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/','',$string);
    $string  = ($string);
    $string = str_replace("<br>", "&nbsp;", $string);
    $string = strip_tags($string,""); //清除HTML如<br />等代码
    // $string = str_replace("\n", "", str_replace(" ", "", $string));//去掉空格和换行
    $string = str_replace("\n", "", $string);//去掉空格和换行
    $string = str_replace("\t","",$string); //去掉制表符号
    $string = str_replace(PHP_EOL,"",$string); //去掉回车换行符号
    $string = str_replace("\r","",$string); //去掉回车
    $string = str_replace("'","‘",$string); //替换单引号
    $string = str_replace("&amp;","&",$string);
    $string = str_replace("=★","",$string);
    $string = str_replace("★=","",$string);
    $string = str_replace("★","",$string);
    $string = str_replace("☆","",$string);
    $string = str_replace("√","",$string);
    $string = str_replace("±","",$string);
    $string = str_replace("‖","",$string);
    $string = str_replace("×","",$string);
    $string = str_replace("∏","",$string);
    $string = str_replace("∷","",$string);
    $string = str_replace("⊥","",$string);
    $string = str_replace("∠","",$string);
    $string = str_replace("⊙","",$string);
    $string = str_replace("≈","",$string);
    $string = str_replace("≤","",$string);
    $string = str_replace("≥","",$string);
    $string = str_replace("∞","",$string);
    $string = str_replace("∵","",$string);
    $string = str_replace("♂","",$string);
    $string = str_replace("♀","",$string);
    $string = str_replace("°","",$string);
    $string = str_replace("¤","",$string);
    $string = str_replace("◎","",$string);
    $string = str_replace("◇","",$string);
    $string = str_replace("◆","",$string);
    $string = str_replace("→","",$string);
    $string = str_replace("←","",$string);
    $string = str_replace("↑","",$string);
    $string = str_replace("↓","",$string);
    $string = str_replace("▲","",$string);
    $string = str_replace("▼","",$string);
    $string = preg_replace('/<\s+img[^>]+>/i', '', $string);
    // 过滤微信表情
    $string = preg_replace_callback('/[\xf0-\xf7].{3}/', function($r) { return '';}, $string);
    return $string;
}

/**
 * curl请求函数
 * @param $url
 * @param null $data
 * @return bool|string
 */
function curl_request($url,$data = null)
{
    $curl = curl_init();    // curl 设置
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);  // 校验证书节点
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);// 校验证书主机
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0); //强制协议为1.0
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect: ')); //头部要送出'Expect: '
    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 ); //强制使用IPV4协议解析域名
    if ( !empty($data) )
    {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 以文件流的形式 把参数返回进来
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}

/**
 * 移除超链接
 * @param $str
 * @return string|string[]|null
 */
function removeLinks($str)
{
    if(empty($str))return    '';
    $str    =preg_replace('/(http)(.)*([a-z0-9\-\.\_])+/i','',$str);
    $str    =preg_replace('/(www)(.)*([a-z0-9\-\.\_])+/i','',$str);
    return    $str;
}

/**
 * 获取替换文章中的图片路径
 * @param $content
 * @param $type
 * @param bool $replace
 * @return mixed
 */
function replace_image($content,$type,$replace=false)
{
    if(get_setting('down_img_to_location_enable')=='N' || !$replace)
    {
        return $content;
    }

    $upload_dir = get_setting('upload_dir').'/'.$type.'/'.date('Ymd', time()).'/';
    if(!is_dir($upload_dir))
    {
        @mkdir($upload_dir, 0777);
    }
    //匹配图片的src
    preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i',$content, $match);
    foreach($match[1] as $img_url)
    {
        if((strstr($img_url, 'http') || strstr($img_url, 'https')) && !strstr($img_url, base_url()))
        {
            $location_file_path = sync_img($img_url, $type);
            $content=str_replace($img_url,$location_file_path,$content);
        }
    }
    return $content;
}

/*远程下载图片*/
function sync_img($url, $type)
{
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $file = curl_exec($ch);
        curl_close($ch);
        $ext = '.'.end(explode(".",$url));
        AWS_APP::upload()->initialize(array(
            'allowed_types' => '*',
            'upload_path' => get_setting('upload_dir') . '/'.$type.'/' .  gmdate('Ymd'),
            'is_image' => TRUE,
            'max_size' => 0,
        ))->do_upload($ext,$file);
        $upload_data = AWS_APP::upload()->data();
        $image = '/uploads/'.$type.'/' .  gmdate('Ymd').'/'.$upload_data['file_name'];
        return $image;
    } catch (Exception $e) {
    }
}