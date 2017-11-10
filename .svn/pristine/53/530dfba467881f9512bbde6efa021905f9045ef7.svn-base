<?php
namespace web\cemetery\controller;
use think\Controller;
use think\Db;
use web\extra\controller\Base;
use think\Cookie;//cookie类
use web\extra\model\Store;

class Ecological extends Base
{
    /*
     * 生态葬
     * @param   void;
     * @return  void;
     */
    public function index(){
        $province = decode(Cookie::get('ip_region_id'));
        $where['province_id'] =  $province;
        $where['recommend'] = config('normal_status');
        $data = Db::name('EcologicalBurial')->where($where)->order('sort DESC')->field('type,store_id,store_sn')->select();
        //初始化数据
        foreach(config('ecology_tombs_type') as $v){
            $dataid[$v] = '';
        }
        foreach($data as $v){
            $dataid[$v['type']][$v['store_id']] = $v['store_id']; 
        }
        foreach($dataid as $k=>$v){
            $storewhere['id'] = array('in',$v);
            $lastdata[$k] = Store::with(['province','city'])->where($storewhere)->limit(3)->field('id,name,thumb_image,image,min_price,province_id,city_id')->select();
        }
        //SEO部分
        $seo = array(
            'seo_title'=>'生态葬-91搜墓网',
            'seo_keywords'=>'节地生态葬,草坪葬,水葬,树葬,花坛葬',
            'seo_description'=>'91搜墓网为您介绍各种生态葬葬式类型及各种墓型结构等相关内容.基本的葬式包括有：
                                海葬、壁葬、树葬、花坛葬、草坪葬、烟花葬......公墓内大致的墓型有：传统墓、艺
                                术墓、家族墓.91搜墓网服务热线：400-618-9191.'
        );
        $this->assign([
            'lastdata'  => $lastdata,
            'seo'       => $seo
        ]);
        return $this->fetch();
    }

    
}