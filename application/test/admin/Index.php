<?php
namespace app\test\admin;
use app\system\admin\Admin;
use app\common\base\TableConfig;
use think\Db;

class Index extends Admin
{
    protected $hisiModel = '';//模型名称[通用添加、修改专用]
    protected $hisiTable = '';//表名称[通用添加、修改专用]
    protected $hisiAddScene = '';//添加数据验证场景名
    protected $hisiEditScene = '';//更新数据验证场景名
    //城市
    public function index()
    {

        if ($this->request->isAjax()) {
            $map = [];
            $keyword    = $this->request->param('keyword');
            if ($keyword) {
                $map[] = ['name', 'like', "%{$keyword}%"];
            }
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);

            $data['data'] = Db::name(TableConfig::CITY)->where($map)->page($page)->limit($limit)->select();
            $data['count']  = Db::name(TableConfig::CITY)->where($map)->count('id');
            $data['code']   = 0;
            return json($data);
        }
        return $this->fetch();
    }


    public function status()
    {
        $id         = $this->request->param('id/a');
        $val        = $this->request->param('val/d');
        $map        = [];
        $map['id']  = $id;
//        $rows       = Db::name(TableConfig::CITY)->where($map)->field('id,system')->select();
        $res = Db::name(TableConfig::CITY)->where($map)->setField('status', $val);;
        if ($res === false) {
            return $this->error('操作失败');
        }

        return $this->success('操作成功');
    }


    public function friend_link()
    {
        if ($this->request->isAjax()) {
            $map = [];
            $keyword    = $this->request->param('keyword');
            if ($keyword) {
                $map[] = ['name', 'like', "%{$keyword}%"];
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


    public function  cs(){
        $im=Db::name(TableConfig::CITY)->where('original_url',"neq"," ")->select();

        foreach ($im as $k=>$v){
            if (!empty($v['original_url'])){
                $b = mb_strpos($v['original_url'],"http://") + mb_strlen("http://");
                $e = mb_strpos($v['original_url'],".ohqly") - $b;
                $data=mb_substr($v['original_url'],$b,$e);
                $data=''.$data.'.g207.com';
                Db::name(TableConfig::CITY)->where('original_url',$v['original_url'])->update(['now_url'=>$data]);
//
            }
        }
//        dump($data);die;
//        dump($im);die;

    }

}