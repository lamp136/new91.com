<?php
namespace web\cemetery\controller;
use think\Controller;
use think\Db;
use web\extra\controller\Base;
use web\extra\model\Store;
use think\Cookie;//cookie类
use think\Request;//请求类

class Map extends Base
{
    public function index(){
        $input = input();
        $stores = [];
        $city = [];
        $province = 3;
        if(input('?cookie.ip_region_id')){
            $province = decode(Cookie::get('ip_region_id'));
        }
        $where['province'] = $province;
        $stores = $this->_stores($where);
        $storeCity = Db::table(['__REGION__' => 'r','__STORE__' => 's'])->where('r.pid ='.$province.' and r.id = s.city_id and s.category_id ='.config('category_cemetery_id').' and s.status ='.config('normal_status').' and longitude != ""')->field(['r.id','r.name','count(s.id)' => 'count'])->group('r.id')->order('count desc')->select();
        //SEO部分
        $seo = [
            'seo_title'       => '地图看'.Cookie::get('ip_region_name').'墓地-'.Cookie::get('ip_region_name').'墓地陵园地址电话-'.Cookie::get('ip_region_name').'陵园路线查询-91搜墓网',
            'seo_keywords'    => Cookie::get('ip_region_name').'地图看墓,'.Cookie::get('ip_region_name').'墓地分布图,'.Cookie::get('ip_region_name').'陵园分布图,'.Cookie::get('ip_region_name').'墓地分布,'.Cookie::get('ip_region_name').'陵园地图,'.Cookie::get('ip_region_name').'墓地地图,'.Cookie::get('ip_region_name').'墓地陵园查询,'.Cookie::get('ip_region_name').'墓地电话、地址',
            'seo_description' => '91搜墓网地图看墓为用户提供'.Cookie::get('ip_region_name').'范围内所有墓地在地图上的信息信息,包含'.Cookie::get('ip_region_name').'所有墓地信息查询,'.Cookie::get('ip_region_name').'墓地陵园电话,'.Cookie::get('ip_region_name').'墓地地址,'.Cookie::get('ip_region_name').'墓地信息查询,分布查询,'.Cookie::get('ip_region_name').'墓地信息导航等,让您快速方便的找到需要的墓地.'
        ];

        $this->assign([
            'provinceId' => $province,
            'storeCity'  => $storeCity,
            'stores'     => $stores,
            'seo'        =>$seo,
        ]);
        return $this->fetch();
    }

    /**
     * 获取陵园
     * @param  integer $query 省|市条件
     * @return array
     */
    private function _stores($query = []){
        $data = '';
        $where['category_id'] = config('category_cemetery_id');
        $province = 3;
        $where['longitude'] = ['neq',''];
        $where['status'] = config('normal_status');
        if(!empty($query['province'])){
            $province = $query['province'];
        }
        $where['province_id'] = $province;
        if(!empty($query['city'])){
            $where['city_id'] = $query['city'];
        }
        $stores = Store::with(['province','city'])->where($where)->field('id,name,province_id,city_id,address,image,thumb_image,longitude,latitude,min_price')->select();
        if(!empty($stores)){
            $data = $stores;
        }
        return $data;
    }

    /**
     * 获取商家
     * @return json
     */
    public function merchant(){
        $input = input('get.');
        $result = ['code' => 0,'data' => []];
        $storeData = $this->_stores($input);
        $count = 0;
        if(!empty($storeData)){
            $result = ['code' => 1,'data' => $storeData];
        }

        echo json_encode($result);
    }
}