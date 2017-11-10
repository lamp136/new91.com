<?php
namespace back\plugin\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use back\extra\model\Store;

//热门陵园类
class Hotstore extends Base{

 	/**
 	 * 生成热门陵园数据
 	 */
 	public function makehotdata(){
 		//第一步，从订单表中取出商家ID  订单状态 订单ID 商家状态字段
 		$where['status'] = array('egt',config('default_status'));
 		$where['store_status'] = array('gt',config('default_status'));
 		$datas = Db::name('OrderGrave')->field('id,status,store_id,store_status')->where($where)->select();
 		$lastData = array();
 		$lastDatas = array();
 		 //初始化数据
        foreach($datas as $lastDatas){
        	$lastData[$lastDatas['store_id']]['appoint'] = 0;
            $lastData[$lastDatas['store_id']]['wait_money']=0; 
            $lastData[$lastDatas['store_id']]['success']=0; 
        }

 		foreach($datas as $lastDatas){
            //订单为预约状态或者有意向状态，每单+1分
            if(($lastDatas['status'] == config('order_status.default'))||($lastDatas['status'] == config('order_status.interesting'))){ 

                $lastData[$lastDatas['store_id']]['appoint'] +=config('hot_score.appoint');
            //订单为购墓成功和审核完成待返现状态或有定金，每单+5分
            }else if(($lastDatas['status'] == config('order_status.ok'))||($lastDatas['status'] == config('order_status.check_success')) || ($lastDatas['status'] == config('order_status.deposit'))){

                $lastData[$lastDatas['store_id']]['wait_money'] +=config('hot_score.wait_money'); 
            }else if(($lastDatas['status'] == config('order_status.get_money'))||($lastDatas['status'] == config('order_status.return_success')) || ($lastDatas['status'] == config('order_status.success'))){ 

                $lastData[$lastDatas['store_id']]['success'] +=config('hot_score.success'); 
            }
 		}
 		unset($where);
 		foreach($lastData as  $k=>$v){
 			// $num[$k] = array_sum(array($v['appoint'],$v['success'],$v['wait_money']));
 			$list[]= [
 				'id'=>$k,
 				'system_score'=>array_sum(array($v['appoint'],$v['success'],$v['wait_money'])),
 				'total_score'=>array_sum(array($v['appoint'],$v['success'],$v['wait_money']))
 			];
 		}

 		$store = new Store;
 		$final = $store->saveAll($list);
 		if($final){
 			$result['flag'] = 1;
 			$result['msg'] = '生成系统评测分数和总分数成功!';
 		}

 		echo json_encode($result);
 	}

 	/**
 	 * 热门陵园列表
 	 */
 	public function index(){
 		$getdata = input('get.');
 		if(!empty($getdata['storename'])){
 			$where['name'] = $getdata['storename'];
 		}
 		$where['member_status'] = array('gt',config('default_status'));
 		$pageSize = Config('page_size');
        $field = 'id,member_status,total_score,system_score,name';
        $data = Db::name('store')->where($where)->field($field)->order('total_score desc')->paginate($pageSize,false,['query' => $getdata]);
        $page = $data->render();
        $this->assign('storeMember',getStoreMember());
        $this->assign('data',$data);
        $this->assign('page',$page);
 		return $this->fetch();
 	}

 	/**
 	 * 修改总分数
 	 */
 	public function changescore(){
 		$data = input('post.');
 		$where['id'] = $data['id'];
 		$arr = array($data['score'],$data['total_score']);
 		$info['total_score'] = array_sum($arr);
 		$final = Db::name('store')->where($where)->update($info);
 		if($final){
 			$result['flag'] = 1;
 			$result['msg'] = '修改成功!';
 		}
 		echo json_encode($result);
 	}




 }