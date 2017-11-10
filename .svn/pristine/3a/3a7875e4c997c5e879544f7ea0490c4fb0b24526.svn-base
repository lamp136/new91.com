<?php
namespace web\extra\controller;
use think\Controller;
use think\Request;//请求类
use think\Session;//session类
use think\Cookie;//cookie类
use think\Db;
use web\extra\model\News;
use common\iplocation\IpLocale;

class Base extends Controller 
{

    public function _initialize(){
        //用户登录信息name
        $member_name = decode(session('name'));
        $this->assign('member_name',$member_name);
        //根据域名查找地区
        $domain_name = $_SERVER['SERVER_NAME'];
        $domain_array = explode('.',$domain_name);
        $abbreviate = strtolower($domain_array[0]) == 'www' ? strtoupper('BJ') : strtoupper($domain_array['0']);
        $domain_region = $this->getRegionData(array('abbreviate'=>$abbreviate),array('id','name','abbreviate'),'1',false);
        //获取当前路径
        $path  =  request()->controller();
        $this->assign('path',$path);
        $action = request()->action();
        $this->assign('action',$action);
        $currentProvinceId = config('default_region_id');
        /*
            1、入口地区
            2、是否有地区缓存
            3、是否开始IP
         */
        /**
         * 是否有入口地区
         * 如果有域名来决定地区那么不需要判断IP、缓存
         */
        if (!empty($domain_region)) {
            $currentProvinceId = $domain_region['id'];
            Cookie::set('ip_region_id',encode($domain_region['id']),['expire'=>config('home_lifetime')]);
            Cookie::set('ip_region_name',$domain_region['name'],['expire'=>config('home_lifetime')]);
            $url = "http://".strtolower($domain_region['abbreviate']).'.'.config('domain_name');
        } else {
            /**
             * 如果没有入口地区，需要先判断缓存
             * 缓存不存在，IP定位未开启，默认地区为北京
             * 缓存不存在，IP开启，执行开启ＩＰ定位的地区
             *
             * 如果是通过www域名来访问
             * 1、只需要获取地区数据，不需要进行域名切换
             */
             $cookie_regiod_id = decode(cookie::get('ip_region_id'));
            if(!empty($cookie_regiod_id)){
                $currentProvinceId = $cookie_regiod_id;
                $cookie_region = $this->getRegionData(array('id'=>$cookie_regiod_id),array('abbreviate'),'1',false);
                $url = "http://".strtolower($cookie_region['abbreviate']).'.'.config('domain_name');
            } else {
                $iplocation = config('ip_location');//IP定位是否开启
                if ($iplocation) {
                    $ip = request()->ip();//获取IP
                    $locale = New IpLocale();
                    $areas = $locale->getProvinceCityId ($ip); // 根据ip获取登录地区
                    if(empty($areas)){
                        $areas['provid'] = config('default_region_id');
                        $areas['name'] = config('default_region_name');
                    }
                    $currentProvinceId = $areas['provid'];
                    Cookie::set('ip_region_id',encode($areas['provid']),['expire'=>config('home_lifetime')]);
                    Cookie::set('ip_region_name',$areas['name'],['expire'=>config('home_lifetime')]);
                    $urlabbreviate = $this->getRegionData(array('id'=>$currentProvinceId),array('abbreviate'),'1',false);
                    $url = "http://".strtolower($urlabbreviate['abbreviate']).'.'.config('domain_name');
                } else {
                    Cookie::set('ip_region_id',encode(config('default_region_id')),['expire'=>config('home_lifetime')]);
                    Cookie::set('ip_region_name',config('default_region_name'),['expire'=>config('home_lifetime')]);  
                    $url = "http://".config('default_region_abbr').'.'.config('domain_name');
                }
            }
        }
        $this->assign('url',$url);
        
        //获取头部地区切换的数据（添加缓存） 
        $province = array();
        $regions = $this->getRegionData(array('status'=>config('normal_status'),'pid'=>config('china_num')),array('id','name','flag','abbreviate'),'',false,'flag asc');
        foreach($regions as $val){
            $province[$val['flag']][$val['id']] = [
                'name' => $val['name'],
                'abbr' => strtolower($val['abbreviate'])
            ];
        }
        $this->assign('province',$province);
        //推荐关键词(需要当地的地区条件过滤，考虑全局通用问题)
        $this->getRecommendWords($currentProvinceId);
    }
    /**
     * 搜索框下面搜索提示词
     * @param int $currentProvinceId 当前地区的ID
     * 
     * @return void
     */
    protected function getRecommendWords($currentProvinceId) {
        $where['province_id'] = array('like','%,'.$currentProvinceId.',%');
        $introdata = Db::name("Introduce")->where($where)->field('name,province_id,sort,url')->order('sort desc')->select();
        $introarraydata = array_slice($introdata,0,5);//获取数组长度
        $this->assign('introdata',$introarraydata);
    }

