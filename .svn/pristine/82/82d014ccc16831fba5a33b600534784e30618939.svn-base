<?php
namespace back\analysis\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Session;//session类

/**
 * 运营统计控制器
 */
class Operate extends Base
{


   /**
     * 百度访客足迹统计
     */
    public function baiduvisitor(){
        $where = array();
        $input = input('get.');
        //封装完成时间
        $where = $this->ordertime('start_time');
        //封装搜索地区

        $search_province_id = input('get.regionData/a');
        if(!empty($search_province_id)){
            $where['province_id'] = array('in',$search_province_id); 
        }
        $BaiduVisitor = Db::name('BaiduVisitor');
        
        $pageSize = Config('page_size');
        $BaiduData = $BaiduVisitor->where($where)->order('province_id DESC')->paginate($pageSize,false,['query'=>$input]);
        //分页
        $page = $BaiduData->render();
        //完成订单
       
        $regionData = $this->getRegionData(array('status'=>config('normal_status'),'level'=>1),array('id,name'),'',true);
        $this->assign('regionData',$regionData);
        $this->assign('baidudata',$BaiduData);
        $this->assign('search_province_id',$search_province_id);
        $this->assign('page',$page);
        return $this->fetch();
    }

    /**详情页**/
    public function detail(){
        $input = input('get.');
        $where = array();
        if(!empty($input['province_id'])){
            $where['province_id']  = $input['province_id'];
        }
        $where['ip'] = $input['ip'];
        $BaiduVisitor = Db::name('BaiduVisitor');
        $data = $BaiduVisitor->where($where)->order('start_time DESC')->paginate('',false,['query'=>$input]);

        $this->assign('data',$data);
        return $this->fetch();
    }

    

    /**
     * 获取省市数据
     * @param  array   $where  条件
     * @param  array   $fields 字段
     * @param  int     $num    条数
     * @param  boolean $bool   获取方式
     * @return array
     */
    private function getRegionData($where=array(), $fields=array(), $num=null, $bool=false){
        $regionModel = Db::name('region');
        if(!$bool){
            if($num == 1){
                $region = $regionModel->where($where)->field($fields)->find();
            }
            if(empty($num)){
                $region = $regionModel->where($where)->field($fields)->select();
            }
        }else{
            $tmp = implode(',',$fields);
            $region = $regionModel->where($where)->column($tmp);
        }
        return $region;
    }


     /**
     * 封装分析时间
     * @param  input|field;//字段名
     * @param  input|$monthlength;//开始时间为空时当前几个月[默认(0)当月]
     * @return  output|array;
     */
    private function ordertime($field,$monthlength = 0){
        $where = array();
        $start_time = input('start_time');
        $end_time = input('end_time');
        if(!empty($start_time) && empty($end_time)){
            $where[$field] = array('egt',strtotime($start_time));
        }
        if(!empty($end_time) && empty($start_time)){
            $where[$field] = array('elt',strtotime($end_time.' 23:59:59'));
        }
        if(!empty($start_time) && !empty($end_time)){
            $where[$field] = array('BETWEEN',array(strtotime($start_time),strtotime($end_time.' 23:59:59')));
        }
        //默认月处理
        if(empty($start_time)){
            $year = date("Y");
            $month = date('m') - $monthlength;
            $where[$field] = array('egt',strtotime($year."-".$month."-1"));
            $start_time = $year."-".$month."-1";
        }
        //默认当天
        if(empty($end_time)){
            $end_time = date('Y-m-d',time());
        }
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        return $where;
    }
}