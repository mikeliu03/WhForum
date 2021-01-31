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

class HTTP
{
    public static $api_url = 'https://wenda.wecenter.com/';
    public static $version_check_url = 'cloud/ajax/check_version/';
    public static $get_upgrade_info_url = 'cloud/ajax/get_upgrade_info/';
    public static $upgrade_url = 'cloud/ajax/download_upgrade_file/';
    public static $check_auth_url = 'cloud/ajax/check_auth_type/';
    public static $unbind_url = 'cloud/ajax/unbind_user/';
    public static $update_download_status_url = 'cloud/ajax/update_download_status/';

    protected static $userAgent = [
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36 OPR/26.0.1656.60',
        'Opera/8.0 (Windows NT 5.1; U; en)',
        'Mozilla/5.0 (Windows NT 5.1; U; en; rv:1.8.1) Gecko/20061208 Firefox/2.0.0 Opera 9.50',
        'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 9.50',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0',
        'Mozilla/5.0 (X11; U; Linux x86_64; zh-CN; rv:1.9.2.10) Gecko/20100922 Ubuntu/10.10 (maverick) Firefox/3.6.10',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11',
        'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.133 Safari/534.16',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.101 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.11 TaoBrowser/2.0 Safari/536.11',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.71 Safari/537.1 LBBROWSER',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E; LBBROWSER)',
        'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E; LBBROWSER)',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E; QQBrowser/7.0.3698.400)',
        'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E)',
        'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.84 Safari/535.11 SE 2.X MetaSr 1.0',
        'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SV1; QQDownload 732; .NET4.0C; .NET4.0E; SE 2.X MetaSr 1.0)',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Maxthon/4.4.3.4000 Chrome/30.0.1599.101 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.122 UBrowser/4.0.3214.0 Safari/537.36',
    ];

    /**
     * CURL发送Request请求,支持GET、POST、PUT、DELETE
     * @param string $url 请求的地址
     * @param mixed $params 传递的参数
     * @param string $method 请求的方法
     * @param array $header 传递的头部参数
     * @param int $timeout 超时设置，默认30秒
     * @param mixed $options CURL的参数
     * @return array|string
     */
    private static function send($url, $params = '', $method = 'GET', $header = [], $timeout = 30, $options = [])
    {
        $userAgent = self::$userAgent[array_rand(self::$userAgent, 1)];
        $ch = curl_init();
        $opt                            = [];
        $opt[CURLOPT_USERAGENT]         = $userAgent;
        $opt[CURLOPT_CONNECTTIMEOUT]    = $timeout;
        $opt[CURLOPT_TIMEOUT]           = $timeout;
        $opt[CURLOPT_RETURNTRANSFER]    = true;
        $opt[CURLOPT_HTTPHEADER]        = $header ? : ['Expect:'];
        $opt[CURLOPT_FOLLOWLOCATION]    = true;
        $opt[CURLOPT_REFERER] = $options['referer'];
        if (substr($url, 0, 8) == 'https://') {
            $opt[CURLOPT_SSL_VERIFYPEER] = false;
            $opt[CURLOPT_SSL_VERIFYHOST] = 2;
        }

        if (is_array($params)) {
            $params = http_build_query($params);
        }

        switch (strtoupper($method)) {
            case 'GET':
                $extStr             = (strpos($url, '?') !== false) ? '&' : '?';
                $opt[CURLOPT_URL]   = $url . (($params) ? $extStr . $params : '');
                break;

            case 'POST':
                $opt[CURLOPT_POST]          = true;
                $opt[CURLOPT_POSTFIELDS]    = $params;
                $opt[CURLOPT_URL]           = $url;
                break;

            case 'PUT':
                $opt[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $opt[CURLOPT_POSTFIELDS]    = $params;
                $opt[CURLOPT_URL]           = $url;
                break;

            case 'DELETE':
                $opt[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                $opt[CURLOPT_POSTFIELDS]    = $params;
                $opt[CURLOPT_URL]           = $url;
                break;

            default:
                return ['error' => 0, 'msg' => '请求的方法不存在', 'info' => []];
                break;
        }

        curl_setopt_array($ch, (array) $opt + $options);
        $result = curl_exec($ch);
        $error  = curl_error($ch);

        if ($result == false || !empty($error))
        {
            $errno  = curl_errno($ch);
            $info   = curl_getinfo($ch);
            curl_close($ch);
            return [
                'errno' => $errno,
                'msg'   => $error,
                'info'  => $info,
            ];
        }
        curl_close($ch);
        return $result;
    }

    /**
     * NO CACHE 文件头
     *
     * @param $type
     * @param $charset
     */
    public static function no_cache_header($type = 'text/html', $charset = 'utf-8')
    {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Pragma: no-cache');
        header('Content-Type: ' . $type . '; charset=' . $charset . '');
    }

    /**
     * 获取 COOKIE
     * @param $name
     * @return bool|mixed
     */
    public static function get_cookie($name)
    {
        if (isset($_COOKIE[G_COOKIE_PREFIX . $name]))
        {
            return $_COOKIE[G_COOKIE_PREFIX . $name];
        }

        return false;
    }

    /**
     * 设置 COOKIE
     * @param $name
     * @param string $value
     * @param null $expire
     * @param string $path
     * @param null $domain
     * @param bool $secure
     * @param bool $httponly
     * @return bool
     */
    public static function set_cookie($name, $value = '', $expire = null, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        if (! $domain and G_COOKIE_DOMAIN)
        {
            $domain = G_COOKIE_DOMAIN;
        }

        return setcookie(G_COOKIE_PREFIX . $name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /*系统自定义404错误*/
    public static function error_404()
    {
        if ($_POST['_post_type'] == 'ajax')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, 'HTTP/1.1 404 Not Found'));
        }
        else
        {
            header('HTTP/1.1 404 Not Found');
            TPL::output('global/error_404');
            exit;
        }
    }

    /**
     * 解析重定向地址
     * @param $url
     * @return mixed|string
     */
    public static function parse_redirect_url($url)
    {
        if (substr($url, 0, 1) == '?')
        {
            $url = base_url() . $url;
        }
        else if (substr($url, 0, 1) == '/')
        {
            $url = get_js_url($url);
        }
        $url = domain_replace($url);
        return $url;
    }

    /**
     * 地址重定向
     * @param $url
     */
    public static function redirect($url)
    {
        if ($url = HTTP::parse_redirect_url($url))
        {
            header('Location: ' . $url);
            die;
        }
    }

    public static function download_filename_header($filename)
    {
        if (preg_match('~&#([0-9]+);~', $filename))
        {
            $filename_conv = @iconv('utf-8', 'UTF-8//IGNORE', $filename);
            if ($filename_conv !== false)
            {
                $filename = $filename_conv;
            }
            $filename = preg_replace(
                '~&#([0-9]+);~e',
                "convert_int_to_utf8('\\1')",
                $filename
            );
        }
        $filename_charset = 'utf-8';
        $filename = preg_replace('#[\r\n]#', '', $filename);
        if (self::is_browser('mozilla'))
        {
            $filename = "filename*=" . $filename_charset . "''" . rawurlencode($filename);
        }
        else
        {
            if ($filename_charset != 'utf-8' AND function_exists('iconv'))
            {
                $filename_conv = iconv($filename_charset, 'UTF-8//IGNORE', $filename);

                if ($filename_conv !== false)
                {
                    $filename = $filename_conv;
                }
            }

            if (self::is_browser('opera') OR self::is_browser('konqueror') OR self::is_browser('safari'))
            {
                $filename = 'filename="' . str_replace('"', '', $filename) . '"';
            }
            else if (self::is_browser('ie'))
            {
                $filename = 'filename="' . str_replace('+', ' ', urlencode($filename)) . '"';
            }
            else
            {
                $filename = 'filename="' . rawurlencode($filename) . '"';
            }
        }
        return $filename;
    }

    /**
     * @param $filename
     * @param int $filesize
     * @param int $modifytime
     */
    public static function force_download_header($filename, $filesize = 0, $modifytime = 0)
    {
        $range = 0;

        if ($_SERVER['HTTP_RANGE'])
        {
            list($range) = explode('-',(str_replace('bytes=', '', $_SERVER['HTTP_RANGE'])));
        }

        ob_end_clean();

        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header('Date: ' . gmdate('D, d M Y H:i:s', $modifytime) . ' GMT');
        header('Content-Disposition: attachment; ' . self::download_filename_header($filename));
        header("Content-Type: application/octet-stream");
        header('Accept-Ranges: bytes');

        if ($filesize)
        {
            if ($_SERVER['HTTP_RANGE'])
            {
                $rangesize = ($filesize - $range) > 0 ?  ($filesize - $range) : 0;
                header('Content-Length: ' . $rangesize);
                header('Content-Range: bytes ' . $range . '-' . ($filesize - 1) . '/' . ($filesize));
            }
            else
            {
                header('Content-Length: ' . $filesize);
            }
        }
    }

    /**
     * 下载远程文件
     * @param string $url 请求的地址
     * @param string $savePath 本地保存完整路径
     * @param mixed $params 传递的参数
     * @param array $header 传递的头部参数
     * @param int $timeout 超时设置，默认3600秒
     * @return bool|string
     */
    public static function download($url, $savePath, $params = '', $header = [], $timeout = 3600)
    {
        if (!is_dir(dirname($savePath))) {
            Dir::create(dirname($savePath));
        }

        $ch = curl_init();
        $fp = fopen($savePath, 'wb');

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header ? : ['Expect:']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 64000);
        curl_setopt($ch, CURLOPT_POST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $res        = curl_exec($ch);
        $curlInfo   = curl_getinfo($ch);
        if (curl_errno($ch) || $curlInfo['http_code'] != 200) {
            curl_error($ch);
            @unlink($savePath);
            return false;
        } else {
            curl_close($ch);
        }
        fclose($fp);
        return $savePath;
    }

    public static function is_browser($browser, $version = 0)
    {
        static $is;
        if (! is_array($is))
        {
            $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $is = array(
                'opera' => 0,
                'ie' => 0,
                'mozilla' => 0,
                'firebird' => 0,
                'firefox' => 0,
                'camino' => 0,
                'konqueror' => 0,
                'safari' => 0,
                'webkit' => 0,
                'webtv' => 0,
                'netscape' => 0,
                'mac' => 0
            );

            // detect opera
            # Opera/7.11 (Windows NT 5.1; U) [en]
            # Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 5.0) Opera 7.02 Bork-edition [en]
            # Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 4.0) Opera 7.0 [en]
            # Mozilla/4.0 (compatible; MSIE 5.0; Windows 2000) Opera 6.0 [en]
            # Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC) Opera 5.0 [en]
            if (strpos($useragent, 'opera') !== false)
            {
                preg_match('#opera(/| )([0-9\.]+)#', $useragent, $regs);
                $is['opera'] = $regs[2];
            }

            // detect internet explorer
            # Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Q312461)
            # Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.0.3705)
            # Mozilla/4.0 (compatible; MSIE 5.22; Mac_PowerPC)
            # Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC; e504460WanadooNL)
            if (strpos($useragent, 'msie ') !== false and ! $is['opera'])
            {
                preg_match('#msie ([0-9\.]+)#', $useragent, $regs);
                $is['ie'] = $regs[1];
            }

            // detect macintosh
            if (strpos($useragent, 'mac') !== false)
            {
                $is['mac'] = 1;
            }

            // detect safari
            # Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-us) AppleWebKit/74 (KHTML, like Gecko) Safari/74
            # Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/51 (like Gecko) Safari/51
            if (strpos($useragent, 'applewebkit') !== false and $is['mac'])
            {
                preg_match('#applewebkit/(\d+)#', $useragent, $regs);
                $is['webkit'] = $regs[1];

                if (strpos($useragent, 'safari') !== false)
                {
                    preg_match('#safari/([0-9\.]+)#', $useragent, $regs);
                    $is['safari'] = $regs[1];
                }
            }

            // detect konqueror
            # Mozilla/5.0 (compatible; Konqueror/3.1; Linux; X11; i686)
            # Mozilla/5.0 (compatible; Konqueror/3.1; Linux 2.4.19-32mdkenterprise; X11; i686; ar, en_US)
            # Mozilla/5.0 (compatible; Konqueror/2.1.1; X11)
            if (strpos($useragent, 'konqueror') !== false)
            {
                preg_match('#konqueror/([0-9\.-]+)#', $useragent, $regs);
                $is['konqueror'] = $regs[1];
            }

            // detect mozilla
            # Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.4b) Gecko/20030504 Mozilla
            # Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.2a) Gecko/20020910
            # Mozilla/5.0 (X11; U; Linux 2.4.3-20mdk i586; en-US; rv:0.9.1) Gecko/20010611
            if (strpos($useragent, 'gecko') !== false and ! $is['safari'] and ! $is['konqueror'])
            {
                preg_match('#gecko/(\d+)#', $useragent, $regs);
                $is['mozilla'] = $regs[1];

                // detect firebird / firefox
                # Mozilla/5.0 (Windows; U; WinNT4.0; en-US; rv:1.3a) Gecko/20021207 Phoenix/0.5
                # Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.4b) Gecko/20030516 Mozilla Firebird/0.6
                # Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.4a) Gecko/20030423 Firebird Browser/0.6
                # Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.6) Gecko/20040206 Firefox/0.8
                if (strpos($useragent, 'firefox') !== false or strpos($useragent, 'firebird') !== false or strpos($useragent, 'phoenix') !== false)
                {
                    preg_match('#(phoenix|firebird|firefox)( browser)?/([0-9\.]+)#', $useragent, $regs);
                    $is['firebird'] = $regs[3];

                    if ($regs[1] == 'firefox')
                    {
                        $is['firefox'] = $regs[3];
                    }
                }

                // detect camino
                # Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-US; rv:1.0.1) Gecko/20021104 Chimera/0.6
                if (strpos($useragent, 'chimera') !== false or strpos($useragent, 'camino') !== false)
                {
                    preg_match('#(chimera|camino)/([0-9\.]+)#', $useragent, $regs);
                    $is['camino'] = $regs[2];
                }
            }

            // detect web tv
            if (strpos($useragent, 'webtv') !== false)
            {
                preg_match('#webtv/([0-9\.]+)#', $useragent, $regs);
                $is['webtv'] = $regs[1];
            }

            // detect pre-gecko netscape
            if (preg_match('#mozilla/([1-4]{1})\.([0-9]{2}|[1-8]{1})#', $useragent, $regs))
            {
                $is['netscape'] = "$regs[1].$regs[2]";
            }
        }

        // sanitize the incoming browser name
        $browser = strtolower($browser);
        if (substr($browser, 0, 3) == 'is_')
        {
            $browser = substr($browser, 3);
        }

        // return the version number of the detected browser if it is the same as $browser
        if ($is["$browser"])
        {
            // $version was specified - only return version number if detected version is >= to specified $version
            if ($version)
            {
                if ($is["$browser"] >= $version)
                {
                    return $is["$browser"];
                }
            }
            else
            {
                return $is["$browser"];
            }
        }

        // if we got this far, we are not the specified browser, or the version number is too low
        return 0;
    }

    public static function request($url, $method, $data = null, $timeout = 15, $header = null, $cookie = null)
    {
        if (defined('WECENTER_CURL_USERAGENT'))
        {
            $user_agent = WECENTER_CURL_USERAGENT;
        }
        else
        {
            $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/600.7.12 (KHTML, like Gecko) Version/8.0.7 Safari/600.7.12';
        }

        $headers = array(
            'API-RemoteIP' => fetch_ip()
        );

        if ($header)
        {
            $headers = array_merge($header, $headers);
        }

        $options = array(
            'useragent' => $user_agent,
            'timeout' => $timeout,
            'cookies' => $cookie,
            'verify' => false,
            'verifyname' => false,
        );
        $request =self::send($url,$data,$method,$headers,$timeout,$options);
        return $request;
    }

    public static function wc_request($url, $method, $data = null, $timeout = 15, $header = null, $cookie = null)
    {
        if (defined('WECENTER_CURL_USERAGENT'))
        {
            $user_agent = WECENTER_CURL_USERAGENT;
        }
        else
        {
            $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/600.7.12 (KHTML, like Gecko) Version/8.0.7 Safari/600.7.12';
        }

        $headers = array(
            'API-RemoteIP' => fetch_ip(),
            'Referer'=> base_url(),
            'CLIENT-IP'=>fetch_ip(),
        );

        if ($header)
        {
            $headers = array_merge($header, $headers);
        }

        $options = array(
            'useragent' => $user_agent,
            'timeout' => $timeout,
            'cookies' => $cookie,
            'verify' => false,
            'verifyname' => false,
            'referer' => base_url(),
        );
        $result =self::send($url,$data,$method,$headers,$timeout,$options);
        $result = json_decode($result,true);
        if($result['errno']==1)
        {
            return  $result['rsm'];
        }else{
            return false;
        }
    }
}