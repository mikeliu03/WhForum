<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/
class PLUTPL
{
	public static $template_ext = '.tpl.htm';
	public static $view;
	public static $output_matchs;
	public static $template_path;
	public static $in_app = false;
	public static function initialize()
	{
		if (!is_object(self::$view))
		{
			self::$template_path = realpath(ADDON_PATH . '/');
			self::$view = new Savant3(
				array('template_path' => array(self::$template_path),)
			);
			if (file_exists(AWS_PATH . 'config.inc.php') AND class_exists('AWS_APP', false))
			{
				self::$in_app = true;
			}
		}
		return self::$view;
	}

    /**
     * @param $name
     * @param $value
     */
	public static function assign($name, $value)
    {
		self::$view->$name = $value;
	}

    /**
     * @param $template_filename
     * @param bool $display
     * @return string|string[]|null
     * @throws \Zend_Exception
     */
	public static function output($template_filename, $display = true)
    {
        $names = explode('/', $template_filename);
        $names[0]='wc_'.$names[0];
        array_splice($names,1,0,['view']);
        $template_filename=implode('/', $names);
		if (!strstr($template_filename, self::$template_ext)){
			$template_filename .= self::$template_ext;
		}
		$output = self::$view->getOutput($template_filename);
		if (self::$in_app AND basename($template_filename) != 'debuger.tpl.htm')
		{
			$template_dirs = explode('/', $template_filename);

			if (get_setting('url_rewrite_enable') != 'Y' OR $template_dirs[0] == 'admin')
			{
				$output = preg_replace('/<([^>]*?)(href|action)=([\"|\'])(?!http)(?!mailto)(?!file)(?!ftp)(?!javascript)(?![\/|\#])(?!\.\/)([^\"\']+)([\"|\'])([^>]*?)>/is', '<\1\2=\3' . base_url() . '/' . G_INDEX_SCRIPT . '\4\5\6>', $output);
			}

            if ($request_routes = get_request_route() AND $template_dirs[0] != 'admin' AND get_setting('url_rewrite_enable') == 'Y')
            {
                foreach ($request_routes as $key => $val)
                {
                    $output = preg_replace("/href=[\"|']" . $val[0] . "[\#]/", "href=\"" . $val[1] . "#", $output);
                    $output = preg_replace("/href=[\"|']" . $val[0] . "[\"|']/", "href=\"" . $val[1] . "\"", $output);
                }
            }

            if (get_setting('url_rewrite_enable') == 'Y' AND $template_dirs[0] != 'admin')
            {
                $output = preg_replace('/<([^>]*?)(href|action)=([\"|\'])(?!mailto)(?!file)(?!ftp)(?!https)(?!http)(?!javascript)(?![\/|\#])(?!\.\/)([^\"\']{0,})([\"|\'])([^>]*?)>/is', '<\1\2=\3' . base_url() . '/' . '\4\5\6>', $output);
            }
            $output = preg_replace('/[a-zA-Z0-9]+_?[a-zA-Z0-9]*\-__/', '', $output);
            $output = preg_replace('/(__)?[a-zA-Z0-9]+_?[a-zA-Z0-9]*\-([\'|"])/', '\2', $output);
			if (AWS_APP::config()->get('system')->debug)
			{
				$output .= "\r\n<!-- Template End: " . $template_filename . " -->\r\n";
			}
		}
		if ($display){
			echo $output;
			flush();
		}else{
			return $output;
		}
	}

    public static function set_meta($tag, $value)
    {
        self::assign('_meta_' . $tag, $value);
    }

    public static function val($name)
    {
        return self::$view->$name;
    }

    public static function import_css($path,$plugins)
    {
        $plugins = strstr($plugins,'wc_') ? $plugins : 'wc_'.$plugins;
        if (is_array($path))
        {
            foreach ($path AS $key => $val)
            {
                $val = $plugins.'/static/'.$val;
                if (substr($val, 0, 4) != 'http')
                {
                    $val = base_url() . '/plugins/' . $val;
                }

                self::$view->_import_css_files[] = $val;
            }
        }
        else
        {
            if (substr($path, 0, 4) != 'http')
            {
                $path =base_url().'/plugins/'.$plugins.'/static/'.$path;
            }

            self::$view->_import_css_files[] = $path;
        }
    }

    public static function import_js($path,$plugins)
    {
        $plugins = strstr($plugins,'wc_') ? $plugins : 'wc_'.$plugins;
        if (is_array($path))
        {
            foreach ($path AS $key => $val)
            {
                if (substr($val, 0, 4) != 'http')
                {
                    $val = base_url() . '/plugins/' . $val;
                }

                self::$view->_import_js_files[] = $val;
            }
        }
        else
        {
            if (substr($path, 0, 4) != 'http')
            {
                $path =base_url().'/plugins/'.$plugins.'/static/'.$path;
            }

            self::$view->_import_js_files[] = $path;
        }
    }

    public static function import_clean($type = false)
    {
        if ($type == 'js' OR !$type)
        {
            self::$view->_import_js_files = null;
        }

        if ($type == 'css' OR !$type)
        {
            self::$view->_import_css_files = null;
        }
    }
}