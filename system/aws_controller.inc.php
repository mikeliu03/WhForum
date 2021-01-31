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
 * WeCenter 前台控制器
 * @package		WeCenter
 * @subpackage	System
 * @category	Libraries
 * @author		WeCenter Dev Team
 */
class AWS_CONTROLLER
{

	protected static $init = false;
	public function __construct($process_setup = true)
	{
        if(self::$init && $_GET['app']!='admin'){return true;}
        self::$init = true;
		//升级程序能访问 不查询表数据
        if ($this->model('banip')->check_ip(fetch_ip()))
        {
            H::redirect_msg('您的IP已被封禁,请联系管理员.');
        }
		if (!in_array($_GET['app'], array('upgrade')))
		{
			// 获取当前用户 User ID
            $this->user_id = AWS_APP::user()->get_info('uid');
            $nav_list = $this->model('admin')->get_front_nav_list();
            TPL::assign('front_nav',  $nav_list);
			if ($this->user_info = $this->model('account')->get_user_info_by_uid($this->user_id, TRUE))
			{
				$user_group = $this->model('account')->get_user_group($this->user_info['group_id'], $this->user_info['reputation_group']);
				if ($this->user_info['default_timezone'])
				{
					date_default_timezone_set($this->user_info['default_timezone']);
				}
				$this->model('online')->online_active($this->user_id, $this->user_info['last_active']);
			}
			else if ($this->user_id)
			{
				$this->model('account')->logout();
			}
			else
			{
				$user_group = $this->model('account')->get_user_group_by_id(99);
				if ($_GET['fromuid'])
				{
					HTTP::set_cookie('fromuid', $_GET['fromuid']);
				}
			}
			#用户删除
	        if($this->user_info['is_del'] == 1){
	            H::redirect_msg('用户已被管理员删除.');
	        }
			$this->user_info['group_name'] = $user_group['group_name'];
			$this->user_info['permission'] = $user_group['permission'];
			AWS_APP::session()->permission = $this->user_info['permission'];
			if ($this->user_info['forbidden'] == 1)
			{
				$this->model('account')->logout();
				H::redirect_msg(AWS_APP::lang()->_t('抱歉, 你的账号已经被禁止登录'), '/');
			}
			else
			{
				TPL::assign('user_id', $this->user_id);
				TPL::assign('user_info', $this->user_info);
			}

			if ($this->user_id and ! $this->user_info['permission']['human_valid'])
			{
				unset(AWS_APP::session()->human_valid);
			}
			else if ($this->user_info['permission']['human_valid'] and ! is_array(AWS_APP::session()->human_valid))
			{
				AWS_APP::session()->human_valid = array();
			}
		}
        $css = 'common.css';
        if ($this->user_id&&$this->user_info['skin'])
        {
            $css = $this->user_info['skin'];
        }
		// 引入系统 CSS 文件
		TPL::import_css(array(
			'css/'.$css,
			'css/link.css',
			'js/plug_module/style.css',
		));

		if (defined('SYSTEM_LANG'))
		{
			TPL::import_js(base_url() . '/language/' . SYSTEM_LANG . '.js');
		}

		if (HTTP::is_browser('ie', 8))
		{
			TPL::import_js(array(
				'js/jquery.js',
				'js/respond.js'
			));
		}
		else
		{
			TPL::import_js('js/jquery.2.js');
		}

		// 引入系统 JS 文件
		TPL::import_js(array(
			'js/jquery.form.js',
			'js/plug_module/plug-in_module.js',
			'js/aws.js',
			'js/aw_template.js',
			'js/layer/layer.js',
			'js/app.js',
		));

		// 产生面包屑导航数据
		$this->crumb(get_setting('site_name'), base_url());

		// 载入插件
		/*if ($plugins = AWS_APP::plugins()->parse($_GET['app'], $_GET['c'], 'setup'))
		{
			foreach ($plugins as $plugin_file)
			{
				include $plugin_file;
			}
		}*/

		if (get_setting('site_close') == 'Y' AND $this->user_info['group_id'] != 1 AND !in_array($_GET['app'], array('admin', 'account', 'upgrade')))
		{
			$this->model('account')->logout();
			$login_url = is_mobile() ? get_js_url('/m/login/') : get_js_url('/account/login/');
			H::redirect_msg(get_setting('close_notice'), $login_url);
		}

		if ($_GET['ignore_ua_check'] == 'TRUE')
		{
			HTTP::set_cookie('_ignore_ua_check', 'TRUE', (time() + 3600 * 24 * 7));
		}

		//前台控制器初始化执行钩子
        if($_GET['app']!='admin')
        {
            run_hook('app_begin',['app'=>$_GET['app'],'controller'=>$_GET['c'],'action'=>$_GET['act']]);
        }
		// 执行控制器 Setup 动作
		if ($process_setup)
		{
			$this->setup();
		}
	}

