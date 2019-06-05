<?php
// +----------------------------------------------------------------------
// | HisiPHP框架[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.HisiPHP.com
// +----------------------------------------------------------------------
// | HisiPHP承诺基础框架永久免费开源，您可用于学习和商用，但必须保留软件版权信息。
// +----------------------------------------------------------------------
// | Author: 橘子俊 <364666827@qq.com>，开发者QQ群：50304283
// +----------------------------------------------------------------------

namespace app\common\controller;
use app\common\base\TableConfig;
use think\App;
use think\Db;
use View;
use think\Controller;

/**
 * 框架公共控制器
 * @package app\common\controller
 */
class Common extends Controller
{

    protected function initialize() {

            $common_r_c=$_SERVER['REQUEST_URI'];
            $city_first=$this->first();
            $this->assign('city_first',$city_first);
            $this->assign('common_r_c',$common_r_c);
        $this->assign('site_titles',config('base.site_titles'));//首页副标题
        $this->assign('lists_title',config('base.lists_title'));//列表页副标题
        $this->assign('list_title',config('base.list_title'));//内容页副标题
        $this->assign('site_title',config('base.site_title'));//网站标题
        $this->assign('site_keywords',config('base.site_keywords'));//网站关键词
        $this->assign('site_description',config('base.site_description'));//网站描述
    }



    //获取城市地址和id
    public  function  first(){
        $url=$_SERVER['HTTP_HOST'];
        $city_first=Db::name(TableConfig::CITY)->where(['now_url'=>$url])->field('name,id')->find();
        return $city_first;
    }

    /**
     * 解析和获取模板内容 用于输出
     * @param string    $template 模板文件名或者内容
     * @param array     $vars     模板输出变量
     * @param array     $replace 替换内容
     * @param array     $config     模板参数
     * @param bool      $renderContent     是否渲染内容
     * @return string
     * @throws Exception
     * @author 橘子俊 <364666827@qq.com>
     */
    final protected function fetch($template = '', $vars = [], $replace = [], $config = [], $renderContent = false)
    {
        if (defined('IS_PLUGINS')) {
            return self::pluginsFetch($template , $vars , $replace , $config , $renderContent);
        }
        return parent::fetch($template , $vars , $replace , $config , $renderContent);
    }
    
    /**
     * 渲染插件模板
     * @param string    $template 模板文件名或者内容
     * @param array     $vars     模板输出变量
     * @param array     $replace 替换内容
     * @param array     $config     模板参数
     * @param bool      $renderContent     是否渲染内容
     * @return string
     * @throws Exception
     * @author 橘子俊 <364666827@qq.com>
     */
    final protected function pluginsFetch($template = '', $vars = [], $replace = [], $config = [], $renderContent = false)
    {
        $plugin     = $_GET['_p'];
        $controller = $_GET['_c'];
        $action     = $_GET['_a'];
        if (!$template) {
            $template = $controller.'/'.$action;
        } elseif (strpos($template, '/') == false) {
            $template = $controller.'/'.$template;
        }
        
        if(defined('ENTRANCE') && ENTRANCE == 'admin') {
            $template = 'admin/'.$template;
        } else {
            $template = 'home/'.$template;
        }

        $template_path = strtolower("../plugins/{$plugin}/view/{$template}.".config('template.view_suffix'));
        return parent::fetch($template_path, $vars, $replace, $config, $renderContent);
    }


    //搜索城市
    public function city_r(){
        $city=$this->request->param('citys');
        if ($city=="/"){
            $r_url="";
        }else{
            $r_url=$city;
        }
        $param=$this->request->param('city');
        $now_url=Db::name("city")->where("name",$param)->find();
        if (empty($now_url['now_url'])){
            $this->error("搜索城市不存在");
        }else{
            $this->redirect('http://'.$now_url['now_url'].$r_url.'',301);

        }
    }



    //友情链接
    public function  friend_link(){
        $city=Db::name("link")->order('id asc')->select();
        return $city;
    }

    //城市地址
    public function  city(){

        if ($_SERVER['REQUEST_URI']=="/"){
            $r_url="";
        }else{
            $r_url=$_SERVER['REQUEST_URI'];
            $one=substr($r_url,strpos($r_url,"page"));
            $page=strpos($r_url, "page");
            if ($page!=false){
                $r_url=str_replace($one,'page=1',$r_url);
            }
            $detail=strpos($r_url, "arcdetail");
            if ($detail!=false){
                $r_url="";
            }
        }
        //省级城市分类
        $city=Db::name("city")->order('id asc')->select();
        foreach ($city as $k=>$v){
            if (!empty($r_url)){
                $city[$k]['now_url']=$v['now_url'].$r_url;
            }
        }
        $city=$this->demo($city);

        //城市字母分类
        $citys=Db::name("city")->order('initial_letter asc')->select();
        $newArray=array();

        foreach($citys as $k=>$v){
            $v['now_url']=$v['now_url'].$r_url;
            $newArray[$v['initial_letter']][]=$v;

        }
        return array($city,$newArray);
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



 //子站
    public function  select_s($where,$where_f){
        $first=$this->first();
        $list=Db::name('news_'.$first['id'].'')->whereIn('cid',$where)->where('city_id',$first['id'])->where('title','like',"%{$where_f}%")->paginate(21,false,['query' =>request()->param()]);
        return $list;

    }

    //总站

    public function  select_a($where){
        $list=Db::name('news')->whereIn('cid',$where)->field('id,city_id')->where('date', '>=', strtotime("-1 month"))->paginate(21,false,['query' =>request()->param()]);
       
        foreach ($list as $k=>$v){
            $data=Db::name('news_'.$v['city_id'])->where('id',$v['id'])->find();
            $list[$k]=$data;
        }
        return $list;

    }
}
