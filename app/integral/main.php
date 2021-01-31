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


if (!defined('IN_ANWSION'))
{
    die;
}

class main extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }

    public function rule_action()
    {
        $this->crumb(AWS_APP::lang()->_t(get_setting('site_name').get_setting('integral_unit').'规则'));

        if (get_setting('integral_system_enabled') != 'Y')
        {
            H::redirect_msg(AWS_APP::lang()->_t('本站未启用'.get_setting('integral_unit').'系统'), '/');
        }

        TPL::output('integral/rule');
    }
}