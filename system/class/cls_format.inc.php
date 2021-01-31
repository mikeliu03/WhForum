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
class FORMAT
{
	public static function parse_links($str, $mode = 'text')
	{
		if ($mode == 'bbcode')
		{
			$callback = 'parse_link_callback_bbcode';

			$str = preg_replace('#\[url\]([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\[/url\]#is', '$1', $str);
		}
		else
		{
			$callback = 'parse_link_callback';
		}

		$str = @preg_replace_callback('/(?<!!!\[\]\(|"|\'|\=|\)|>)(https?:\/\/[-a-zA-Z0-9@:;%_\+.~#?\&\/\/=!]+)(?!"|\'|\)|>)/i', $callback, $str);

		return $str;
	}

	public static function outside_url_exists($str)
	{
		$str = strtolower($str);

		if (strstr($str, 'http'))
		{
			preg_match_all('/(https?:\/\/[-a-zA-Z0-9@:;%_\+.~#?\&\/\/=!]+)/i', $str, $matches);
		}
		else
		{
			preg_match_all('/(www\.[-a-zA-Z0-9@:;%_\+\.~#?&\/\/=]+)/i', $str, $matches);
		}

		if ($matches)
		{
			foreach($matches as $key => $val)
			{
				if (!$val)
				{
					continue;
				}

				if (!is_inside_url($val[0]))
				{
					return true;
				}
			}
		}

		return false;
	}

