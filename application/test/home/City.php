<?php
namespace app\test\home;
use app\common\controller\Common;
use think\Db;

class City extends Common
{

        public function index(){
            ini_set('max_execution_time','0');
        $city = Db::name('city')->where('original_url','<>','')->column('id');
        foreach($city as $item){
            $sql = "DROP table IF EXISTS hisi_news_{$item}";
            Db::execute($sql);
            $sql1="CREATE TABLE `hisi_news_{$item}` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) DEFAULT NULL COMMENT '����',
              `img` varchar(255) DEFAULT NULL COMMENT 'ͼƬ',
              `brief` varchar(255) DEFAULT NULL COMMENT '����/���',
              `price` varchar(50) DEFAULT NULL COMMENT '�۸�',
              `date` varchar(20) DEFAULT NULL COMMENT '����',
              `original_link` varchar(50) DEFAULT NULL COMMENT 'ԭʼ����',
              `original_id` varchar(50) DEFAULT NULL COMMENT 'ԭʼID',
              `keywords` varchar(50) DEFAULT NULL COMMENT '�ؼ���',
              `cid` int(11) DEFAULT NULL COMMENT '��������',
              `city_id` varchar(255) DEFAULT NULL COMMENT '����ID',
              PRIMARY KEY (`id`),
              KEY `cid` (`cid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='��Ϣ�б�';";
            Db::execute($sql1);
        }
        foreach ($city as $item){
            $sql = "insert into hisi_news_{$item}(id,title,img,brief,price,date,original_link,original_id,keywords,cid,city_id) select id,title,img,brief,price,date,original_link,original_id,keywords,cid,city_id from hisi_news where city_id%{$item}=0;";
            Db::execute($sql);
        }

//        return json($mp);
    }

    function demo($arr,$id=0,$level=0)
    {
        $list =array();
        foreach ($arr as $k=>$v){
            if ($v['pid'] == $id){
                $v['level']=$level;
                $v['son'] = $this->demo($arr,$v['id'],$level+1);
                $list[] = $v;
            }
        }
        return $list;
    }


}