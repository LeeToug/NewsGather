<?php
namespace app\test\admin;
use app\system\admin\Admin;
use app\common\base\TableConfig;
use think\Db;

class Label extends Admin
{
    protected $hisiModel = '';//模型名称[通用添加、修改专用]
    protected $hisiTable = '';//表名称[通用添加、修改专用]
    protected $hisiAddScene = '';//添加数据验证场景名
    protected $hisiEditScene = '';//更新数据验证场景名


    //大标签
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);
            $map = array();
            $keyword= $this->request->param("keyword");
            $search= $this->request->param("search");
            if ($search) {
                $map[]= ['b.name', 'like', "%{$search}%"];
            }
            if ($keyword) {
                $map= ['a.cate_id'=>$keyword];
            }
            $data['data'] = Db::name(TableConfig::RELATION)->alias("a")
                ->join("tag_map b","a.tag_map_id=b.id","left")
                ->join("categroy c","a.cate_id=c.id","left")
                ->where($map)->page($page)->limit($limit)->field('b.name,b.table_name,a.id,c.title')->select();
            $data['count']  = Db::name(TableConfig::RELATION)->alias("a")->join("tag_map b","a.tag_map_id=b.id","left")
                ->where($map)->count('a.id');
            $data['code']   = 0;
            return json($data);
        }
        $keyword= $this->request->param("keyword");
        $this->assign("id",$keyword);
        return $this->fetch();
    }



    public function label_s()
    {
        if ($this->request->isAjax()) {
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);
            $map = [];
            $keyword= $this->request->param("keyword");
            $search= $this->request->param("search");
            if ($search) {
                $map[]= ['name', 'like', "%{$search}%"];
            }
            $data['data'] = Db::name($keyword)
                ->where($map)->page($page)->limit($limit)->select();

            $data['count']  = Db::name($keyword) ->where($map)->count('id');
            $data['code']   = 0;
            return json($data);
        }
        $keyword= $this->request->param("keyword");
        $this->assign("id",$keyword);
        return $this->fetch("labels");
    }



}