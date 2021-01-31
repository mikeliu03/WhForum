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

class weixin extends AWS_ADMIN_CONTROLLER
{
    public function reply_action()
    {
        $this->crumb(AWS_APP::lang()->_t('自定义回复'), 'admin/weixin/reply/');

        if (!isset($_GET['id']))
        {
            $_GET['id'] = 0;
        }

        $accounts_list = $this->model('weixin')->get_accounts_info();

        $account_info = $accounts_list[$_GET['id']];

        if (!$account_info)
        {
            H::redirect_msg(AWS_APP::lang()->_t('公众账号不存在'));
        }

        TPL::assign('account_info', $account_info);

        TPL::assign('rule_list', $this->model('weixin')->fetch_reply_rule_list($account_info['id']));

        TPL::assign('accounts_list', $accounts_list);

        // TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(801));
        TPL::assign('menu_list',  $this->fetch_menu_list());

        TPL::output('admin/weixin/reply');
    }

    public function reply_edit_action()
    {
        $this->crumb(AWS_APP::lang()->_t('编辑回复规则'), "admin/weixin/reply_edit/");

        if ($_GET['id'])
        {
            $rule_info = $this->model('weixin')->get_reply_rule_by_id($_GET['id']);

            if (!$rule_info)
            {
                H::redirect_msg(AWS_APP::lang()->_t('自定义回复规则不存在'), '/admin/weixin/reply/');
            }

            TPL::assign('account_id', $rule_info['account_id']);

            TPL::assign('rule_info', $rule_info);
        }
        else
        {
            if (!isset($_GET['account_id']))
            {
                $_GET['account_id'] = 0;
            }

            $account_info = $this->model('weixin')->get_account_info_by_id($_GET['account_id']);

            if (!$account_info)
            {
                H::redirect_msg(AWS_APP::lang()->_t('公众账号不存在'), '/admin/weixin/reply/');
            }

            TPL::assign('account_id', $account_info['id']);
        }

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(801));

