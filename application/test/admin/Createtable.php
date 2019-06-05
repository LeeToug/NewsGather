<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/20
 * Time: 9:37
 */

namespace app\index\controller;


use think\Controller;
use think\Db;

class Createtable extends Controller
{
    /**
     * 信息列表水平分表代码
     * @author lyf
     * @date 2019-5-10
     */
    public function index(){
        $city = Db::name('hisi_city')->where('original_url','<>','')->column('id');
        foreach($city as $item){
            $sql = "DROP table IF EXISTS hisi_news_{$item}";
            Db::execute($sql);
            $sql1="CREATE TABLE `hisi_news_{$item}` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `title` varchar(255) DEFAULT NULL COMMENT '标题',
                  `img` varchar(255) DEFAULT NULL COMMENT '图片',
                  `brief` varchar(255) DEFAULT NULL COMMENT '描述/简介',
                  `price` varchar(50) DEFAULT NULL COMMENT '价格',
                  `date` varchar(20) DEFAULT NULL COMMENT '日期',
                  `original_link` varchar(50) DEFAULT NULL COMMENT '原始链接',
                  `original_id` varchar(50) DEFAULT NULL COMMENT '原始ID',
                  `keywords` varchar(50) DEFAULT NULL COMMENT '关键词',
                  `cid` int(11) DEFAULT NULL COMMENT '所属分类',
                  `city_id` varchar(255) DEFAULT NULL COMMENT '城市ID',
                  PRIMARY KEY (`id`),
                  KEY `cid` (`cid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='信息列表';";
            Db::execute($sql1);
        }
        foreach ($city as $item){
            $sql = "insert into hisi_news_{$item}(id,title,img,brief,price,date,original_link,original_id,keywords,cid,city_id) select id,title,img,brief,price,date,original_link,original_id,keywords,cid,city_id from hisi_news where city_id%{$item}=0;";
            Db::execute($sql);
        }
    }
}
