<?php
namespace web\topics\controller;
use think\Controller;
use web\extra\controller\Base;

class Topics extends Controller
{
    public function _initialize(){
        //获取头部地区切换的数据（添加缓存） 
        $province = array();
        $base = new Base;
        $regions = $base->getRegionData(array('status'=>config('normal_status'),'pid'=>config('china_num')),array('id','name','flag','abbreviate'),'',false,'flag asc');
        foreach($regions as $val){
            $province[$val['flag']][$val['id']] = [
                'name' => $val['name'],
                'abbr' => strtolower($val['abbreviate'])
            ];
        }
        $this->assign('province',$province);
    }
    public function zhongyuanjie(){
        return $this->fetch();
    }
}