    /**
     *获取友情链接
     *@input void;
     *@return void;
     */
    protected function getfriendlink(){
        $provinceId = decode(cookie::get('ip_region_id'));
        $where['status'] = config('normal_status');
        $link = Db::name('Friendlink')->field('name,url,province_id')->where($where)->order('sort DESC')->select(); 
        $friendlinks = array();
        foreach ($link as $value) {
            $province_id = explode(',',$value['province_id']);
            if(in_array($provinceId,$province_id)){
                $friendlinks[] = $value;
            }
        }
        $this->assign('friendlinks',$friendlinks);
    }
    /**
     *获取百科数据
     *@input $categoryId;//分类ID
     *@input $num;//信息数量
     *@param [string] $field  指定获取文章的字段
     *@return array;
     */
    public function article($categoryId,$num=6,$field=''){
        $data = array();
        $num = (int)$num;
        $cateWhere['is_show'] = config('normal_status');
        $categorydata = Db::name('category')->field('id,pid')->where($cateWhere)->select();
        $category = $this->getcategoryId($categorydata,$categoryId);
        $category[] = $categoryId;
        $where['category_id'] = array('in',$category);
        $where['status'] = config('normal_status');
        $data = Db::name('News')->where($where)->field($field)->order('published_time DESC,sort DESC')->limit($num)->select();
        return $data;
    }
    /**
     *获取该分类下子所有分类ID
     *@input $data;//分类数据
     *@input $pid;//分类ID
     *@return array;
     */
    private function getcategoryId($data,$pid){
        static $result = array();
        foreach($data as $val){
            if($val['pid'] == $pid){
                $result[] = $val['id'];
                $this->getcategoryId($data,$val['id']);
            }
        }
        return $result;
    }
    /**
     * 获取省市数据
     * @param  array   $where  条件
     * @param  array   $fields 字段
     * @param  int     $num    条数
     * @param  boolean $bool   获取方式
     * @param  string  $sort   排序
     * @return array
     */
    public function getRegionData($where=array(), $fields=array(), $num=null, $bool=false,$sort=false){
        $regionModel = Db::name('region');
        if(!$bool){
            if($num == 1){
                $region = $regionModel->where($where)->field($fields)->find();
            }
            if(empty($num)){
                if($sort){
                    $region = $regionModel->where($where)->field($fields)->order($sort)->select();
                }else{
                    $region = $regionModel->where($where)->field($fields)->select();
                }
            }
        }else{
            $tmp = implode(',',$fields);
            if($sort){
                $region = $regionModel->where($where)->column($tmp);
            }else{
                $region = $regionModel->where($where)->order($sort)->column($tmp);
            }
        }
        return $region;
    }

    /**
     * 查找SEO数据
     * @param    num    $type; 分类ID
     * @return   array  $data;
     */
    public function getseo($type = '') {
        $where['province_id'] = decode(cookie::get('ip_region_id'));
        /*$types = config('seo_type');
        if (empty($type) || !in_array($type, $types)) {
            $type = config('seo_type.cemetery_home');
        }*/
        $where['type'] = $type;
        $where['status'] = config('normal_status');
        $data = Db::name('Seo')->field('seo_title,seo_keywords,seo_description')->where($where)->find();
        //TODO 数据为空判断
        if (empty($data)) {
            $data['seo_title'] = "全国陵园墓地公墓，墓地价格，风水-91搜墓网";
            $data['seo_keywords']= "全国陵园墓地，公墓，墓地价格，陵园风水-91搜墓网";
            $data['seo_description'] = "91搜墓网是全国最大的陵园公墓网络搜索平台，提供公墓，陵园，周边墓地，周边公墓，墓地价格，墓地风水，为需要购买墓地的用户提供真实有效的陵园信息资源和便快捷的贴心服务。全国统一咨询热线：400-8010-344。";
        }
        return $data;
    }

    /**
     * 其他栏目的惠民政策新闻获取
     * @param int $num 获取的数量，如果是0 则是使用默认数量
     *
     * @return void
     */
    public function hmzc($num=0) {
        if (empty($num)) {
            $num = config('default_size');
        } else {
            $num = (int)$num;
        }
        $news = new News;
        //惠民政策
        $hmzc = $news->getNewsByCategory(config('article_fengshui_culture'), $num);
        $this->assign('hmzc', $hmzc);
    }

    public function rejump(){
        $id = input('id');
        if($id != 0){
            $store = Db::name('store')->where('id',$id)->field('id,province_id,category_id')->find();
            $province = $this->getRegionData(['id' => $store['province_id']],['abbreviate'],'1',false);
            $abbr = $province['abbreviate'];
            $path = '/cemetery';
            if($store['category_id'] == config('category_funeral_id')){
                $path = '/funeral';
            }
            $url = 'http://'.strtolower($abbr).'.'.config('domain_name').$path.'/details/'.$id.'.html';
            $this->redirect($url);
        }
    }
    public function rejumpf(){
        $id = input('id');
        if($id != 0){
            $store = Db::name('store')->where('id',$id)->field('id,province_id')->find();
            $province = $this->getRegionData(['id' => $store['province_id']],['abbreviate'],'1',false);
            $abbr = $province['abbreviate'];
            $url = 'http://'.strtolower($abbr).'.'.config('domain_name').'/funeral/details/'.$id.'.html';
            $this->redirect($url);
        }
    }
}