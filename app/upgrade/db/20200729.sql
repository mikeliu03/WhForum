DELETE FROM `[#DB_PREFIX#]menu` WHERE  pid=57 and url = 'admin/db_manager/';
DELETE FROM `[#DB_PREFIX#]menu` WHERE  pid=57 and url = 'admin/tools/pay/';
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'mobile_editor', '手机端编辑器', 'system', 1, 1583844198, 0);
INSERT INTO `[#DB_PREFIX#]hook`(`system`, `name`, `intro`, `source`, `status`, `add_time`, `update_time`) VALUES (1, 'm_publish_btn', '发起按钮拓展钩子', 'system', 1, 1583844198, 0);
ALTER TABLE `[#DB_PREFIX#]order_detail` MODIFY COLUMN `relation_type` varchar(100) NULL DEFAULT NULL COMMENT '关联类型';
ALTER TABLE `[#DB_PREFIX#]order_detail` CHANGE COLUMN `time` `add_time` int(10) NULL DEFAULT NULL COMMENT '交易时间';
UPDATE `[#DB_PREFIX#]menu` SET `pid` = '59' WHERE `title` = '短信记录' AND  `url`= 'admin/tools/notes/';
DELETE FROM `[#DB_PREFIX#]menu` WHERE `title`='交易管理' AND pid=0;
DELETE FROM `[#DB_PREFIX#]menu` WHERE `title`='交易流水' AND url='admin/transaction/trading/';
DELETE FROM `[#DB_PREFIX#]menu` WHERE `title`='提现申请' AND url='admin/transaction/apply/';
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES ('login_logo_url', 's:0:"";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES('down_img_to_location_enable','s:1:"N";');
INSERT INTO `[#DB_PREFIX#]system_setting`(`varname`, `value`) VALUES('enable_auto_link', 's:1:"Y";');