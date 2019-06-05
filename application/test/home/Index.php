<?php
namespace app\test\home;
use app\common\controller\Common;
use think\App;
use think\Db;

class Index extends Common
{

    public function index()
    {
        $url=$_SERVER['HTTP_HOST'];
        //分类
        $category=Db::name("categroy")->order('id asc')->select();
        $category=$this->demo($category);
        $cate=array();
        foreach($category as $v){
            $cate[$v['tag']][]=$v;
        }
       //友情链接
        $link=$this->friend_link();

        //城市
        $city=$this->city();
//        dump($city[0]);die;
        $this->assign('city',$city[0]);//省级城市分类
        $this->assign('letter',$city[1]);//城市字母分类
        $this->assign('link',$link);
        $this->assign('category',$cate);
        if ($url=="www.g207.com"||$url=="g207.com"){
            $list=Db::name('news')->field('id,city_id')->order('date desc')->limit(0,18)->select();
            foreach ($list as $k=>$v){
                $data=Db::name('news_'.$v['city_id'])->where('id',$v['id'])->find();
                $list[$k]=$data;
            }
            $this->assign('data',$list);
            return $this->fetch('all/index');
        }
        return $this->fetch();
    }
}