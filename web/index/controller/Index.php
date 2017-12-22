<?php
namespace web\index\controller;
use think\Controller;
use web\extra\controller\Base;
use think\Db;
use web\extra\model\Store;
use think\Cookie;//cookie类

/**
 * 首页控制器
 * author heqingyu;
 * date   17/7/11 13:30:00;
 */
class Index extends Base{  
    /*
     * 首页
     * @param   void;
     * @return  void;
     */
    public function index()
    {
        $provinceId = decode(cookie('ip_region_id'));
        //SEO
        $seo = $this->getseo(config('seo_type.cemetery_home'));
        $this->assign('seo',$seo);
        //轮播图
        $banner = $this->getadvertising($provinceId,config('web_banner'),$num=config('web_banner_num'));
        $this->assign('banner',$banner);
        //风景优美
        $beautiful = $this->getstoredata($provinceId,config('category_cemetery_id'),config('recommend_msg')['beautiful'],$num=6);
        $this->assign('beautiful',$beautiful);
        //dump('ok');die;
        //大家都在看
        $allview = $this->getstoredata($provinceId,config('category_cemetery_id'),config('recommend_msg')['allview'],$num=6);
        $this->assign('allview',$allview);
        //风水优越
        $fengshui = $this->getstoredata($provinceId,config('category_cemetery_id'),config('recommend_msg')['fengshui'],$num=6);
        $this->assign('fengshui',$fengshui);
        //礼仪公司  
        $etiquette = $this->getetiquettedata($provinceId=0,$num=3);
        $this->assign('etiquette',$etiquette);
        //生态葬
        $ecological  = Db::name('EcologicalTable')->where('status',config('normal_status'))->field('name,bill_image,introduce')->order('sort DESC')->select();
        $this->assign('ecological',$ecological);
        //风水文化
        $culture = $this->article(config('article_fengshui_culture'),6);
        $this->assign('culture_category_id',config('article_fengshui_culture'));
        $this->assign('culture',$culture);
        //常见问题(白事常识)
        $sense = $this->article(config('article_sense'),6);
        $this->assign('sense_category_id',config('article_sense'));
        $this->assign('sense',$sense);
        //最新咨询
        $news = $this->article(config('article'),6);
        $this->assign('news_category_id',config('article'));
        $this->assign('news',$news);
        //友情链接
        $this->getfriendlink();
        return $this->fetch();
    }
     /**
     * 获取广告位的图片
     * 如果在他自己的广告位上获取不到图片或则数量不够，则剩余的获取共享图片
     * 以自己位置上的图片为准
     *
     * @param int $positionId 广告位置ID
     * @param int $provinceId 省份ID
     * @param number $num  获取的数量
     *
     * @return array;
     */
    public function getadvertising($provinceId,$positionId,$num=1) {
        if (!empty($positionId)) {
            $where['ad_position_id'] = $positionId;
            $where['status'] = config('normal_status');
            $where['banner_type'] = config('banner_type_msg.pc');
            $where['share'] = config('share_status');
            $where['province_id'] = config('china_num');
            $fields = array('banner_name, banner_url, banner_link,share');
            $sharedata = Db::name('AdvertisingBanner')->field($fields)->where($where)->order('sort DESC')->limit($num)->select();
            //dump($sharedata);
            $datanum = $num - count($sharedata);
            if($datanum > 0){
                $rewhere['province_id'] = $provinceId;
                $rewhere['ad_position_id'] = $positionId;
                $rewhere['status'] = config('normal_status');
                $rewhere['banner_type'] = config('banner_type_msg.pc');
                $redata = Db::name('AdvertisingBanner')->field($fields)->where($rewhere)->order('sort DESC')->limit($datanum)->select();
                //dump($redata);die;
            }
            foreach($redata as $val){
                $sharedata[] = $val;
            }
            return $sharedata;
        }//end if  $positionId
    }
    
    
    /*
     * 获取商家数据
     * @param  $provinceId;//省份ID
     * @param  $storecategory;//商家分类ID
     * @param  $feature;//显示位置
     * @param  $num;//查找数量
     * return  array;//返回数组
     */
    private function getstoredata($provinceId,$storecategory,$feature,$num=6){
        $data = array();
        //查找推荐ID
        $recommendWhere['province_id'] = $provinceId;
        $recommendWhere['feature'] = $feature; 
        $recommendWhere['category_id'] = $storecategory; 
        $recommend = Db::name('StoreRecommend')->where($recommendWhere)->field('store_id')->select();
        $recomendid = array();
        foreach($recommend as $val){
            $recomendid[] = $val['store_id'];
        } 
        //dump($recommend);die;
        $recommenddata = array();
        if(!empty($recomendid)){
            $memberWhere['id'] = array('notin',$recomendid);//为下面会员查找使用
            //查找推荐数据
            $findWhere['id'] = array('in',$recomendid);
            $findWhere['status'] = config('normal_status');
            $recommenddata = Db::name('store')->where($findWhere)->field('id,store_sn,name,thumb_image,image,min_price,province_id,city_id')->order('sort DESC')->select(); 
        }
        //判断推荐信息是否够用
        $onenum = $num - count($recommenddata);
        if($onenum > 0){//推荐数量不满足够用
            $memberWhere['province_id'] = $provinceId;
            $memberWhere['status'] = config('normal_status');
            $memberWhere['member_status'] = array('neq',config('default_status'));
            $memberWhere['category_id'] = $storecategory; 
            $memberdata = Db::name('store')->where($memberWhere)->field('id,store_sn,name,image,min_price,province_id,city_id')->select();
            //dump($memberdata);die;
            $twonum = $onenum-count($memberdata);
            if($twonum>0){//会员商家数量不满足够用
                $nomemberWhere['province_id'] = $provinceId;
                if(!empty($recomendid)){
                    $nomemberWhere['id'] = array('notin',$recomendid);
                }
                $nomemberWhere['status'] = config('normal_status');
                $nomemberWhere['member_status'] = config('default_status');
                $nomemberWhere['category_id'] = $storecategory;
                //dump($nomemberWhere);die;
                $nomemberdata = Db::name('store')->where($nomemberWhere)->field('id,store_sn,name,image,min_price,province_id,city_id')->select();
                $threenum = $twonum - count($nomemberdata);
                if($threenum<0){//非会员商家数量满足够用
                    $neednomemberdata = array();
                    $nomemberid = array_rand($nomemberdata,$twonum);
                    //判断随机数是否是数组
                    if(is_array($nomemberid)){
                        foreach($nomemberid as $val){
                            $neednomemberdata[] = $nomemberdata[$val];
                        }
                    }else{
                        $neednomemberdata[] = $nomemberdata[$nomemberid];
                    }
                }else{//非会员商家数量不满足够用
                    $neednomemberdata = $nomemberdata;
                }
                $tmpdata = array_merge($recommenddata,$memberdata);
                $data = array_merge($tmpdata,$neednomemberdata);
            }else{//会员商家数量满足够用
                $needmemberdata = array();
                $memberid = array_rand($memberdata,$onenum);
                //判断随机数是否是数组
                if(is_array($memberid)){
                    foreach($memberid as $val){
                        $needmemberdata[] = $memberdata[$val];
                    }
                }else{
                    $needmemberdata[] = $memberdata[$memberid];
                }
                $data = array_merge($recommenddata,$needmemberdata);
            }
        }else{//推荐数量满足够用
            $data = array_slice($recommenddata,0,$num);
        }
        $region = $this->getRegionData(array('status'=>config('normal_status')),array('id,name,abbreviate'),'',true);
        $region[0] = '';
        $datacount = count($data);
        for($i=0;$i<$datacount;$i++){
            $data[$i]['province_id'] = $region[$data[$i]['province_id']]['id'];
            $data[$i]['province_name'] = $region[$data[$i]['province_id']]['name'];
            $data[$i]['city_id'] = $region[$data[$i]['city_id']]['id'];
            $data[$i]['city_name'] = $region[$data[$i]['city_id']]['name'];
            $data[$i]['abbr'] = strtolower($region[$data[$i]['province_id']]['abbreviate']);
        }
        
        return $data;
    }
    /*
     * 获取商家数据
     * @param  $provinceId;//省份ID
     * @param  $num;//查找数量
     * return  array;//返回数组
     */
    public function getetiquettedata($provinceId,$num=3){
        $data = array();
        //查找推荐商家ID
        if($provinceId){
            $recommendWhere['province_id'] = $provinceId;
        }
        $recommendWhere['category_id'] = config('category_etiquette_id'); 
        $recommend = Db::name('StoreRecommend')->where($recommendWhere)->field('store_id')->select();
        $allid = array();
        foreach($recommend as $val){
            $allid[] = $val['store_id'];
        } 
        $onenum = $num - count($allid);
        if($onenum > 0){
            //查找其他商家
            if(!empty($allid)){
                $memberWhere['id'] = array('notin',$allid);
            }
            if($provinceId){
                $memberWhere['province_id'] = $provinceId;
            }
            $memberWhere['status'] = config('normal_status');
            $memberWhere['category_id'] = config('category_etiquette_id');  
            $memberdata = Db::name('store')->where($memberWhere)->field('id')->select();
            if ($memberdata) {
                foreach($memberdata as $val){
                    $memberid[] = $val['id'];
                }
                $twonum = count($memberid) - $onenum;
                if($twonum > 0 ){
                    $randid = array_rand($memberid,$onenum);
                    if(is_array($randid)){
                        foreach($randid as $val){
                            $needid[] = $memberid[$val];
                        }
                    }else{
                        $needid[] = $memberid[$randid];
                    }
                    foreach($needid as $val){
                        $allid[] = $val;
                    }
                }else{
                    foreach($memberid as $val){
                        $allid[] = $val;
                    }
                }
            }
        }
        //查找数据
        $data = array();
        if ($allid) {
            $findWhere['id'] = array('in',$allid);
            $order_grave_count = 0;
            $data = Store::withCount(array('Comment' => 'comment_count','OrderService'=>'order_service_count'),false)->where($findWhere)->field('id,store_sn,name,image,min_price,province_id,city_id,hits,actual_hits')->select(); 
            $region = $this->getRegionData(array('status'=>config('normal_status')),array('id,name'),'',true);
            $datacount = count($data);
            for($i=0;$i<$datacount;$i++){
                $data[$i]['province_id'] = $region[$data[$i]['province_id']];
                $data[$i]['city_id'] = $region[$data[$i]['city_id']];
            }
        }
        
        return $data;
    }
}
