<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/15
 * Time: 9:04
 */

namespace app\test\admin;
use app\system\admin\Admin;
use QL\QueryList;
use QL\Ext\CurlMulti;
use think\Db;

class Gather extends Admin
{
    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 采集文章信息
     * @author lyf
     * @date 2019-5-10
     */
    public function index(){
        $param=$this->request->get();
        dump($param);die;
        $ql = QueryList::getInstance();
        $ql->use(CurlMulti::class);

        $htmls = array();
        $map['status'] = 0;
        $html = Db::name('city_cate_url')
            ->where($map)
            ->order("id","ASC")
            ->limit("0","10")
            ->select();
        foreach ($html as $item){
            $url = explode('/',$item['url']);
            $link = explode('.',$url[3]);
            $htmls[] = $url[0].'//'.$url[2].'/'.$link[0].'P'.$item['page'].'.'.$link[1];
        }
        $data = $this->gather($htmls,$ql);

        $ids=array_column($html,"id");
        $res_list=db("city_cate_url")
            ->where("id","in",$ids)
            ->where('status','-1')
            ->select();
        $k=0;
        if($htmls){
            $k++;
            if($res_list){
                $this->success("正在采集第{$k}次","gather/index","",60);
            }else{
                $this->success("正在采集第{$k}次","gather/index","",30);
            }
        }else{
            $this->success("采集成功");
        }

    }

    /**
     * @param $html
     * @param $city_name
     * @param $cate_name
     * @param $num
     * @return string
     * 采集列表信息
     * @author lyf
     * @date 2019-5-8
     */
    public function gather($html,$ql){
        $ql->curlMulti($html)->success(function (QueryList $ql,CurlMulti $curl,$r){
//            echo $r['info']['url'];echo "<br>";
            //初始化
            $content1 = '';
            $content2 = '';
            //获取城市ID
            $city = explode('/',$r['info']['url']);
            $cityId = $this->getCityId($city[0].'//'.$city[2].'/');

            $msg = '';
            $selector1 = "#main";
            $selector2 = "tbody";
            try{
                $content1=$ql->get($r['info']['url'])->find($selector1)->html();
                $content2=$ql->get($r['info']['url'])->find($selector2)->html();

            }catch(\Exception $e) {

            }
            $rules = [
                'title' => ['.t>div>a','title'],
                'original_link' => ['.t>div>a','href'],
                'img' => ['.i>a>img','src'],
                'brief' => ['.t>div','html','-#wrapv1'],
                'price' => ['.p','text'],
                'date' => ['.u','text']
            ];

            if($content1&&$content2){
                //处理数据信息
                $data = $this->dealData($content2,$rules,$ql,$cityId,$r['info']['url']);
                //数据入库
                if($data){
                    $res = $this->saveData($data,$r['info']['url']);
                }
            }
            else if($content1&&!$content2){
                $this->endNow($r['info']['url'],$status='1');
            }else{
                $this->endNow($r['info']['url'],$staus='-1');
            }
            $ql->destruct();

        })->start([
            // 最大并发数，这个值可以运行中动态改变。
            'maxThread' => 10,
            // 触发curl错误或用户错误之前最大重试次数，超过次数$error指定的回调会被调用。
            'maxTry' => 3,
            // 全局CURLOPT_*
            'opt' => [
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_RETURNTRANSFER => true
            ]
        ]);
    }

    /**
     * @param $content
     * @param $rules
     * @return Array
     * 处理数据
     * @author lyf
     * @date 2019-5-6
     */
    public function dealData($content,$rules,$ql,$cityId,$nowurl){
        $datas = array();
        if($content){
            $data = $ql->html($content)->rules($rules)->range('')->queryData();
            foreach($data as $k=>&$item){
                if(@$item['date']){
                    $time = $this->switchDate($item['date']);
                    if($time<strtotime('2018-01-01 00:00:00')){//2018-01-01
                        $this->endNow($nowurl,$status='1');
                        break;
                    }else{
                        $datas[$k]['date'] = $time;
                    }
                }else{
                    $datas[$k]['date'] = '';
                }
                if($item['title']){
                    $datas[$k]['title'] = $item['title'];
                }else{
                    $datas[$k]['title'] = '暂无标题';
                }
                if(@$item['img']){
                    $datas[$k]['img'] = $item['img'];
                }else{
                    $datas[$k]['img'] = "nocover.jpg";
                }
                if(@$item['brief']){
                    $brief = explode('<div',$item['brief']);
                    $brief = explode('<br>',$brief[0]);
                    $datas[$k]['keywords'] = $brief[1];
                    if(@$brief[2]){
                        $datas[$k]['brief'] = $brief[2];
                    }else{
                        $datas[$k]['brief'] = null;
                    }
                }else{
                    $datas[$k]['keywords'] = '';
                    $datas[$k]['brief'] = '';
                }
                if(@$item['price']){
                    $datas[$k]['price'] = $item['price'];
                }else{
                    $datas[$k]['price'] = '';
                }
                if($item['original_link']){
                    $datas[$k]['original_link'] = $item['original_link'];
                }


                $type = explode('/',$item['original_link']);
                if(@$type[2]){
                    $original_id = explode('.',$type[2]);
                }else{
                    $original_id = explode('.',$type[0]);;
                }

                $datas[$k]['original_id'] = $original_id[0];

                if(@$type[1]){
                    $datas[$k]['cid'] = $this->getCategroyId(@$type[1]);
                }else{
                    $datas[$k]['cid'] = 0;
                }
                $datas[$k]['city_id'] = $cityId;


            }
            return $datas;
        }
    }

