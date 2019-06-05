<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14
 * Time: 14:12
 */
namespace app\test\home;
use app\common\controller\Common;

use QL\QueryList;
use think\facade\Cache;
use think\Db;



header("Content-type: text/html; charset=utf-8");
class Arcdetail extends Common {
    /**
     * @param $arc_id
     * @return array|Array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 根据文章ID,获取文章的原始url,采集获取数据
     */
    public function get_one_data(){
        //从缓存中获取文章的缓存数据
        $urls=$_SERVER['HTTP_HOST'];
        $arc_id=$this->request->param('d');
        $ids=Db::name('news')->where('id',$arc_id)->find();
        $cate_id=$ids['cid'];
        $cache_name="arc_detail_".$arc_id;
        $cache_data=Cache::store("arc")->get($cache_name);
//        dump($cache_data);die;
        $return_data=[];
        $first=$this->first();//获取城市id
        if ($urls=="www.g207.com"||$urls=="g207.com"){
         $first=Db::name('city')->where('id',$ids['city_id'])->field('name')->find();
        }
        $url=Db::name('news_'.$ids['city_id'])->where('id',$arc_id)->field("original_link,city_id,title,brief")->find();
        //r如果为空
        if(empty($cache_data)){
            //获取文章的网站详情原始路由url
            if (!$url['brief']){
                $url['brief']=$url['title'];
            }
            $c_url=db("city")->where(["id"=>$url['city_id']])->value("original_url");
//            dump($c_url);die;
            //网站url
            $url_website=$c_url;
            //最终的文章url=网站url+路由url
            $final_url=$url_website.$url['original_link'];
            //替换可能重复的斜杠
            $final_url=str_replace("//","/",$final_url);

            //获取文章的原始ID
            $original_url_list=explode("/",$final_url);
            $original_id=end($original_url_list);
            $original_id=str_replace(".html","",$original_id);

            //开始采集过程,若采集过程中出错则数据为空.
            $ql=QueryList::getInstance();
            $data=[];
            try{
                $data=$ql->get($final_url)
                    ->rules([
                        'view_detail' => ['.view_detail','html','a p'],
                        'memo' => ['.memo','html','a p']
                    ])
                    ->queryData();

            }catch (\Exception $exception){

            }
            //保存文章详情缓存信息
            $this->set_arc_detail_data($original_id,$data);

            if (!$data){
                $return_data[]=["view_detail"=>"","memo"=>""];
            }else{
                $return_data=&$data;
            }


        }else{

            //设置值
            $return_data=&$cache_data;
        }
        //友情链接
        $link=$this->friend_link();
        //城市
        $city=$this->city();

        //导航
        $category=Db::name("categroy")->order('id asc')->select();
        $cate=Db::name("categroy")->where('id',$cate_id)->field('title')->find();
        $category=$this->demo($category);

        $this->assign('city',$city[0]);//省级城市分类
        $this->assign('letter',$city[1]);//城市字母分类
        $this->assign('link',$link);
        $this->assign('title',$url['title']);
        $this->assign('cate_title',$cate['title']);//详细信息上一级
        $this->assign('brief',$url['brief']);
        $this->assign('city_name',$first['name']);
        $this->assign('website_name',config('website_name'));
        $this->assign('data',$return_data);
        $this->assign('category',$category);
        return $this->fetch('view/view');


    }



    /**
     * @param $original_id
     * @param $data
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 根据文章的original_id,更新文章的采集状态和采集日志,并设置文章缓存数据
     */
    public function set_arc_detail_data($original_id,$data){

        $arc_id=db("news")->where(["original_id"=>$original_id])->value("id");

        //找到ID后,判断信息是否为空?
        $is_empty=empty($data);
        //如果为空,把这条ID相关的文章采集状态设置为-1
        if($is_empty==true){
            $up_data=[
                "arc_detail_collect_status"=>-1,
                "arc_detail_collect_log"=>"文章获取信息为空"
            ];
//            db("news")->where(["id"=>$arc_id])->update($up_data);
        }

        //不为空,设置缓存信息
        //然后根据文章ID,把这条数据的文章采集状态 设置为1
        if($is_empty!==true){
            $cache_name="arc_detail_".$arc_id;
            Cache::store("arc")->set($cache_name,$data);
            $up_data=[
                "arc_detail_collect_status"=>1,
                "arc_detail_collect_log"=>"文章采集成功,cache名称为:".$cache_name
            ];
//            db("news")->where(["id"=>$arc_id])->update($up_data);

        }

        unset($data);



    }







}