INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('cache_type', 's:4:"file";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('enable_remove_xss', 's:1:"Y";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('user_remove_content_enable', 's:1:"N";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('enable_img_box', 's:1:"N";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('db_back_path', 's:7:"backup/";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('db_back_part_size', 's:2:"10";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('db_back_compress', 's:1:"0";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('db_back_compress_level', 's:2:"80";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('site_logo_url', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('mobile_enable', 's:1:"Y";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('web_base_url', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('mobile_base_url', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('cache_host', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('cache_host_port', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('page_nav_num', 's:1:"5";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('enable_preview', 's:1:"Y";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('debug_enable', 's:1:"0";');

DROP TABLE IF EXISTS `[#DB_PREFIX#]hooks`;
UPDATE `[#DB_PREFIX#]menu` SET `url` = 'admin/hook/' WHERE `title` = '钩子管理' AND  `url`= 'admin/hooks/' ;
CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]hook` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `system` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '系统插件',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '钩子名称',
  `intro` varchar(255) NOT NULL DEFAULT '' COMMENT '钩子简介',
  `source` varchar(255) DEFAULT 'system' COMMENT '钩子来源',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=[#DB_ENGINE#] AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='钩子表';

CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]hook_plugins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hook` varchar(32) NOT NULL COMMENT '钩子id',
  `plugins` varchar(32) NOT NULL COMMENT '插件标识',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=[#DB_ENGINE#] AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='钩子-插件对应表';

INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_header', '前端页面公共头部钩子', 'system', 1, 1583843022, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_footer', '前端页面公共底部钩子', 'system', 1, 1583843058, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'question_detail_hook', '问题详情页逻辑处理钩子', 'system', 1, 1583843373, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'article_comment_hook', '文章提交评论逻辑处理钩子', 'system', 1, 1583843928, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'save_question_answer_comment', '问题回复评论逻辑处理钩子', 'system', 1, 1583844120, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'save_question_comment', '问题评论逻辑处理钩子', 'system', 1, 1583844163, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'save_question_answer_hook', '问题回复逻辑处理', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'article_detail_hook', '文章详情页逻辑处理钩子', 'system', 1, 1583844253, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'upload_action_hook', '上传处理钩子', 'system', 1, 1583844295, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'editor', '编辑器插件钩子', 'system', 1, 1583844295, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_user_nav_hook', '前台用户下拉导航钩子', 'system', 1, 1583844295, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_publish_nav_hook', '前台发起下拉导航钩子', 'system', 1, 1583844295, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_sidebar_hook', '前台侧边栏钩子', 'system', 1, 1583844295, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'publish_question_hook', '问题提交发布逻辑处理钩子', 'system', 1, 1583844035, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'publish_article_hook', '文章提交发布逻辑处理钩子', 'system', 1, 1583844079, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'modify_question_hook', '问题修改提交发布逻辑处理钩子', 'system', 1, 1583844035, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'modify_article_hook', '文章修改提交发布逻辑处理钩子', 'system', 1, 1583844079, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'question_answer_vote_hook', '问题回复赞踩后置钩子', 'system', 1, 1583844079, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_publish_footer_hook', '前台发起内容底部按钮钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'app_begin', '控制器实例化执行钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'publish_article_action_hook', '发起文章页面渲染钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'publish_question_action_hook', '发起问题页面渲染钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'cron_hook', '定时任务钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_wrap_hook', '内容页通用顶部底部钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_content_hook', '内容页左侧内容区通用钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_content_operate_hook', '内容页用户操作区通用钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'save_user_register_hook', '用户提交注册逻辑处理钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'save_user_login_hook', '用户提交登陆逻辑处理钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'user_base_profile_setting', '用户基本资料设置钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'save_user_profile_hook', '保存用户基本资料设置钩子', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'page_publish_hook', '发起页面附加字段钩子', 'system', 1, 1583844198, 0);

ALTER TABLE `[#DB_PREFIX#]menu` MODIFY COLUMN `url` varchar(255) NULL DEFAULT NULL;

INSERT INTO `[#DB_PREFIX#]menu` (`title`, `cname`, `url`, `pid`, `unid`, `status`, `systerm`) VALUES ('数据库管理', '', 'admin/db_manager/', '57', NULL, '1', '0');
INSERT INTO `[#DB_PREFIX#]menu` (`title`, `cname`, `url`, `pid`, `unid`, `status`, `systerm`) VALUES ('在线升级', '', 'admin/cloud/', '57', NULL, '1', '0');
INSERT INTO `[#DB_PREFIX#]menu` (`title`, `cname`, `url`, `pid`, `unid`, `status`, `systerm`) VALUES ('操作记录', '', 'admin/tools/action_log/', '55', NULL, '1', '0');

CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]action_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '行为名称',
  `action_ip` bigint(255) DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT '0',
  `remark` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `item_id` int(10) unsigned DEFAULT '0',
  `add_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='行为记录表';
