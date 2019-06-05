<?php
/**
 * 分类信息
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/6
 * Time: 14:21
 */

namespace app\index\controller;
use app\system\admin\Admin;
use QL\QueryList;
use think\Db;

class Categroy extends Admin
{
    /**
     * @return string
     * 采集分类
     * @author lyf
     * @date 2019-5-6
     */
    public function index()
    {
        $msg = '';
        $num = 0;
        //要采集的地址
        $html = "http://bj.ohqly.com";
        //选择大的区域
        $selector = "body";
        //获取当前所选区域html
        $content=QueryList::get($html)->find($selector)->html();
        //最外层规则筛选
        $rules = array(
            'plist' => ['dt','html']
        );
        //获取分类数据、处理/采集数据
        $data = $this->dealData($content,$rules);
        //将获取到的数据插入到数据库中
        foreach ($data as $i=>&$item){
            foreach ($item['plist'] as $k=>$v){
                preg_match_all("/[\x{4e00}-\x{9fa5}]|\\//u", $v['title'], $x);
                $v['title'] = implode("",$x[0]);
                $v['tag'] = $i;//给每一个大分类加标签，标签相同表示同属一个div块
                //暂时过滤掉分类为 更多 的相关数据
                if(strpos($v['title'],'更多')===false){
                    //入库一级分类
                    $res = $this->saveCategroy($v);
                    if($res){
                        $num++;
                    }
                }
            }
        }
        if($num>0){
            $msg .= "一级分类采集完成<br>";
        }
        //采集二级分类
        $res = $this->getSecondCate();
        if($res){
            $msg .= "二级分类采集完成";
        }

        return $msg;
    }

    /**
     * @return int|string
     * 获取二级分类信息
     * @author lyf
     * @date 2019-5-10
     */
    public function getSecondCate(){
        $cate = Db::name('categroy')->column('id,original_link');
        foreach ($cate as $id=>$item){
            //要采集的地址
            $html = "http://bj.ohqly.com".$item;
            //选择大的区域
            $selector = "body";
            //获取当前所选区域html
            $content=QueryList::get($html)->find($selector)->html();
            //最外层规则筛选
            $rules = array(
                'title' => ['.main_search_category_wrap>a','text'],
                'original_link' => ['.main_search_category_wrap>a','href']
            );
            //获取分类数据、处理/采集数据
            $data = $this->dealSecondData($content,$rules,$id);
            //入库
            $res = Db::name('categroy')->insertAll($data);
        }
        return $res;
    }

    /**
     * @param $content
     * @param $rules
     * @param $pid
     * @return Array
     * 处理二级分类信息
     * @author lyf
     * @date 2019-5-8
     */
    public function dealSecondData($content,$rules,$pid){
        $data = QueryList::html($content)->rules($rules)->range('')->queryData();
        foreach ($data as &$item){
            $original_id = explode('.',$item['original_link']);
            $item['original_id'] = ltrim($original_id[0],'/');
            $item['pid'] = $pid;
        }
        return $data;
    }

    /**
     * @param $val
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 将数据插入分类表中
     * @author lyf
     * @date 2019-5-6
     */
    public function saveCategroy($val){
        $original_id = explode('.',$val['original_link']);
        $val['original_id'] = ltrim($original_id[0],'/');
        if($val['title'] == '车辆买卖'){
            $val['title'] = '车辆服务';
        }
        $category = Db::name('categroy')->where('original_id',$val['original_id'])->find();
        if($category==null){
            $id = Db::name('categroy')->insertGetId($val);
            if($id){
                return $id;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }

    /**
     * @param $content
     * @param $rules
     * @return Array
     * 采集数据
     * @author lyf
     * @date 2019-5-6
     */
    public function dealData($content,$rules){
        $data = QueryList::html($content)->rules($rules)->range('')->queryData(function ($item){
            $item['plist'] = QueryList::html($item['plist'])->rules(array(
                'title' => array('a','text'),
                'original_link' =>array('a','href')
            ))->range('')->queryData();
            return $item;
        });
        return $data;
    }



}