	/**
	 * 控制器 Setup 动作
	 * 每个继承于此类库的控制器均会调用此函数
	 * @access	public
	 */
	public function setup() {}

	public function doact_action()
    {
		$p=trim($_GET['p']);
		$a=trim($_GET['a']);
		$data=$_POST?$_POST:$_GET;
		return hook($p,$a,$data);
	}

	/**
	 * 判断当前访问类型是否为 POST
	 * 调用 $_SERVER['REQUEST_METHOD']
	 * @access	public
	 * @return	boolean
	 */
	public function is_post()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * 调用系统 Model
	 * 于控制器中使用 $this->model('class')->function() 进行调用
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	public function model($model = null)
	{
		return AWS_APP::model($model);
	}

    /**
     * 产生面包屑导航数据
     * 产生面包屑导航数据并生成浏览器标题供前端使用
     * @param $name
     * @param null $url
     * @return $this
     */
	public function crumb($name, $url = null)
	{
		if (is_array($name))
		{
			foreach ($name as $key => $value)
			{
				$this->crumb($key, $value);
			}
			return $this;
		}

		$name = htmlspecialchars_decode($name);

		$crumb_template = $this->crumb;

		if (strlen($url) > 1 and substr($url, 0, 1) == '/')
		{
			$url = base_url() . substr($url, 1);
		}

		$this->crumb[] = array(
			'name' => $name,
			'url' => $url
		);

		$crumb_template['last'] = array(
			'name' => $name,
			'url' => $url
		);

		TPL::assign('crumb', $crumb_template);
		foreach ($this->crumb as $key => $crumb)
		{
			if($_GET['app']=='explore' )
			{
				$title = $crumb['name'] . ' - ' . $title;
			}
	      	else{
				$title = $crumb['name']. ' - ' . get_setting('brand_name');
			}
		}
		TPL::assign('page_title', htmlspecialchars(rtrim($title, ' - ')));
		return $this;
	}

	public function publish_approval_valid($content = null)
	{
		if ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'])
		{
			return false;
		}

		if ($default_timezone = get_setting('default_timezone'))
		{
			date_default_timezone_set($default_timezone);
		}

		if ($this->user_info['permission']['publish_approval'] == 1)
		{
			if (!$this->user_info['permission']['publish_approval_time']['start'] AND !$this->user_info['permission']['publish_approval_time']['end'])
			{
				if ($this->user_info['default_timezone'])
				{
					date_default_timezone_set($this->user_info['default_timezone']);
				}

				return true;
			}

			if ($this->user_info['permission']['publish_approval_time']['start'] < $this->user_info['permission']['publish_approval_time']['end'])
			{
				if (intval(date('H')) >= $this->user_info['permission']['publish_approval_time']['start'] AND intval(date('H')) < $this->user_info['permission']['publish_approval_time']['end'])
				{
					if ($this->user_info['default_timezone'])
					{
						date_default_timezone_set($this->user_info['default_timezone']);
					}

					return true;
				}
			}

			if ($this->user_info['permission']['publish_approval_time']['start'] > $this->user_info['permission']['publish_approval_time']['end'])
			{
				if (intval(date('H')) >= $this->user_info['permission']['publish_approval_time']['start'] OR intval(date('H')) < $this->user_info['permission']['publish_approval_time']['end'])
				{
					if ($this->user_info['default_timezone'])
					{
						date_default_timezone_set($this->user_info['default_timezone']);
					}

					return true;
				}
			}

			if ($this->user_info['permission']['publish_approval_time']['start'] == $this->user_info['permission']['publish_approval_time']['end'])
			{
				if (intval(date('H')) == $this->user_info['permission']['publish_approval_time']['start'])
				{
					if ($this->user_info['default_timezone'])
					{
						date_default_timezone_set($this->user_info['default_timezone']);
					}
					return true;
				}
			}
		}

		if ($this->user_info['default_timezone'])
		{
			date_default_timezone_set($this->user_info['default_timezone']);
		}

		if ($content AND H::sensitive_word_exists($content))
		{
			return true;
		}

		return false;
	}
}

/**
 * WeCenter 后台控制器
 *
 * @package		WeCenter
 * @subpackage	System
 * @category	Libraries
 * @author		WeCenter Dev Team
 */
