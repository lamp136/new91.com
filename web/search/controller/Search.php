<?php
namespace web\search\controller;
use think\Controller;
use think\Cache;
use think\Cookie;//cookie类
use think\Db;
use think\cache\driver\Redis;
use common\sphinx\SphinxClient;

class Search extends controller
{
    public function __construct(){
        // $host = '127.0.0.1';
        // $port = 6379;
        // $redis = new \Redis();
        // $redis->connect($host,$port);
        // $redis->set('name','lsjfklsjfkljsljf',3600 * 24);
        // $name = $redis->get('test');
        // dump($redis);exit;
    }
    
    /**
     * 搜索提示
     * @return array;  
     */
    public function hint(){
        $reult = array();
        $input = input('post.');
        $type  = $input['type'];
        $keyword  = $input['keyword'];
        $provinceid = decode(cookie::get('ip_region_id'));
        $data = array();
        $lastdata = '';
        if(!empty($keyword)){
            if(!empty($type)){
                $searchwhere['type'] = $type;
            }
            $searchwhere['keyword'] =array('like','%'.$keyword.'%'); 
            $searchwhere['province_id'] = $provinceid;
            $data = Db::name('SearchEngine')->where($searchwhere)->field('store_id,keyword')->select();
        }
        return json_encode($data);
    }
    /**
     * @param  string   $keyword   搜索词
     * @param  Int      $type      类型(eg:陵园 殡仪馆 )
     * @return array;   
     */
    public function searchName($keyword,$type){
        $provinceid = decode(cookie::get('ip_region_id'));
        $data = array();
        $lastdata = array();
        if(!empty($keyword)){
            if(!empty($type)){
                $searchwhere['type'] = $type;
            }
            $searchwhere['keyword'] =array('like','%'.$keyword.'%'); 
            $searchwhere['province_id'] = $provinceid;
            $data = Db::name('SearchEngine')->where($searchwhere)->field('store_id')->select();
            foreach($data as $val){
                $lastdata[] = $val['store_id'];
            }
        }
        if(empty($lastdata)){
            //获取对应的商家数据
            $data = $this->_sphinxSearchd($keyword,$type,$provinceid,$city=0);
            if(!empty($data['matches'])){
                foreach($data['matches'] as $key=>$val){
                    $lastdata[] = $key;
                }
            }
        }
        return $lastdata;
    }
    
    /**
     * 实例化 sphinx 类
     * @param  String   $host    服务的地址
     * @param  Int      $port    端口号
     * @return SphinxClient
     */
    private function sphinxClient($host='', $port=9312) {
        if (empty($host)) {
            $host = config('search_server');
        }
        $sphinxClient = new SphinxClient();
        $sphinxClient->SphinxClient();
        $sphinxClient -> SetServer($host, $port);
        return $sphinxClient;
    }
    /*
     * @param   string $wd         搜索词
     * @param   int    $type       商家类型
     * @param   int    $province   省份  
     * @param   int    $city       地区 
     * @param   int    $offset     偏移量
     * @param   bool   $bool       
     * return  array;
     */
    private function _sphinxSearchd($wd, $type, $province, $city, $offset=0, $bool=true) {
        if (empty($wd) && empty($province)) {
            return array();
        }
        //获取sphinx对象
        $sphinxClient = $this->sphinxClient();
        if($type != config('category_etiquette_id')){
            // 地区过滤
            $filterProvince = (int)$province;
            $sphinxClient -> SetFilter("province_id", array($filterProvince));   //省份过滤条件
            //如果城市存在，那么也需要进行过滤，主要用在列表页的条件过滤上
            if ($city && $bool) {
                $sphinxClient -> SetFilter("city_id", array((int)$city));   //省份过滤条件
            }
        }
        if ($bool) {
            $sphinxClient -> SetLimits($offset, config('default_size'));    //匹配的偏移量
        } else {
            $sphinxClient -> SetLimits(0, 1000);    //匹配的偏移量
        }

        $sphinxClient->SetMatchMode(SPH_MATCH_ALL);//匹配所有查询词(默认模式);
        //会员状态  取反  非删除的数据
        //$sphinxClient -> SetFilter("state", array(config('delete_status')), true);
        //排序
        $sphinxClient -> setSortMode(SPH_SORT_EXTENDED, '@weight DESC');
        $sphinxClient -> SetSortMode( SPH_SORT_EXTENDED, "member_status DESC");
        //数据类型 陵园,殡仪馆还是服务
        if ($type && $type == config('category_cemetery_id')) {
            $filterCategory = array(config('category_cemetery_id'));
        } else if ($type && $type == config('category_funeral_id')) {
            $filterCategory = array(config('category_funeral_id'));
        } else if($type && $type == config('category_etiquette_id')){
            $filterCategory = array(config('category_etiquette_id'));
        }else {
            $filterCategory = array(config('category_cemetery_id'),config('category_funeral_id'),config('category_etiquette_id'));
        }
        //如果添加第三个参数， true 则是取反
        $sphinxClient->SetFilter("category_id", $filterCategory);
        $sphinxClient->SetMatchMode(SPH_MATCH_ALL);
        $sphinxResult = $sphinxClient->query($wd, "*");
        //完全匹配数据，如果数据没有那么进行模糊匹配
        if(!empty($sphinxResult['matches'])){
            $sphinxClient->SetMatchMode(SPH_MATCH_ANY);
            $sphinxResult = $sphinxClient->query($wd, "*");
        }
        return $sphinxResult;
    }

    public function searchwd(){
        $input = input('get.');
        if(!empty($input['keyWord'])){
            $data = [
                'keyword'      => $input['keyWord'],
                'province_id'  => decode(cookie('ip_region_id')),
                'category_id'  => $input['category'],
                'ip'           => request()->ip(1),
                'created_time' => time(),
            ];
            Db::name('SearchKeywords')->data($data)->insert();
        }
    }
    
    
}