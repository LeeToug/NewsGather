<?php
namespace app\test\home;
use app\common\controller\Common;
use think\Db;
use think\Paginator;

class Lists extends Common
{

    //小分类列表
    public function index()
    {
        $url=$_SERVER['HTTP_HOST'];

            if ($this->request->param('d')){
                $id=$this->request->param('d');
                $data=$this->request->param('title');
                $where=['pid'=>$id];
                $list_title=Db::name('categroy')->where('id',$id)->field('title')->find();//查询标题
                $list_s=Db::name('categroy')->where($where)->field('id,title')->select();//查询二级分类
                $list=Db::name('categroy')->where($where)->column('id');
                $lists=implode(",",$list);

                  if ($url=="www.g207.com"||$url=="g207.com"){
                    //总站
                    $list_all=$this->select_a($lists);
                }else{
                    //子站
                    $list_all = $this->select_s($lists, $data);
                }
                $pages = $list_all->render();
                $this->assign('d',$id);
       
                $this->assign('title', $list_title['title']);
                $this->assign('pages', $pages);
                $this->assign('category',$list_s);
                $this->assign('list',$list_all);
            }

        //友情链接
        $link=$this->friend_link();
        //城市
        $city=$this->city();
        $this->assign('city',$city[0]);//省级城市分类
        $this->assign('letter',$city[1]);//城市字母分类
        $this->assign('link',$link);
        if ($url=="www.g207.com"||$url=="g207.com"){
            return $this->fetch('all/c_list');
        }
        return $this->fetch('c_list');
    }


  //标签列表
    public function Label()
    {
        $url=$_SERVER['HTTP_HOST'];
        if ($this->request->param('d')){
            $first=$this->first();
            $id=$this->request->param('d');

            if($id){
                $list_two_title=Db::name('categroy')->where('id',$id)->field('title,pid')->find();//查询二级标题
                $list_one_title=Db::name('categroy')->where('id',$list_two_title['pid'])->field('title')->find();//查询一级标题
            }

            if ($url=="www.g207.com"||$url=="g207.com"){
                //总站
                $list=Db::name('news')->whereIn('cid',$id)->limit('id,city_id')->whereTime('date', '>=', date(strtotime("-1  month")))->paginate(21,false,['query' =>request()->param()]);
                foreach ($list as $k=>$v){
                    $data=Db::name('news_'.$v['city_id'])->where('id',$v['id'])->find();
                    $list[$k]=$data;
                }
                $this->assign('d',$id);
                $this->assign('data',$list);

            }else{
               //子站
                if ($id=='index'){
                    $index_search=$this->request->param('category');
                    $category=Db::name('categroy')->where('title','like',"%{$index_search}%")->field('id')->find();
                    $id=$category['id'];
                }

                $all=$this->request->param();
                unset($all['/lists/label/d/'.$id.'_html'],$all['/lists/label'],$all['d'],$all['page'],$all['/lists/index'],$all['category'],$all['title']);
                if ($all){
                    $all_s=[];
                    $all_t=[];
                    foreach ($all as $k=>$v){
                        $all_s[]=$v;
                        if ($v){
                            $all_t[]='%'.$v.'%';
                        }

                    }
                }
                //大标签
                $label=Db::name('tag_relation')->alias('a')->join('tag_map b','a.tag_map_id=b.id')
                    ->where('a.cate_id',$id)->field('b.name,b.table_name,b.id,b.pinyin')->select();
                $arl=array(['name'=>'区域','pinyin'=>"quyu","id"=>$first['id']]);
                $label=array_merge($arl,$label);

                if(!empty($label)){
                    $search='';
                    $label_m=array();
                    $not=array();
                    foreach ($label  as $k=>$v){
                        $num=substr( $v['pinyin'], 0, 1 );
                        $not[$k]['pinyin']=$v['pinyin'];
                        $search.=$v['pinyin'].'=0&';//搜索条件
//                  $search.=$num.'0_';//搜索条件
                        if ($v['pinyin']!='quyu')  {
                            $label_m[]=Db::name($v['table_name'])->select();//小标签
                        }else{
                            $label_m[]=Db::name('city')->where('pid',$v['id'])->select();//小标签
                        }

                        foreach ($label_m[$k] as $ke=>$va){
                            $label_m[$k][$ke]['pinyin']=$v['pinyin'];
                            if ($all){
                                $label_m[$k][$ke]['search']=$all_s[$k];
                                $not[$k]['search']=$all_s[$k];
                            }else{
                                $label_m[$k][$ke]['search']='0';
                                $not[$k]['search']='0';
                            }
                        }

                    }
                }
                //拼接标签
                if ($all){
                    $if=strstr($_SERVER['REQUEST_URI'],"page");
                    if ($if!=false){
                        $one=substr($_SERVER['REQUEST_URI'],strpos($_SERVER['REQUEST_URI'],"page"));
                        $_SERVER['REQUEST_URI']=str_replace($one,'page=1',$_SERVER['REQUEST_URI']);
                        $search=urldecode($_SERVER['REQUEST_URI']);
                    }else{
                        $search="label?d={$id}&".$search;
                    }
                }else{
                    $search=$_SERVER['REQUEST_URI']."&".$search;
                }


                //数据
                $where=['cid'=>$id];
                $data=$this->request->param('title');

                if (!empty($all_t)){
                    $a_where[]=['keywords','like',$all_t];
                }else{
                    $a_where="";
                }
                $list_s=Db::name('news_'.$first['id'].'')->where($where)
                    ->where('title','like',"%{$data}%")
                    ->where($a_where)
                    ->paginate(21,false,['query' => request()->param()]);

                $this->assign('d',$id);
                $this->assign('list',$list_s);//数据列表
                $this->assign('not',$not);//条件不限
                $this->assign('search',$search);//上一次搜索条件
                $this->assign('label_s',$label);//大标签
                $this->assign('label_m',$label_m);//小标签
            }

        }
        //导航
        $category=Db::name("categroy")->order('id asc')->select();
        $category=$this->demo($category);

        //友情链接
        $link=$this->friend_link();
        //城市
        $city=$this->city();
        $this->assign('city',$city[0]);//省级城市分类
        $this->assign('letter',$city[1]);//城市字母分类
        $this->assign('link',$link);//友情链接
        $this->assign('title',$list_two_title['title']);//二级标题
        $this->assign('one',$list_one_title['title']);//一级标题
        $this->assign('category',$category);//网站导航
        if ($url=="www.g207.com"||$url=="g207.com"){
            return $this->fetch('all/list');
        }
        return $this->fetch('list');
    }



}