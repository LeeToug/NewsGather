<?php
namespace app\test\admin;
use app\system\admin\Admin;
use app\common\base\TableConfig;
use app\system\admin\Config;
use think\Db;

class Data extends Admin
{
    protected $hisiModel = '';//模型名称[通用添加、修改专用]
    protected $hisiTable = '';//表名称[通用添加、修改专用]
    protected $hisiAddScene = '';//添加数据验证场景名
    protected $hisiEditScene = '';//更新数据验证场景名


    public function  common(){
        $city=Db::name(TableConfig::CITY)->where("original_url","neq"," ")->select();//城市
        $cate=Db::name(TableConfig::CATEGORY)->select();//分类
        $cate=$this->demo($cate);
        return ['city'=>$city,'cate'=>$cate];

    }


    public function index()
    {
        if ($this->request->isAjax()) {
            $map = [];
            $map_s = [];
            $keyword    = $this->request->param('keyword');
            $cate    = $this->request->param('cate');
            if (!$keyword) {
                $keyword = 4267;
            }
            if ($cate) {
                $map_s = ['a.cid'=>$cate];
            }else{
                $map_s = [];
            }

            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);
            $data['data'] = Db::name('news_'.$keyword)->alias("a")
                ->join("city b","a.city_id=b.id","left")
                ->join("categroy c","a.cid=c.id","left")
                ->where($map_s)
                ->page($page)->limit($limit)->field('a.id,a.city_id,b.name,a.title,a.brief,a.price,a.date,c.title as c_title')->select();
            foreach ($data['data'] as $k=>$v){
                $data['data'][$k]['date']=date("Y-m-d",$v['date']);
                if ($v['price']=="?"){
                    $data['data'][$k]['price']="";
                }
            }
            $data['count']  = Db::name('news_'.$keyword)->alias('a')->where($map)->where($map_s)->count('id');
            $data['code']   = 0;
            return json($data);
        }

        $common=$this->common();
        $this->assign('city',$common['city']);
        $this->assign('cate',$common['cate']);
        $jaeger=config('jaeger_url');
        $this->assign('jaeger_url',$jaeger);
        return $this->fetch();
    }



    //修改
    public function  edit($id=0,$city_id=0){
        if ($this->request->isPost()) {
            $param=$this->request->param();
            if (empty($param['title'])==true || empty($param['brief'])==true|| empty($param['price'])==true || empty($param['cid'])==true ){
                return $this->error('请填写数据');
            }
            $data=[
                'title'=>$param['title'],
                'brief'=>$param['brief'],
                'price'=>$param['price'],
                'city_id'=>$param['city_id'],
                'cid'=>$param['cid'],
            ];
            $num=Db::name('news_'.$city_id)->where('id',$id)->update($data);
            if ($num!=1){
                return $this->error('修改失败');
            }
            return $this->success('修改成功');
        }

        $row =Db::name('news_'.$city_id)->where('id', $id)->find();
        $common=$this->common();
        $this->assign('city',$common['city']);
        $this->assign('cate',$common['cate']);
        $this->assign('data_info', $row);
        return $this->fetch('edit');
    }

//删除
    public function  del(){
        $param=$this->request->param();
        $num=Db::name('news_'.$param['city_id'])->where('id',$param['id'])->delete();
        if($num!=1){
            return $this->error('删除失败');
        }
        return $this->success('删除成功');
    }

}