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
if (! defined('IN_ANWSION'))
{
    die();
}
class people extends AWS_MOBILE_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
        $rule_action['actions'] = array(
            'index_square'
        );
        return $rule_action;
    }

    public function index_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/people/'.$_GET['id']);
        }
        if (isset($_GET['notification_id']))
        {
            $this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
        }

        if ($this->user_id AND !$_GET['id'])
        {
            $user = $this->user_info;
        }
        else
        {
            if (is_digits($_GET['id']))
            {
                if (!$user = $this->model('account')->get_user_info_by_uid($_GET['id'], TRUE))
                {
                    $user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE);
                }
            }
            else if ($user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE))
            {

            }
            else
            {
                $user = $this->model('account')->get_user_info_by_url_token($_GET['id'], TRUE);
            }

            if (!$user)
            {
                HTTP::error_404();
            }
            $this->model('people')->update_views($user['uid']);
        }

        if ($user['forbidden'] AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            header('HTTP/1.1 404 Not Found');
            H::redirect_msg(AWS_APP::lang()->_t('该用户已被封禁'), '/');
        }

        TPL::assign('user', $user);

        TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $user['uid']));

        TPL::assign('reputation_topics', $this->model('people')->get_user_reputation_topic($user['uid'], $user['reputation'], 12));
        TPL::assign('fans_list', $this->model('follow')->get_user_fans($user['uid'], 20));
        TPL::assign('friends_list', $this->model('follow')->get_user_friends($user['uid'], 20));
        TPL::assign('focus_topics', $this->model('topic')->get_focus_topic_list($user['uid'], 8));
        $this->crumb(AWS_APP::lang()->_t('%s 的个人主页', $user['user_name']), '/m/people/' . $user['url_token']);
        $job_info = $this->model('account')->get_jobs_by_id($user['job_id']);
        TPL::assign('job_name', $job_info['job_name']);
        if ($user['weibo_visit'])
        {
            if ($users_sina = $this->model('openid_weibo_oauth')->get_weibo_user_by_uid($user['uid']))
            {
                TPL::assign('sina_weibo_url', 'http://www.weibo.com/' . $users_sina['id']);
            }
        }
        if($this->user_id == intval($_GET['id']))
        {
            TPL::output('m/people/index');
        }else{
            TPL::output('m/people/other');
        }
    }

    public function index_square_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/people/');
        }
        $this->crumb(AWS_APP::lang()->_t('用户列表'), '/m/people/');
        TPL::output('m/people/square');
    }

    /*账号设置*/
    public function setting_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/account/setting/profile/');
        }
        TPL::output('m/people/setting');
    }

    /*个人资料*/
    public function profile_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/account/setting/profile/');
        }
        for ($i = date('Y'); $i > 1900; $i--)
        {
            $birthday_y[$i] = $i;
        }

        TPL::assign('birthday_y', $birthday_y);

        for ($tmp_i = 1; $tmp_i <= 31; $tmp_i ++)
        {
            $birthday_d[$tmp_i] = $tmp_i;
        }

        TPL::assign('birthday_d', $birthday_d);
        TPL::assign('job_list', $this->model('work')->get_jobs_list());
        TPL::import_js('js/fileupload.js');
        TPL::output('m/people/profile');
    }

    /*账号安全*/
    public function security_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/account/setting/security/');
        }
        TPL::output('m/people/security');
    }

    /*账号绑定*/
    public function bind_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/account/setting/openid/');
        }
        if (get_setting('qq_login_enabled') == 'Y')
        {
            TPL::assign('qq', $this->model('openid_qq')->get_qq_user_by_uid($this->user_id));
        }

        if (get_setting('sina_weibo_enabled') == 'Y')
        {
            TPL::assign('sina_weibo', $this->model('openid_weibo_oauth')->get_weibo_user_by_uid($this->user_id));
        }

        if (get_setting('weixin_app_id'))
        {
            TPL::assign('weixin', $this->model('openid_weixin_weixin')->get_user_info_by_uid($this->user_id));
        }

        if (get_setting('google_login_enabled') == 'Y')
        {
            TPL::assign('google', $this->model('openid_google')->get_google_user_by_uid($this->user_id));
        }

        if (get_setting('facebook_login_enabled') == 'Y')
        {
            TPL::assign('facebook', $this->model('openid_facebook')->get_facebook_user_by_uid($this->user_id));
        }

        if (get_setting('twitter_login_enabled') == 'Y')
        {
            TPL::assign('twitter', $this->model('openid_twitter')->get_twitter_user_by_uid($this->user_id));
        }

        TPL::output('m/people/bind');
    }

    /*账号认证*/
    public function verify_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/account/setting/verify/');
        }
        TPL::assign('verify_apply', $this->model('verify')->fetch_apply($this->user_id));
        TPL::output('m/people/verify');
    }

    /*粉丝列表*/
    public function fans_action()
    {
        TPL::output('m/people/fans');
    }

    /*修改密码*/
    public function modify_password_action()
    {
        TPL::output('m/people/modify_password');
    }

    /*修改邮箱*/
    public function modify_email_action()
    {
        TPL::output('m/people/modify_email');
    }

    /*修改手机号*/
    public function modify_mobile_action()
    {
        TPL::output('m/people/modify_mobile');
    }

    /*修改交易密码*/
    public function trade_action()
    {
        TPL::output('m/people/trade');
    }
}