class AWS_ADMIN_CONTROLLER extends AWS_CONTROLLER
{
	public $per_page = 20;
	public function __construct()
	{
		parent::__construct(false);
		if ($_GET['app'] != 'admin')
		{
			return false;
		}
		TPL::import_clean();
		if (defined('SYSTEM_LANG'))
		{
			TPL::import_js(base_url() . '/language/' . SYSTEM_LANG . '.js');
		}
		if (HTTP::is_browser('ie', 8))
		{
			TPL::import_js('js/jquery.js');
		}
		else
		{
			TPL::import_js('js/jquery.2.js');
		}
		TPL::import_js(array(
			'admin/js/aws_admin.js',
			'admin/js/aws_admin_template.js',
			'js/jquery.form.js',
			'admin/js/framework.js',
			'admin/js/global.js',
		));

		TPL::import_css(array(
			'admin/css/common.css'
		));

		if (in_array($_GET['act'], array(
			'login',
			'login_process',
		)))
		{
			return true;
		}

		$admin_info = json_decode(AWS_APP::crypt()->decode(AWS_APP::session()->admin_login), true);
		if ($admin_info['uid'])
		{
			if ($admin_info['uid'] != $this->user_id OR $admin_info['UA'] != md5($_SERVER['HTTP_USER_AGENT']) OR !AWS_APP::session()->permission['is_administortar'] AND !AWS_APP::session()->permission['is_moderator'])
			{
				unset(AWS_APP::session()->admin_login);

				if ($_POST['_post_type'] == 'ajax')
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('会话超时, 请重新登录')));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('会话超时, 请重新登录'), '/admin/login/url-' . base64_current_path());
				}
			}
		}
		else
		{
			if ($_POST['_post_type'] == 'ajax')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('会话超时, 请重新登录')));
			}
			else
			{
				HTTP::redirect('/admin/login/url-' . base64_current_path());
			}
		}
		$this->setup();
	}

	public function fetch_menu_list()
    {
		$c=$_GET['app'].'/'.$_GET['c'].'/'.$_GET['act'].'/';
		if($_GET['act']=='index'){
			$c=$_GET['app'].'/'.$_GET['c'].'/';
		}
		
		if($_GET['act']=='settings'){
			$c=$_GET['app'].'/'.$_GET['act'].'/category-'.$_GET['category'];
		}

		if($_GET['c'] == 'main' && $_GET['act'] == 'nav_menu'){
			$c=$_GET['app'].'/'.$_GET['act'].'/';
		}
		return $this->model('admin')->fetch_menu_list($c);
	}
}

/**
 * 手机端控制器基类
 * Class AWS_MOBILE_CONTROLLER
 */
class AWS_MOBILE_CONTROLLER extends AWS_CONTROLLER
{
    public function __construct()
    {
        parent::__construct(false);
        if (!is_mobile())
        {
            return false;
        }
        TPL::import_clean();

        if (defined('SYSTEM_LANG'))
        {
            TPL::import_js(base_url() . '/language/' . SYSTEM_LANG . '.js');
        }
        TPL::import_js(array(
            'js/jquery.2.js',
            'js/jquery.form.js',
            'h5/js/framework.js',
            'js/layui/layui.all.js',
            'h5/js/aws_mobile.js',
            'h5/js/aws_mobile_template.js',
            'h5/js/app.js',
        ));
        TPL::import_css(array(
            'js/layui/css/layui.mobile.css',
            'h5/css/int.css',
            'h5/css/toast.css',
            'h5/css/main.css',
        ));

        if (in_weixin())
        {
            $noncestr = mt_rand(1000000000, 9999999999);
            TPL::assign('weixin_noncestr', $noncestr);
            $jsapi_ticket = $this->model('openid_weixin_weixin')->get_jsapi_ticket($this->model('openid_weixin_weixin')->get_access_token(get_setting('weixin_app_id'), get_setting('weixin_app_secret')));
            $url = ($_SERVER['HTTPS'] AND !in_array(strtolower($_SERVER['HTTPS']), array('off', 'no'))) ? 'https' : 'http';
            $url .= '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            TPL::assign('weixin_signature', $this->model('openid_weixin_weixin')->generate_jsapi_ticket_signature(
                $jsapi_ticket,
                $noncestr,
                TIMESTAMP,
                $url
            ));
        }
        $this->setup();
    }

    /*插件输出模板*/
    public function plugins_output($template_filename, $display = true)
    {
        PLUTPL::output($template_filename, $display);
    }

    /*输出pc模板*/
    public function output($template_filename, $display = true)
    {
        TPL::output($template_filename, $display);
    }
}