    /**
     * @param $url
     * @return mixed|string
     * 获取当前URL中的城市ID
     * @author lyf
     * @date 2019-5-21
     */
    public function getCityId($url){
        $res = Db::name('city')->where('original_url',$url)->value('id');
        if($res){
            return $res;
        }else{
            return '0';
        }
    }

    /**
     * @param $val
     * @return mixed
     * 获取数据列表对应的分类ID
     * @author lyf
     * @date 2019-5-7
     */

    public function getCategroyId($val){
        $id = Db::name('categroy')->where('original_id',$val)->value('id');
        if($id){
            return $id;
        }else{
            return 0;
        }
    }

    /**
     * @param $data
     * @return bool
     * 批量插入数据
     * @author lyf
     * @date 2019-5-7
     */
    public function saveData($data,$url){
        foreach ($data as $item){
            $id = Db::name('news')->where('original_id',$item['original_id'])->value('id');
            if($id){
                $this->endNow($url,$status=1);
                break;
            }else{
                $data = [
                    'original_id' => $item['original_id'],
                    'cid' => $item['cid'],
                    'city_id' => $item['city_id']
                ];
                $id = Db::name('news')->insertGetId($data);
                $item['id'] = $id;
                Db::name('news_'.$item['city_id'])->insert($item);
            }
        }
        $this->ContinueNow($url);
        return true;
    }


    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 将城市与分类联合起来作为采集的url
     * @author lyf
     * @date 2019-5-21
     */
    public function unionTable(){
        $url = array();
        $city = Db::name('city')->where('original_url','<>','')->select();
        $category = Db::name('categroy')->where('pid','<>','0')->select();
        foreach ($city as $item){
            foreach($category as $val){
                $link = explode('/',$val['original_link']);
                $url[] = $item['original_url'].$link[1];
            }
        }
        foreach($url as $item){
            $data = [
                'url' => $item,
                'status' => 0
            ];
            $insert = Db::name('city_cate_url')->insert($data);
        }

        echo "写入完成";exit;

    }

    /**
     * @return false|int
     * 将当前时间转换为时间戳
     * @author lyf
     * @date 2019-5-20
     */
    public function switchDate($str=''){
        $date = explode('年',$str);
        if(!@$date[1]){
            $mounth = explode('月',$date[0]);
            if(!@$mounth[1]){
                $str = date('Y年m月d日',time());
            }elseif($date[0]=='12月31日'){
                $str = '2018年'.$date[0];
            }else{
                $str = '2019年'.$date[0];
            }
        }
        $arr = date_parse_from_format('Y年m月d日',$str);
        $time = mktime(0,0,0,$arr['month'],$arr['day'],$arr['year']);
        return $time;
    }

    /**
     * @param string $url
     * @param $data
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 将url表中的status修改，表示当前采集的状态  status为1表示该URL采集完成，-1则为采集中断
     * @author lyf
     * @date 2019-5-21
     */
    public function endNow($url='',$status){
        $map = explode('/',$url);
        $a = explode('.',$map[3]);
        $b = explode('P',$a[0]);
        $link = $map['0'].'//'.$map[2].'/'.$b[0].'.'.$a[1];
        $update = Db::name('city_cate_url')->where('url',$link)->update(['status'=>$status]);
        if($update){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $url
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 更新url表中的page页数  page+1
     * @author lyf
     * @date 2019-5-21
     */
    public function ContinueNow($url){
        $map = explode('/',$url);
        $a = explode('.',$map[3]);
        $b = explode('P',$a[0]);
        $link = $map['0'].'//'.$map[2].'/'.$b[0].'.'.$a[1];
        $res = Db::name('city_cate_url')->where('url',$link)->find();
        $update_data = ['page'=>($res['page']+1)];
        $update = Db::name('city_cate_url')->where('id',$res['id'])->update($update_data);
        if($update){
            return true;
        }else{
            return false;
        }
    }

}