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
if (!defined('IN_ANWSION')) {
    die;
}

class main extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = "black"; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
        return $rule_action;
    }

    /*插件跳转控制*/
    public function index_action()
    {
        $p=trim($_GET['p']);
        $a=trim($_GET['a']);
        $data = $_POST? $_POST : $_GET;
        unset($data['p'],$data['a'],$data['app'],$data['act']);
        return hook($p,$a,$data);
    }
}