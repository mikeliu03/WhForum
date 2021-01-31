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
class main extends AWS_MOBILE_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'black'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
        $rule_action['actions'] = array();
        return $rule_action;
    }

    /*登陆*/
    public function index_action()
    {
        if (!is_mobile())
        {
            HTTP::redirect('/explore/');
        }
        TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('explore'));
        TPL::output('m/explore/index');
    }
}
