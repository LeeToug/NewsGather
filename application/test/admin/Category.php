<?php
namespace app\test\admin;
use app\system\admin\Admin;
use app\common\base\TableConfig;
use think\Db;

class Category extends Admin
{
    protected $hisiModel = '';//模型名称[通用添加、修改专用]
    protected $hisiTable = '';//表名称[通用添加、修改专用]
    protected $hisiAddScene = '';//添加数据验证场景名
    protected $hisiEditScene = '';//更新数据验证场景名
  //查询分类列表
    public function index()
    {
            $data= Db::name(TableConfig::CATEGORY)->select();
            $data=$this->demo($data);
            $this->assign('data',$data);
            $this->assign('hisiTabType', 4);
        return $this->fetch();
    }


    public function  edit($id=0){
        if ($this->request->isPost()) {
            $param=$this->request->param();
            if (empty($param['title'])==true ){
                return $this->error('请填写数据');
            }
            $data=[
                'title'=>$param['title'],
            ];
            $num=Db::name(TableConfig::CATEGORY)->where('id',$id)->update($data);
            if ($num!=1){
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }
        $row =Db::name(TableConfig::CATEGORY)->where('id', $id)->find();
        $this->assign('data_info', $row);
        return $this->fetch('edit');
    }



}