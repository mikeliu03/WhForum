ALTER TABLE `[#DB_PREFIX#]question` ADD COLUMN `set_top` tinyint(1) unsigned DEFAULT '0' COMMENT '是否置顶 0不置顶1置顶';
ALTER TABLE `[#DB_PREFIX#]question` ADD COLUMN `set_top_time` int(11) unsigned DEFAULT '0' COMMENT '置顶时间';
ALTER TABLE `[#DB_PREFIX#]article` ADD COLUMN `set_top` tinyint(1) unsigned DEFAULT '0' COMMENT '是否置顶 0不置顶1置顶';
ALTER TABLE `[#DB_PREFIX#]article` ADD COLUMN `set_top_time` int(11) unsigned DEFAULT '0' COMMENT '置顶时间';
ALTER TABLE `[#DB_PREFIX#]posts_index` ADD COLUMN `set_top` tinyint(1) unsigned DEFAULT '0' COMMENT '是否置顶 0不置顶1置顶';
ALTER TABLE `[#DB_PREFIX#]posts_index` ADD COLUMN `set_top_time` int(11) unsigned DEFAULT '0' COMMENT '置顶时间';
ALTER TABLE `[#DB_PREFIX#]column` ADD COLUMN `recommend`  tinyint(1) UNSIGNED NULL DEFAULT 0 COMMENT '是否推荐' AFTER `reson`;
INSERT INTO `[#DB_PREFIX#]menu` (`title`, `cname`, `url`, `pid`, `unid`, `systerm`, `status`) VALUES ( '微信通知模板', 'wx_template_msg', 'admin/weixin/template_msg/', '43', NULL, '0', '1');
CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]weixin_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` varchar(100) DEFAULT NULL COMMENT '模板ID',
  `template_type` varchar(50) DEFAULT NULL COMMENT '适用类型',
  `template_data` text COMMENT '模板数据',
  PRIMARY KEY (`id`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8mb4;
INSERT INTO `[#DB_PREFIX#]system_setting` ( `varname`, `value`) VALUES ( 'weixin_notify_type', 's:4:"TEXT";');
CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]nav` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `status` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '启用禁用状态 Y启用 N禁用',
  `is_user` tinyint(1) NULL DEFAULT NULL COMMENT '是否登录显示 1是',
  `icon` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `is_index` tinyint(1) NULL DEFAULT 0 COMMENT '是否设置为首页 1是',
  `sort` int(11) DEFAULT '0' COMMENT '排序 数字越大越靠前',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = [#DB_ENGINE#] DEFAULT CHARSET=utf8;
INSERT INTO `[#DB_PREFIX#]nav` VALUES (1, 'home', '动态', 'Y', 0, 'icon icon-home', 0, 0);
INSERT INTO `[#DB_PREFIX#]nav` VALUES (2, 'column', '专栏', 'N', 0, 'icon icon-column', 0, 0);
INSERT INTO `[#DB_PREFIX#]nav` VALUES (3, 'explore', '发现', 'Y', 0, 'icon icon-list', 0, 0);
INSERT INTO `[#DB_PREFIX#]nav` VALUES (4, 'topic', '话题', 'Y', 0, 'icon icon-topic', 0, 0);
INSERT INTO `[#DB_PREFIX#]nav` VALUES (5, 'notifications', '通知', 'Y', 1, 'icon icon-bell', 0, 0);
INSERT INTO `[#DB_PREFIX#]nav` VALUES (6, 'help', '帮助', 'N', 0, 'icon icon-bulb', 0, 0);
INSERT INTO `[#DB_PREFIX#]menu` (`title`, `cname`, `url`, `pid`, `unid`, `status`, `systerm`) VALUES ('前台菜单', '', 'admin/nav/', '36', NULL, '1', '0');

CREATE TABLE IF NOT EXISTS `[#DB_PREFIX#]hooks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '钩子名',
  `function_name` varchar(255) DEFAULT NULL COMMENT '函数名',
  `desc` varchar(255) DEFAULT NULL COMMENT '描述',
  `is_system` tinyint(1) DEFAULT '0' COMMENT '是否系统自带 1是',
  PRIMARY KEY (`id`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;
INSERT INTO `[#DB_PREFIX#]hooks` (`name`, `function_name`, `desc`, `is_system`) VALUES ('问题前置钩子', "hook('question','pre_question',$_GET['params']);", 'hook(插件名称，方法名，参数);问题控制器前置处理，可用于对问题渲染前处理', 0);
INSERT INTO `[#DB_PREFIX#]hooks` (`name`, `function_name`, `desc`, `is_system`) VALUES ('问题后置钩子', "hook('question','after_question',$_GET['params']);", 'hook(插件名称，方法名，参数);问题控制器后置处理，可用于对问题渲染后处理', 0);
INSERT INTO `[#DB_PREFIX#]hooks` (`name`, `function_name`, `desc`, `is_system`) VALUES ('文章前置钩子', "hook('article','pre_article',$_GET['params']);", 'hook(插件名称，方法名，参数);文章控制器前置处理，可用于对文章渲染前处理', 0);
INSERT INTO `[#DB_PREFIX#]hooks` (`name`, `function_name`, `desc`, `is_system`) VALUES ('文章后置钩子', "hook('article','after_article',$_GET['params']);", 'hook(插件名称，方法名，参数);文章控制器后置处理，可用于对文章渲染后处理', 0);
INSERT INTO `[#DB_PREFIX#]hooks` (`name`, `function_name`, `desc`, `is_system`) VALUES ('文章评论钩子', "hook('article','save_comment',$_POST['params']);", 'hook(插件名称，方法名，参数);文章评论处理，可用于对文章评论处理', 0);
INSERT INTO `[#DB_PREFIX#]hooks` (`name`, `function_name`, `desc`, `is_system`) VALUES ('问题回答评论钩子', "hook('question','save_question_answer_comment',$_POST['params']);", 'hook(插件名称，方法名，参数);问题回答评论处理，可用于对问题回答评论处理', 0);
INSERT INTO `[#DB_PREFIX#]hooks` (`name`, `function_name`, `desc`, `is_system`) VALUES ('问题评论钩子', "hook('question','save_question_comment',$_POST['params']);", 'hook(插件名称，方法名，参数);问题评论处理，可用于对问题评论处理', 0);
INSERT INTO `[#DB_PREFIX#]hooks` (`name`, `function_name`, `desc`, `is_system`) VALUES ('问题发布钩子', "hook('question','publish_question',$_POST['params']);", 'hook(插件名称，方法名，参数);问题发布模型处理，可用于对问题发布模型进行内容处理', 0);
INSERT INTO `[#DB_PREFIX#]hooks` (`name`, `function_name`, `desc`, `is_system`) VALUES ('文章发布钩子', "hook('article','publish_article',$_POST['params']);", 'hook(插件名称，方法名，参数);文章发布模型处理，可用于对文章发布模型进行内容处理', 0);
INSERT INTO `[#DB_PREFIX#]menu` (`title`, `cname`, `url`, `pid`, `unid`, `status`, `systerm`) VALUES ('钩子管理', '', 'admin/hooks/', '57', NULL, '1', '0');
