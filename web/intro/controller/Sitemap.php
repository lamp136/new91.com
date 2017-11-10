<?php
namespace web\intro\controller;
use think\Controller;
use think\Db;
use think\Cookie;//cookie类
use web\extra\controller\Base;
use web\extra\model\Store;
use think\Session;//session类

class Sitemap extends Base
{
    public function _initialize(){
        //用户登录信息name
        $member_name = decode(session('name'));
        $this->assign('member_name',$member_name);
        //根据域名查找地区
        $domain_name = $_SERVER['SERVER_NAME'];
        $domain_array = explode('.',$domain_name);
        $abbreviate = strtoupper($domain_array['0']);
        $domain_region = $this->getRegionData(array('abbreviate'=>$abbreviate),array('id','name'),'1',false);
      
        //获取当前路径
        $path  =  request()->controller();
        $this->assign('path',$path);
        $action = request()->action();
        $this->assign('action',$action);
        //查找省份 
        $province = array();
        $regions = $this->getRegionData(array('status'=>config('normal_status'),'pid'=>config('china_num')),array('id','name','flag','abbreviate'),'',false,'flag asc');
        foreach($regions as $val){
            $province[$val['flag']][$val['id']] = [
                'name' => $val['name'],
                'abbr' => strtolower($val['abbreviate'])
            ];
        }
        $this->assign('province',$province);
        //友情链接
        $this->getfriendlink();
        //推荐关键词
        $introarray = array();
        $intro_province_id = decode(cookie::get('ip_region_id'));
        $introdata = Db::name("Introduce")->field('name,province_id,sort,url')->order('sort desc')->select();
        foreach($introdata as $val){
            $tmpintrodata = explode(',',$val['province_id']);
            if(in_array($intro_province_id,$tmpintrodata)){
                $introarray[] = $val;
            }
        }
        $introarraydata = array_slice($introarray,0,5);//获取数组长度
        $this->assign('introdata',$introarraydata);
    }
    /**
     * 站点地图
     * @return void
     */
    public function index(){
        $storeDatas = Db::table(['__STORE__' => 's','__REGION__' => 'r'])->field(['s.id','s.name','s.category_id','s.member_status','r.id' => 'region_id','r.name' => 'region_name','r.abbreviate' => 'abbr'])->where('s.province_id = r.id and s.status > 0')->order('s.member_status desc,r.sort asc')->select();
        if($storeDatas){
            foreach ($storeDatas as $key => $val) {
                $stores[strtolower($val['abbr'])][$val['category_id']][] = $val;
                $stores[strtolower($val['abbr'])]['region_name'] = $val['region_name'];
            }
        }
        $this->assign([
            'stores' => $stores
        ]);
        $seo = $this->getseo();
        $this->assign('seo',$seo);
        return $this->fetch();
    }
}