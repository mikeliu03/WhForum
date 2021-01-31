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
class account extends AWS_MOBILE_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'black'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
        $rule_action['actions'] = array();
        return $rule_action;
    }

    /*登陆*/
    public function login_action()
    {
        if (!is_mobile())
        {
            HTTP::redirect('/account/login/');
        }

        $url = base64_decode($_GET['url']);

        if ($this->user_id)
        {
            if ($url)
            {
                header('Location: ' . $url);
            }
            else
            {
                HTTP::redirect('/m/');
            }
        }
        if ($url)
        {
            $return_url = $url;
        }
        else if (strstr($_SERVER['HTTP_REFERER'], '/m/'))
        {
            $return_url = $_SERVER['HTTP_REFERER'];
        }
        else
        {
            $return_url = get_js_url('/m/');
        }

        if (in_weixin() AND get_setting('weixin_app_id') AND get_setting('weixin_account_role') == 'service')
        {
            HTTP::redirect($this->model('openid_weixin_weixin')->redirect_url($return_url));
        }
        TPL::assign('body_class', 'explore-body');
        TPL::assign('return_url', strip_tags($return_url));
        $this->crumb(AWS_APP::lang()->_t('登录'), '/m/account/login/');
        TPL::output('m/account/login');
    }

    /*注册*/
    public function register_action()
    {
        if (($this->user_id AND !$_GET['weixin_id']) OR $this->user_info['weixin_id'])
        {
            if ($_GET['url'])
            {
                header('Location: ' . $_GET['url']);
            }
            else
            {
                HTTP::redirect('/m/');
            }
        }

        if (get_setting('register_type') == 'close')
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站目前关闭注册'));
        }
        else if (get_setting('register_type') == 'invite' AND !$_GET['icode'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站只能通过邀请注册'));
        }
        else if (get_setting('register_type') == 'weixin')
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站只能通过微信注册'));
        }

        if ($_GET['icode'])
        {
            if ($this->model('invitation')->check_code_available($_GET['icode']))
            {
                TPL::assign('icode', $_GET['icode']);
            }
            else
            {
                H::redirect_msg(AWS_APP::lang()->_t('邀请码无效或已经使用，请使用新的邀请码'), '/');
            }
        }
        if($_GET['type'] == 'email')
        {
            $type = 'email';
            $other_valid =array('type'=>'mobile','name'=>'手机注册');

        }else if(get_hook_info('mobile_regist')['state'] == 1 && get_hook_config('mobile_regist')['register_valid_type']['value'] && $_GET['type'] != 'email')
        {
            switch (get_hook_config('mobile_regist')['register_valid_type']['value'])
            {
                case 'mobile':
                    $type = 'mobile';
                    $other_valid = array();
                    break;
                case 'double_certification':
                    if($_GET['type'] == 'email'){
                        $type = 'email';        //当前验证方式
                        $other_valid = array('type'=>'mobile','name'=>'手机注册');        //  另一种验证方式
                    }elseif($_GET['type'] == 'mobile'){
                        $type = 'mobile';
                        $other_valid = array('type'=>'email','name'=>'邮箱注册');
                    }else{
                        $type = 'email';
                        $other_valid = array('type'=>'mobile','name'=>'手机注册');
                    }
                    break;
            }
        }else{
            $type = 'email';
            $other_valid = array('type'=>'mobile','name'=>'手机注册');
        }
        TPL::assign('type', $type);
        TPL::assign('other_valid', $other_valid);
        $this->crumb(AWS_APP::lang()->_t('注册'), '/m/register/');
        TPL::assign('body_class', 'explore-body');
        TPL::output('m/account/register');
    }

    /*找回密码*/
    public function find_password_action()
    {
        $this->crumb(AWS_APP::lang()->_t('找回密码'), '/m/find_password/');
        if(get_hook_info('mobile_regist')['state'] == 1)
        {
            switch ($_GET['type'])
            {
                case 'mobile':
                    $type = 'mobile'; //当前验证方式
                    $other_valid = array('type'=>'email','name'=>'邮箱验证');   //  另一种验证方式
                    break;

                case 'email':
                    $type = 'email';
                    $other_valid = array('type'=>'mobile','name'=>'短信验证');
                    break;

                default :
                    $type = 'email';
                    $other_valid = array('type'=>'mobile','name'=>'短信验证');
            }
        }else{
            $type = 'email';
            $other_valid = array();
        }
        $action_link = $type == 'mobile' ? get_js_url('account/ajax/first_find_password/') : get_js_url('account/ajax/request_find_password/');
        TPL::assign('type', $type);
        TPL::assign('other_valid', $other_valid);
        TPL::assign('action_link', $action_link);
        TPL::output('m/account/find_password');
    }

    /*密码找回成功*/
    public function find_password_success_action()
    {
        TPL::assign('email', AWS_APP::session()->find_password);
        $this->crumb(AWS_APP::lang()->_t('找回密码'), '/m/find_password_success/');
        TPL::output('m/account/find_password_success');
    }

    /*找回密码确认*/
    public function find_password_modify_action()
    {
        if (!$active_code_row = $this->model('active')->get_active_code($_GET['key'], 'FIND_PASSWORD'))
        {
            H::redirect_msg(AWS_APP::lang()->_t('链接已失效'), '/');
        }

        if ($active_code_row['active_time'] OR $active_code_row['active_ip'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('链接已失效'), '/');
        }

        TPL::output('m/account/find_password_modify');
    }
}
