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
    die();
}

class notifications extends AWS_MOBILE_CONTROLLER
{
    protected $per_page;
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';
        return $rule_action;
    }

    function setup()
    {
        HTTP::no_cache_header();
        $this->per_page = get_setting('notifications_per_page');
    }

    /*消息列表*/
    public function index_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/notifications/');
        }
        $list_data = null;
        if($this->user_info['inbox_unread'])
        {
            if ($dialog = $this->model('message')->fetch_row('inbox_dialog','recipient_uid = '.$this->user_id .' or sender_uid = '.$this->user_id,'update_time DESC'))
            {
                if ($dialog_list = $this->model('message')->get_message_by_dialog_id($dialog['id'],'add_time DESC',0,1))
                {
                    foreach ($dialog_list as $key => $value)
                    {
                        if($value['uid'] != $this->user_id)
                        {
                            $recipient_user = $this->model('account')->get_user_info_by_uid($value['uid'],false,true,true);
                            $value['message'] = html_entity_decode(FORMAT::parse_attachs(FORMAT::parse_bbcode($value['message'])));
                            $value['user_name'] = $recipient_user['user_name'];
                            $value['other_uid'] = $recipient_user['uid'];
                            $value['avatar_file'] = get_avatar_url($recipient_user['uid'], 'mid');
                            $value['add_time'] = date_friendly($value['add_time']);
                            $list_data = $value;
                        }
                    }
                }
            }
        }
        TPL::assign('msg', $list_data);
        TPL::output('m/notifications/index');
    }

    /*通知设置*/
    public function setting_action()
    {
        if(!is_mobile())
        {
            HTTP::redirect('/account/setting/privacy/');
        }
        TPL::assign('notification_settings', $this->model('account')->get_notification_setting_by_uid($this->user_id));
        TPL::assign('notify_actions', $this->model('notify')->notify_action_details);
        TPL::output('m/notifications/setting');
    }
}