	public static function parse_attachs($str, $get_attachs_id = false)
	{
		if ($get_attachs_id)
		{
			preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $str, $matches);

			return array_unique($matches[1]);
		}
		else
		{
			return preg_replace_callback('/\[attach\]([0-9]+)\[\/attach\]/i', 'parse_attachs_callback', $str);
		}
	}

	public static function parse_imgs($str)
	{
        if (!$str) return false;

        if(get_setting('enable_img_box')=='Y')
        {
            return preg_replace_callback('/<img.*?src="(.*?)".*?>/is', 'parse_imgs_callback', htmlspecialchars_decode($str));
        }else{
            return htmlspecialchars_decode($str);
        }
	}

	public static function parse_bbcode($text)
	{
		if (!$text)
		{
			return false;
		}
		return load_class('Services_BBCode')->parse($text);
	}

	// 兼容旧版本
	public static function parse_markdown($text)
	{
		return self::parse_bbcode($text);
	}

	public static function bbcode_2_markdown($text)
	{
		$p[] = '#\[img\]([\w]+?://[\w\#$%&~/.\-;:=,' . "'" . '?@\[\]+]*?)\[/img\]#is';
		$p[] = '#\[img\]<a (.*?)>([\w]+?://[\w\#$%&~/.\-;:=,' . "'" . '?@\[\]+]*?)</a>\[/img\]#is';

		$p[] = "#\[url\]([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\[/url\]#is";
		$p[] = "#\[url\]<a (.*?)>([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)</a>\[/url\]#is";

		$p[] = "#\[url=([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
		$p[] = "#\[url=<a (.*?)>([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)</a>\]<a (.*?)>([^?\n\r\t].*?)</a>\[/url\]#is";
		$p[] = "#\[url=<a (.*?)>([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)</a>\]([^?\n\r\t].*?)\[/url\]#is";

		$p[] = "#\[url=([\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
		$p[] = "#\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/email\]#si";
		$p[] = '/\[url\]([^?].*?)\[\/url\]/i';
		$p[] = "#\[color=(.*?)\](.*?)\[/color\]#is";
		$p[] = "#\[size=(.*?)\](.*?)\[/size\]#is";
		$p[] = "#\[font=(.*?)\](.*?)\[/font\]#is";
		$p[] = '/\[pre\]([^?].*?)\[\/pre\]/i';
		$p[] = '/\[address\]([^?].*?)\[\/address\]/i';
		$p[] = '/\[h1\]([^?].*?)\[\/h1\]/i';
		$p[] = '/\[h2\]([^?].*?)\[\/h2\]/i';
		$p[] = '/\[h3\]([^?].*?)\[\/h3\]/i';
		$p[] = "#\[code=(.*?)\](.*?)\[/code\]#is";
		$p[] = "#\[li\](.*?)\[/li\]#is";

		$r[] = '!($1)';
		$r[] = '!($2)';
		$r[] = '$1';
		$r[] = '$2';
		$r[] = '$1';
		$r[] = '$2';
		$r[] = '$2';
		$r[] = '$1';
		$r[] = '$1';
		$r[] = '$1';
		$r[] = '$2';
		$r[] = '$2';
		$r[] = '$2';
		$r[] = '$1';
		$r[] = '$1';
		$r[] = '## $1';
		$r[] = '### $1';
		$r[] = '### $1';
		$r[] = '{{{$2}}}';
		$r[] = '- $1';

		$text = preg_replace($p, $r, $text);

		$text = str_ireplace(array('[ul]', '[ol]', '[/ul]', '[/ol]'), '', $text);

		preg_match('/\[b\]/i', $text, $_m_b_open);
		preg_match('/\[\/b\]/i', $text, $_m_b_close);

		preg_match('/\[i\]/i', $text, $_m_i_open);
		preg_match('/\[\/i\]/i', $text, $_m_i_close);

		preg_match('/\[u\]/i', $text, $_m_u_open);
		preg_match('/\[\/u\]/i', $text, $_m_u_close);

		preg_match('/\[s\]/i', $text, $_m_s_open);
		preg_match('/\[\/s\]/i', $text, $_m_s_close);

		preg_match('/\[quote\]/i', $text, $_m_quote_open);
		preg_match('/\[\/quote\]/i', $text, $_m_quote_close);

		if (count($_m_b_open) == count($_m_b_close)) {
			$text = str_ireplace("[b]\n", '[b]', $text);
			$text = str_ireplace("\n[/b]", '[/b]', $text);
			$text = str_ireplace('[b]', '**', $text);
			$text = str_ireplace('[/b]', '**', $text);
		}

		if (count($_m_i_open) == count($_m_i_close)) {
			$text = str_ireplace("[i]\n", '[i]', $text);
			$text = str_ireplace("\n[/i]", '[/i]', $text);
			$text = str_ireplace('[i]', '_', $text);
			$text = str_ireplace('[/i]', '_', $text);
		}

		if (count($_m_u_open) == count($_m_u_close)) {
			$text = str_ireplace("[u]\n", '[u]', $text);
			$text = str_ireplace("\n[/u]", '[/u]', $text);
			$text = str_ireplace('[u]', '', $text);
			$text = str_ireplace('[/u]', '', $text);
		}

		if (count($_m_s_open) == count($_m_s_close)) {
			$text = str_ireplace("[s]\n", '[s]', $text);
			$text = str_ireplace("\n[/s]", '[/s]', $text);
			$text = str_ireplace('[s]', '', $text);
			$text = str_ireplace('[/s]', '', $text);
		}

		if (count($_m_quote_open) == count($_m_quote_close)) {
			$text = str_ireplace("[quote]\n", '[quote]', $text);
			$text = str_ireplace("\n[/quote]", '[/quote]', $text);
			$text = str_ireplace('[quote]', '> ', $text);
			$text = str_ireplace('[/quote]', "\n", $text);
		}

		$text = preg_replace('/\[(?![\/]?attach)[^\[\]]{1,}\]/', '', $text);

		return $text;
	}

	public static function markdown_2_bbcode($text)
	{
		$text = htmlspecialchars_decode($text);

		$text = preg_replace('/\*\*((?:(?!\*\*).)+)\*\*/', '[b]\1[/b]', $text);
		$text = preg_replace('/\*((?:(?!\*).)+)\*/', '[i]\1[/i]', $text);
		$text = preg_replace('/##((?:(?!##).)+)(?:##)?/', '[size=16]\1[/size]', $text);
		$text = preg_replace('/!!\[(?:(?!\]).)*\]\(((?:(?!\)).)+)\)/', '[video]\1[/video]', $text);
		$text = preg_replace('/!\[(?:(?!\]).)*\]\(((?:(?!\)).)+)\)/', '[img]\1[/img]', $text);
		$text = preg_replace('/\[((?:(?!\]).)+)\]\(((?:(?!\)).)+)\)/', '[url=\2]\1[/url]', $text);
		$text = preg_replace('/{{{(.+?)}}}/s', '[code]\1[/code]', $text);
		$text = preg_replace('/^>((?:(?!\n\n).)+)/ms', '[quote]\1[/quote]', $text);

		preg_match_all('/(^\d+\. .+\n?)+/m', $text, $num_list);
		if ($num_list[0])
		{
			foreach ($num_list[0] AS $value)
			{
				$new_value = trim(preg_replace('/^\d+\. (.+)/m', '[*]\1[/*]', $value));
				$text = str_replace($value, "[list=1]\n$new_value\n[/list]", $text);
			}
		}

		preg_match_all('/(^- .+\n?)+/m', $text, $nor_list);
		if ($nor_list[0])
		{
			foreach ($nor_list[0] AS $value)
			{
				$new_value = trim(preg_replace('/^- (.+)/m', '[*]\1[/*]', $value));
				$text = str_replace($value, "[list]\n$new_value\n[/list]", $text);
			}
		}

		return htmlspecialchars($text);
	}

	public static function sub_url($url, $length)
	{
		if (strlen($url) > $length)
		{
			$url = str_replace(array('%3A', '%2F'), array(':', '/'), rawurlencode($url));

			$url = substr($url, 0, intval($length * 0.6)) . ' ... ' . substr($url, - intval($length * 0.1));
		}

		return $url;
	}

    /**
     * 自动过滤网址文本为超链接
     * @param $ret
     * @return string|string[]|null
     */
	public static function parse_auto_link($ret)
    {
        $ret = ' ' . $ret;
        /*过滤a标签*/
        $ret = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', function ($matches){
            $ret = '';
            $url = $matches[2];
            $url = str_replace('&nbsp;','',$url);
            if ( empty($url) )
                return $matches[0];
            if ( in_array(substr($url, -1), array('.', ',', ';', ':')) === true ) {
                $ret = substr($url, -1);
                $url = substr($url, 0, strlen($url)-1);
            }
            return $matches[1] . "<a href=\"$url\" rel=\"nofollow\" target=\"_blank\">$url</a>" . $ret;
        }, $ret);

        /*过滤ftp*/
        $ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', function ($matches){
            $ret = '';
            $dest = $matches[2];
            $dest = str_replace('&nbsp;','',$dest);
            $dest = 'http://' . $dest;
            if ( empty($dest) )
                return $matches[0];
            if ( in_array(substr($dest, -1), array('.', ',', ';', ':')) === true ) {
                $ret = substr($dest, -1);
                $dest = substr($dest, 0, strlen($dest)-1);
            }
            return $matches[1] . "<a href=\"$dest\" rel=\"nofollow\" target=\"_blank\">$dest</a>" . $ret;
        }, $ret);

        /*过滤Email*/
        $ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', function ($matches){
            $email = $matches[2] . '@' . $matches[3];
            return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
        }, $ret);

        $ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
        $ret = trim($ret);
        return $ret;
    }
}