        TPL::output('admin/weixin/reply_edit');
    }

    public function mp_menu_action()
    {
        $this->crumb(AWS_APP::lang()->_t('菜单管理'), 'admin/weixin/mp_menu/');

        if (!isset($_GET['id']))
        {
            $_GET['id'] = 0;
        }

        $accounts_list = $this->model('weixin')->get_accounts_info();

        $account_id = $accounts_list[$_GET['id']]['id'];

        if (!isset($account_id))
        {
            H::redirect_msg(AWS_APP::lang()->_t('公众账号不存在'));
        }

        if ($accounts_list[$account_id]['weixin_account_role'] == 'base' OR !$accounts_list[$account_id]['weixin_app_id'] OR !$accounts_list[$account_id]['weixin_app_secret'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('此功能不适用于未通过微信认证的订阅号'), '/admin/');
        }

        $this->model('weixin')->client_list_image_clean($accounts_list[$account_id]['weixin_mp_menu']);

        TPL::assign('account_id', $account_id);

        TPL::assign('mp_menu', $accounts_list[$account_id]['weixin_mp_menu']);

        TPL::assign('accounts_list', $accounts_list);

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(803));

        TPL::assign('feature_list', $this->model('feature')->get_enabled_feature_list('id DESC', null, null));

        if (get_setting('category_enable') == 'Y')
        {
            TPL::assign('category_data', json_decode($this->model('system')->build_category_json('question'), true));
        }

        TPL::assign('reply_rule_list', $this->model('weixin')->fetch_unique_reply_rule_list($account_id));

        TPL::import_js('js/fileupload.js');

        TPL::import_js('js/md5.js');

        TPL::output('admin/weixin/mp_menu');
    }

    public function save_mp_menu_action()
    {
        define('IN_AJAX', TRUE);

        if (!isset($_POST['account_id']))
        {
            $_POST['account_id'] = 0;
        }

        if ($_POST['button'])
        {
            if (!$mp_menu = $this->model('weixin')->process_mp_menu_post_data($_POST['button']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('远程服务器忙,请稍后再试')));
            }
        }

        $this->model('weixin')->update_setting_or_account($_POST['account_id'], array(
            'weixin_mp_menu' => $mp_menu
        ));

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function rule_save_action()
    {
        define('IN_AJAX', TRUE);

        if (!$_POST['keyword'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入关键词')));
        }

        if (!$_POST['title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入回应内容')));
        }

        if ($_POST['id'])
        {
            $rule_info = $this->model('weixin')->get_reply_rule_by_id($_POST['id']);

            if (!$rule_info)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('自定义回复规则不存在')));
            }
        }
        else
        {
            if (!$this->model('weixin')->get_account_info_by_id($_POST['account_id']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('公众账号不存在')));
            }

            if ($this->model('weixin')->get_reply_rule_by_keyword($_POST['account_id'], $_POST['keyword']) AND !$_FILES['image']['name'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经存在相同的文字回应关键词')));
            }
        }

        if ($_POST['use_old_image'])
        {
            $image_file = $rule_info['image_file'];
        }
        else
        {
            if ($rule_info['image_file'])
            {
                @unlink(get_setting('upload_dir') . '/weixin/' . $rule_info['image_file']);
            }

            if ($_FILES['image']['name'])
            {
                AWS_APP::upload()->initialize(array(
                    'allowed_types' => 'jpg,jpeg,png',
                    'upload_path' => get_setting('upload_dir') . '/weixin/',
                    'is_image' => TRUE
                ))->do_upload('image');

                if (AWS_APP::upload()->get_error())
                {
                    switch (AWS_APP::upload()->get_error())
                    {
                        default:
                            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                        break;

                        case 'upload_invalid_filetype':
                            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
                        break;
                    }
                }

                $upload_data = AWS_APP::upload()->data();

                if (!$upload_data)
                {
                    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
                }

                $origin_file = get_setting('upload_dir') . '/weixin/' . 'origin_' . $upload_data['file_name'];

                rename($upload_data['full_path'], $origin_file);

                AWS_APP::image()->initialize(array(
                    'quality' => 90,
                    'source_image' => $origin_file,
                    'new_image' => $upload_data['full_path'],
                    'width' => 640,
                    'height' => 320
                ))->resize();

                AWS_APP::image()->initialize(array(
                    'quality' => 90,
                    'source_image' => $origin_file,
                    'new_image' => get_setting('upload_dir') . '/weixin/square_' . basename($upload_data['full_path']),
                    'width' => 80,
                    'height' => 80
                ))->resize();

                $image_file = basename($upload_data['full_path']);
            }
        }

        if ($_POST['id'])
        {
            $this->model('weixin')->update_reply_rule($rule_info['id'], $_POST['title'], $_POST['description'], $_POST['link'], $image_file);
        }
        else
        {
            $this->model('weixin')->add_reply_rule($_POST['account_id'], $_POST['keyword'], $_POST['title'], $_POST['description'], $_POST['link'], $image_file);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/weixin/reply/id-' . $_POST['account_id'])
        ), 1, null));
    }

    public function accounts_action()
    {
        $this->crumb(AWS_APP::lang()->_t('公众账号管理'), "admin/weixin/accounts/");

        $accounts_list = $this->model('weixin')->fetch_page('weixin_accounts', null, 'id ASC', $_GET['page'], $this->per_page);

        $accounts_total = $this->model('weixin')->found_rows();

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/weixin/accounts/'),
            'total_rows' => $accounts_total,
            'per_page' => $this->per_page
        ))->create_links());

        TPL::assign('menu_list', $this->fetch_menu_list());

        TPL::assign('accounts_list', $accounts_list);

        TPL::assign('accounts_total', $accounts_total);

        TPL::output('admin/weixin/accounts');
    }

    public function account_action()
    {
        $this->crumb(AWS_APP::lang()->_t('编辑公众账号'), "admin/weixin/accounts/");

        if ($_GET['id'])
        {
            $account_info = $this->model('weixin')->get_account_info_by_id($_GET['id']);

            if (!$account_info)
            {
                H::redirect_msg(AWS_APP::lang()->_t('该账号不存在'), '/admin/weixin/accounts/');
            }

            TPL::assign('account_info', $account_info);
        }

        TPL::assign('menu_list', $this->fetch_menu_list());

        TPL::output('admin/weixin/account');
    }

    public function sent_msgs_list_action()
    {
        $this->crumb(AWS_APP::lang()->_t('群发消息列表'), "admin/weixin/sent_msgs_list/");

        $msgs_list = $this->model('weixin')->fetch_page('weixin_msg', null, 'id DESC', $_GET['page'], $this->per_page);

        $msgs_total = $this->model('weixin')->found_rows();

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/weixin/sent_msgs_list/'),
            'total_rows' => $msgs_total,
            'per_page' => $this->per_page
        ))->create_links());

        TPL::assign('menu_list', $this->fetch_menu_list());

        TPL::assign('msgs_list', $msgs_list);

        TPL::assign('msgs_total', $msgs_total);

        TPL::output('admin/weixin/sent_msgs_list');
    }

    public function sent_msg_details_action()
    {
        $this->crumb(AWS_APP::lang()->_t('查看群发消息'), "admin/weixin/sent_msg_details/");

        $msg_details = $this->model('weixin')->get_msg_details_by_id($_GET['id']);

        if (!$msg_details)
        {
            H::redirect_msg(AWS_APP::lang()->_t('群发消息不存在'), '/admin/weixin/sent_msgs_list/');
        }

        TPL::assign('menu_list', $this->fetch_menu_list());

        TPL::assign('msg_details', $msg_details);

        TPL::output('admin/weixin/sent_msg_details');
    }

    public function send_msg_batch_action()
    {
        $this->crumb(AWS_APP::lang()->_t('群发消息'), "admin/weixin/send_msg_batch/");

        if (get_setting('weixin_account_role') != 'subscription' AND get_setting('weixin_account_role') != 'service' OR !get_setting('weixin_app_id') OR !get_setting('weixin_app_secret'))
        {
            H::redirect_msg(AWS_APP::lang()->_t('此功能只适用于通过微信认证的公众号'), '/admin/weixin/sent_msgs_list/');
        }

        $groups = $this->model('weixin')->get_groups();

        if (!is_array($groups))
        {
            H::redirect_msg(AWS_APP::lang()->_t('获取微信分组失败, 错误为: %s', $groups), '/admin/weixin/sent_msgs_list/');
        }

        TPL::assign('menu_list', $this->fetch_menu_list());

        TPL::assign('groups', $groups);

        TPL::output('admin/weixin/send_msg_batch');
    }

    public function qr_code_action()
    {
        $this->crumb(AWS_APP::lang()->_t('二维码管理'), "admin/weixin/qr_code/");

        if (get_setting('weixin_account_role') != 'service' OR !get_setting('weixin_app_id') OR !get_setting('weixin_app_secret'))
        {
            H::redirect_msg(AWS_APP::lang()->_t('此功能只适用于通过微信认证的服务号'), '/admin/');
        }

        $qr_code_list = $this->model('weixin')->fetch_page('weixin_qr_code', 'ticket IS NOT NULL', 'scene_id ASC', $_GET['page'], $this->per_page);

        $qr_code_rows = $this->model('weixin')->found_rows();

        TPL::assign('qr_code_list', $qr_code_list);

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/weixin/qr_code/'),
            'total_rows' => $qr_code_rows,
            'per_page' => $this->per_page
        ))->create_links());

        TPL::assign('menu_list',$this->fetch_menu_list());

        TPL::output('admin/weixin/qr_code');
    }

    public function third_party_access_action()
    {
        $this->crumb(AWS_APP::lang()->_t('第三方接入'), 'admin/weixin/third_party_access/');

        if (!isset($_GET['id']))
        {
            $_GET['id'] = 0;
        }

        $accounts_list = $this->model('weixin')->get_accounts_info();

        $account_id = $accounts_list[$_GET['id']]['id'];

        if (!isset($account_id))
        {
            H::redirect_msg(AWS_APP::lang()->_t('公众账号不存在'), '/admin/weixin/mp_menu/');
        }

        TPL::assign('account_id', $account_id);

        $rule_list = $this->model('openid_weixin_third')->get_third_party_api_by_account_id($_GET['id']);

        TPL::assign('rule_list', $rule_list);

        TPL::assign('menu_list', $this->fetch_menu_list());

        TPL::output('admin/weixin/third_party_access');
    }

    public function edit_third_party_access_rule_action()
    {
        $this->crumb(AWS_APP::lang()->_t('接入规则编辑'), 'admin/weixin/third_party_access/');

        if ($_GET['id'])
        {
            $rule_info = $this->model('openid_weixin_third')->get_third_party_api_by_id($_GET['id']);

            if (!$rule_info)
            {
                H::redirect_msg(AWS_APP::lang()->_t('自定义回复规则不存在'), '/admin/weixin/reply/');
            }

            TPL::assign('account_id', $rule_info['account_id']);

            TPL::assign('rule_info', $rule_info);
        }
        else
        {
            if (!isset($_GET['account_id']))
            {
                $_GET['account_id'] = 0;
            }

            $account_info = $this->model('weixin')->get_account_info_by_id($_GET['account_id']);

            if (!$account_info)
            {
                H::redirect_msg(AWS_APP::lang()->_t('公众账号不存在'), '/admin/weixin/reply/');
            }

            TPL::assign('account_id', $account_info['id']);
        }

        TPL::assign('menu_list', $this->fetch_menu_list());

        TPL::output('admin/weixin/edit_third_party_access_rule');
    }

    /*微信通知模板列表*/
    public function template_msg_action()
    {
        $page = intval($_GET['page']);
        $template_msg_list = $this->model('weixin')->fetch_page('weixin_template',null, null, $page,$this->per_page);
        $template_msg_list_total = $this->model('weixin')->found_rows();
        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/weixin/template_msg/'),
            'total_rows' =>$template_msg_list_total,
            'per_page' => $this->per_page
        ))->create_links());

        $template_type=array(
            'AT_ME'=>'当有人@我',
            'NEW_ANSWER'=>'我的问题有了新回复',
            'NEW_ARTICLE_ANSWER'=>'我的文章有了新评论',
            'NEW_COMMENT'=>'我的回复有了新评论',
            'QUESTION_INVITE'=>'有人邀请我回答问题'
        );
        TPL::assign('template_type', $template_type);
        TPL::assign('template_msg_list', $template_msg_list);
        TPL::assign('menu_list', $this->fetch_menu_list());
        TPL::output('admin/weixin/template_msg');
    }

    /*新增微信通知模板*/
    public function add_template_msg_action()
    {
        $template_type=array(
            'AT_ME'=>'当有人@我',
            'NEW_ANSWER'=>'我的问题有了新回复',
            'NEW_ARTICLE_ANSWER'=>'我的文章有了新评论',
            'NEW_COMMENT'=>'我的回复有了新评论',
            'QUESTION_INVITE'=>'有人邀请我回答问题'
        );
        if(intval($_GET['id']))
        {
            $template_msg_info =$this->model('weixin')->fetch_row('weixin_template','id = '.intval($_GET['id']));
            TPL::assign('template_msg_info', $template_msg_info);
        }
        TPL::assign('template_type', $template_type);
        TPL::assign('menu_list', $this->fetch_menu_list());
        TPL::output('admin/weixin/add_template_msg');
    }
}