<?php
namespace app\test\admin;
use app\system\admin\Admin;
use app\common\base\TableConfig;
use think\Db;

class Link extends Admin
{
    protected $hisiModel = '';//模型名称[通用添加、修改专用]
    protected $hisiTable = '';//表名称[通用添加、修改专用]
    protected $hisiAddScene = '';//添加数据验证场景名
    protected $hisiEditScene = '';//更新数据验证场景名
  //查询友情链接
    public function index()
    {
        if ($this->request->isAjax()) {

            $map = [];
            $keyword    = $this->request->param('keyword');
            if ($keyword) {
                $map[] = ['title', 'like', "%{$keyword}%"];
            }
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);
            $data['data'] = Db::name(TableConfig::LINK)->where($map)->page($page)->limit($limit)->select();
            $data['count']  = Db::name(TableConfig::LINK)->where($map)->count('id');
            $data['code']   = 0;
            return json($data);
        }
        return $this->fetch();
    }


    public function  add(){
        if ($this->request->isPost()) {
            $param=$this->request->param();
            if (empty($param['title'])==true ||empty($param['link'])==true){
                return $this->error('保存失败');
            }
            $data=[
                'title'=>$param['title'],
                'link'=>$param['link'],
                'time'=>time(),
            ];
            $num=Db::name(TableConfig::LINK)->insert($data);
            if ($num!=1){
                return $this->error('保存失败');
            }
            return $this->success('保存成功');
        }
        return $this->fetch('form');
    }




   //修改
    public function  edit($id=0){
        if ($this->request->isPost()) {
            $param=$this->request->param();
            if (empty($param['title'])==true || empty($param['link'])==true ){
                return $this->error('请填写数据');
            }
            $data=[
                'title'=>$param['title'],
                'link'=>$param['link'],
            ];
            $num=Db::name(TableConfig::LINK)->where('id',$id)->update($data);
            if ($num!=1){
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }
        $row =Db::name(TableConfig::LINK)->where('id', $id)->find();
        $this->assign('data_info', $row);
        return $this->fetch('edit');
    }

//删除
    public function  del(){
        $param=$this->request->param();
        $num=Db::name(TableConfig::LINK)->where('id',$param['id'])->delete();
        if($num!=1){
            return $this->error('删除失败');
        }
        return $this->success('删除成功');
    }



}