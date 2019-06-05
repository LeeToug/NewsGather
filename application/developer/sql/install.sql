/*
 sql安装文件
*/
CREATE TABLE `hisiphp_developer_versions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '类型：1模块，2插件，3模板',
  `app_name` varchar(100) NOT NULL DEFAULT '' COMMENT '应用code',
  `app_version` varchar(30) NOT NULL DEFAULT '' COMMENT '应用版本号',
  `update_log` text COMMENT '应用更新日志',
  `update_file` text COMMENT '更新文件记录',
  `delete_file` text COMMENT '删除文件记录',
  `update_sql` text COMMENT '更新sql',
  `install_package` varchar(255) DEFAULT '' COMMENT '安装包',
  `upgrade_package` varchar(255) DEFAULT '' COMMENT '升级包',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '发布状态：1已发布，0待发布',
  `ctime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='[开发助手] 版本记录';