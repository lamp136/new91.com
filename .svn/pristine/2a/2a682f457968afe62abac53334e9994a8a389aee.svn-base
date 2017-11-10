<?php
namespace back\financial\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use think\Session;//session类
use back\extra\model\OrderService;



 class Service extends Base{

 	 public function getCondition(){
        $where = array();
        $getdata = input('get.');
        if($getdata){ 
            if(!empty($getdata['member_contact'])){
                $where['member_contact'] = $getdata['member_contact'];
            }
            //商务跟踪权限判断
            $tmp = session('businessPrivilege');

            if(!empty($tmp)){
                $where['order_flow_id'] = session('admin_id');
            }
            return $where;
        }
    }

    /**前台留言预约套餐列表**/
 	public function leavemessage(){
 		 $get = input('get.');
         $where = $this->getCondition();
         $where['category_id'] = config('category_etiquette_id');
         $pageSize = Config('page_size');
         $data = Db::name('appoint')->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $get]);
         $page = $data->render();
         $alladmin = $this->getBusinessMen(false,false);
         $alladmin[0] = '';
         $this->assign('order_flow',$alladmin);
         $this->assign('data', $data); // 赋值数据集
         $this->assign('page', $page);
         return $this->fetch();
 	}

 	/**套餐预约列表**/
 	public function appoint(){
         $where = $this->getCondition();
         $where['state'] = array('gt',-1);//未删除
         $where['status'] = config('default_status');
         $this->comfunc($where);
         return $this->fetch();
 	}


 	 /**
     * 交定金列表
     * @param  input|void;
     * @return  output|void;
     */
    public function deposit() {
         $where = $this->getCondition();
         $where['state'] = array('gt',-1);//未删除
         $where['status'] = config('order_status.deposit');
         $this->comfunc($where);
         return $this->fetch();
    }

    /**
     * 有意向列表
     */
    public function interest(){
    	 $where = $this->getCondition();
         $where['state'] = array('gt',-1);//未删除
         $where['status'] = config('order_status.interesting');
         $this->comfunc($where);
         return $this->fetch();
    }

     /**
     * 审核
     */
    public function check(){
    	 $where = $this->getCondition();
         $where['state'] = array('gt',-1);//未删除
    	 $arr = array(config('order_status.ok'),config('order_status.check_success'));
         $where['status'] = array('in',$arr);
         $where['send_finance_status'] = config('send_finance_status.default');
         $this->comfunc($where);
         return $this->fetch();
    }


    /**
     * 审核
     */
    public function waitpayfor(){
         $where = $this->getCondition();
         $where['state'] = array('gt',-1);//未删除
         $where['status'] = config('order_status.check_success');
         $where['send_finance_status'] = config('send_finance_status.send_finance');
         $this->comfunc($where);
         return $this->fetch();
    }


   	 /**
     * 完成订单
     */
    public function combosuccess(){
    	 $where = $this->getCondition();
         $where['state'] = array('gt',-1);//未删除
         $where['status'] = config('order_status.success');
         $this->comfunc($where);
         return $this->fetch();

    }

    	 /**
     * 完成订单
     */
    public function fail(){
    	 $where = $this->getCondition();
         $where['state'] = array('eq',-1);//未删除
         $this->comfunc($where);
         return $this->fetch();

    }

    /**
     * 共同方法
     */
    public function comfunc($where){
    	 $get = input('get.');
         $pageSize = Config('page_size');
         $data = OrderService::with('findmember')->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $get]);
         $page = $data->render();
         $alladmin = $this->getBusinessMen(false,false);
         $alladmin[0] = '';
         $this->assign('storeMember',getStoreMember());
         $this->assign('order_flow',$alladmin);
         $this->assign('data', $data); // 赋值数据集
         $this->assign('page', $page);
    }


    /**
     * 添加
     */
 	public function add(){
 		if(Request::instance()->isPost()){
 			$data= input('post.');
 			$info = $data['info'];
 			$mobile = $info['reservation_phone'];
 			$store_id = $info['store_id'];
 			$memberinfo = Db::name('member')->where('mobile='.$mobile)->field('id')->find();
 			if(!empty($info['goods_id'])){
                $goods_id = $info['goods_id'];  
                $comboinfo = Db::name('EtiquetteCombo')->where('id='.$goods_id)->field('id,combo_name,combo_sn,combo_type')->find();
                    $info['combo_sn']   = $comboinfo['combo_sn'];
                    $info['combo_type'] = $comboinfo['combo_type'];
                    $info['combo_name'] = $comboinfo['combo_name'];
            }
 			$storeinfo = Db::name('store')->where('id='.$store_id)->field('id,store_sn,name,member_status')->find();
 			$info['store_status'] = $storeinfo['member_status'];
 			$info['store_sn']   = $storeinfo['store_sn'];
 			$info['store_name'] = $storeinfo['name'];
            $info['order_flow_id'] = session('admin_id');
            if(!empty($info['appoint_time'])){
 			    $info['appoint_time'] = strtotime($info['appoint_time']);
            }
 			$info['created_time'] = time();
 			if(empty($memberinfo)){
 				$memb['mobile'] = $info['reservation_phone'];
 				$memb['member_type'] = '2';
                $memb['name'] = $info['reservation_person'];
                $memb['check_mobile'] = config('normal_status');
                $memb['created_time'] = date('Y-m-d');
 				$memb['status'] =config('normal_status');
 				$membfina = Db::name('member')->insert($memb);
 				if($membfina){
 					$info['member_id'] = Db::name('member')->getLastInsID();
 				}
 			}else{
 				$info['member_id'] = $memberinfo['id'];
 			}
            
 			$final = Db::name('OrderService')->insert($info);
 			if($final){
 				$this->success('操作成功',url('financial/Service/appoint'));
 			}
 		}else{
 			$where['level'] = config('normal_status');
            $where['status'] = config('normal_status');
            $province = Db::name('region')->where($where)->column('id,name');
            $this->assign('province',$province);
 			return $this->fetch();
 		}
 	}



    /**
     * 获取陵园列表(添加)
     */
    public function getcemetery(){
        $result = array('flg'=>0,'data'=>'');
        $info = input('post.');
        $where['province_id'] = $info['provinceId'];
        // $where['city_id'] = $info['cityId'];
        $where['category_id'] = config('category_etiquette_id');
        $where['status'] = config('normal_status');
        $list = Db::name('store')->where($where)->field('id,name,member_status')->select();
        if($list){
            $result = array('flg'=>1,'data'=>$list);
        }

        echo json_encode($result);
    }

 	 /**
     * 获取商家下套餐(添加)
     * @return json
     */
    public function getcemeterygoods(){
        $storeid = input('get.storeId');
        $result = ['code' => 0,'data' => []];
        if($storeid){
            $goodsData = $this->serviceList($storeid);
            if($goodsData){
                $result = ['code' => 1,'data' => $goodsData];
            }
        }

        echo json_encode($result);
    }


     /**
     * 获取服务公司下商品方法
     * @param  int $id 商家ID
     * @return array
     */
    public function serviceList($id){
        $data = [];
        if($id){
            $data = Db::name('EtiquetteCombo')->where('store_id',$id)->column('id,combo_name');
        }

        return $data;
    }


       /**
     * 审核信息
     */
    public function checkinfo(){
    	$postdata = input('post.');
    	$id = $postdata['order_id'];
    	$data['price'] = $postdata['price'];
    	$data['status'] = config('order_status.check_success');
    	$final = Db::name('OrderService')->where('id='.$id)->update($data);
    	$result = array();
    	if($final){
    		$result['flag'] =1;
    		$result['msg'] ='审核成功';
    	}
    	echo json_encode($result);
    }
 
    /**
     * 推送财务(审核列表)
     * @param  input|void;
     * @return  outout|string(json);
     */
    public function pushfinance(){
        $result = array('flag'=>0,'msg'=>'操作失败');
        $orderId = input('orderId');
        if(!empty($orderId)){
            $data['send_finance_status'] = Config('send_finance_status')['send_finance'];
            $data['send_finance_time']  = time();
            $data['updated_time']  = time();
            if(Db::name('OrderService')->where('id',$orderId)->update($data)){
                $result['flag'] = 1;
                $result['msg'] = '操作成功!';
            }
        }
        echo json_encode($result);
    }

    



 	    /*******************前台预约套餐订单留言功能开始****************/
    /**
     * 拒绝
     */
    public function refuse(){
        $data = input('post.');
        $admin_id = session('admin_id');
        $admin_name = Db::name('admin')->where('id',$admin_id)->field('name')->find();
        $info['status'] = config('appoint_status.refuse');
        $info['remark'] = $data['question'];
        $info['order_flow_id'] = session('admin_id');
        $info['updated_time'] = time();
        $id = $data['id'];
        $info = Db::name('appoint')->where('id='.$id)->update($info);
        $flow['admin_id'] = session('admin_id');
        $flow['appoint_id'] = $id;
        $flow['order_type'] = '2';
        $flow['type'] = '1';
        $flow['question'] = $data['question'];
        $flow['admin_name'] = $admin_name['name'];
        $flow['created_time'] = time();
        $flowinfo = Db::name('AppointFollow')->insert($flow);

        if($info && $flowinfo) {
            $result['flag']=1;
            $result['msg']='操作成功';
        }else{
            $result['flag']=0;
            $result['msg']='操作失败';
        }
        echo json_encode($result);
    }

    /**
     * 前台留言-删除  硬删除
     */
    public function del() {
        $id = input('id');
        $order = Db::name('appoint');
        $info =  $order->where('id='.$id)->delete(); 
        if($info) {
            $result['flag']=1;
            $result['msg']='删除成功';
        }else{
            $result['flag']=0;
            $result['msg']='删除失败';
        }
        echo json_encode($result);
    }

    /**
     * 前台留言-成功
     */
    public function leavemessagesuccess() {
        $id = input('id');
        $appotinfo = Db::name('Appoint')->where('id='.$id)->field('buyer,mobile,order_flow_id')->find();
        $order['reservation_person'] = $appotinfo['buyer'];
        $order['reservation_phone'] = $appotinfo['mobile'];
        $order['order_flow_id'] = $appotinfo['order_flow_id'];
        $order['order_type'] = 5;
        $where['level'] = config('normal_status');
        $where['status'] = config('normal_status');
        $province = Db::name('region')->where($where)->column('id,name');

        $data['status'] = config('appoint_status.success'); 
        $map['id']=$id;
        $ending = Db::name('Appoint')->where($map)->update($data);
        $this->assign('province',$province);
        $this->assign('order',$order);
        return $this->fetch('add');
    }

    /**
     * 添加前台留言跟踪信息
     */
    public function addmessage(){
        $data = input('post.');
        $info = $data['info'];
        $admin_id = Session::get('admin_id');
        $admin_name = Db::name('admin')->where('id',$admin_id)->field('name')->find();
        $info['admin_name'] = $admin_name['name'];
        $info['admin_id'] = $admin_id;
        $info['type'] = 1;
        $info['created_time'] = time();
        $info['order_type'] = 2;
        $res = Db::name('appoint_follow')->insert($info);
        if($res){
            $result = array('flag'=>1,'msg'=>'添加成功');
        }else{
            $result = array('flag'=>0,'msg'=>'添加失败');
        }

        echo json_encode($result);
    }




    /*******************前台预约订单留言功能结束****************/

     /**
     * 套餐列表---->购买成功
     * @param  post    id  
     * @return json
     */
    public function cooperatesuccess(){
        $postdata = input('post.');
        $orderId = $postdata['id'];
        $orderModel = Db::name('OrderService');
        $order = $orderModel->where('id=' . $orderId)->field('id,store_id,status,store_id,store_name,store_status')->find();
        if (empty($order)) {
            $data['flag'] = 0;
            $data['msg'] = '数据没找到';
        } else {
            //通过商家id,查找该商家是否是会员单位，如果是的话，进入返现等步骤，反则该订单直接结束。
           
                if ($order['store_status'] == config('store_member') || $order['store_status'] == config('store_member_person') || $order['store_status'] == config('store_member_ad') || $order['store_status'] == config('store_member_v')) {

                    $type = $postdata['type'];
                    if(!empty($type) && $type==1){//交订金
                        $info['deposit'] = $postdata['despoit'];
                        $info['status']  = config('order_status.deposit');
                        $info['id'] = $orderId;
                        $info['deposit_give_time'] = time();
                        
                    }else{
                         //商家会员或者是个人会员或者广告会员
                        $info['status'] = config('order_status.ok');
                        $info['id'] = $orderId;
                    }

                    if ($orderModel->where('id='.$info['id'])->update($info)) {
                        $data['flag'] = 1;
                        $data['msg'] = '操作成功';
                    } else {

                        $data['flag'] = 0;
                        $data['msg'] = '操作失败';
                    }

                } else {
                    //不是上面的两个会员,订单不涉及返现，直接完成
                    $info['status'] = config('order_status.success');
                    $info['id'] = $orderId;
                    $info['success_time'] = time();//购墓成交时间
                    $info['updated_time'] = time();
                    if ($orderModel->where('id='.$info['id'])->update($info)) {
                        $data['flag'] = 1;
                        $data['msg'] = '操作成功';
                    } else {
                        $data['flag'] = 0;
                        $data['msg'] = '操作失败';
                    }
                }
            }
        echo json_encode($data);
        }


    /**
     * 合作失败
     */
    public function cooperatefail() {
        $postdata = input('post.');
        $orderId = $postdata['id'];
        $orderModel = Db::name('OrderService');
        $order = $orderModel->where('id='.$orderId)->field('id,store_id,status')->find();
        if (empty($order)) {
            $data['flag'] = 0;
            $data['msg'] = '数据没找到';
        } else {
           $info['fail_reason'] =$postdata['reason'];//备注
           $info['updated_time'] = time();
           $info['state'] = config('order_status.fail');
           if ($orderModel->where('id='.$orderId)->update($info)) {
                $data['flag'] = 1;
                $data['msg'] = '操作成功';
            } else {
                $data['flag'] = 0;
                $data['msg'] = '操作失败';
            }
        }
        echo json_encode($data);
    }


     /**
     * 将订单转换为有意向订单
     */
    public function changeinteresting() {
        $data = input('post.');
        $id = $data['id'];
        $datainfo['interesting_time'] = strtotime($data['interesting_time']);
        $datainfo['status'] = config('order_status.interesting');
        $datainfo['updated_time'] = time();
        $datainfo['interest_reason'] =  $data['reason'];
        $resultdata =  Db::name('OrderService')->where('id='.$id)->update($datainfo);
        if($resultdata){
            $result['flag']=1;
            $result['msg'] = '转为有意向成功';
        }else{
            $result['flag']=0;
            $result['msg'] = '转为有意向失败';

        }
        echo json_encode($result);
    }

    /**
     * 回撤功能
     * @return [type] [description]
     */
    public function backspace(){
        $orderId = input('id');
        $data['state'] = config('default_status');
        $info = Db::name('OrderService')->where('id='.$orderId)->update($data);
        if($info){
            $result['flag'] = 1;
            $result['msg'] = '回撤成功';
        }
        echo json_encode($result);
    }


     /**
     * 委派订单
     * @return void
     */
    public function delegate(){
        $input = input('get.');
        $notIn = [
            config('order_status.fail'),
            config('order_status.success'),
            config('order_status.back_success'),
            config('order_status.no_relation'),
        ];
        $where['status'] = ['not in',$notIn];
        $where['state'] = ['gt',config('delete_status')];
        $storeList = Db::name('OrderService')->where($where)->column('store_id,store_name');
        if(!empty($input['store_id']) && isset($input['store_id'])){
            $where['store_id'] = $input['store_id'];
        }
        if(!empty($input['mobile']) && isset($input['mobile'])){
            $where['mobile|reservation_phone'] = ['like','%'.$input['mobile'].'%'];
        }
        if(!empty($input['flow_id']) && isset($input['flow_id'])){
            $where['order_flow_id'] = $input['flow_id'];
        }
        $fields = 'id,store_id,member_id,store_status,appoint_time,created_time,store_name,order_flow_id,status,reservation_phone,reservation_person';
        $delegateList = OrderService::with('flowman')->where($where)->field($fields)->order('created_time desc')->paginate(config('page_size'),false,['query' => $input]);
        $page = $delegateList->render();
        $this->assign([
            'stores'         => $storeList,
            'businessMen'    => [
                'all'  => $this->getBusinessMen(true,false),// 包括离职的商务人员
                'work' => $this->getBusinessMen(true,true)//在职的商务人员
            ], 
            'storeMember'    => getStoreMember(),
            'orderStatus'    => config('order_status_change'),
            'page'           => $page,
            'delegateList'   => $delegateList
        ]);
        return $this->fetch();
    }

    /**
     * 处理中
     */
    public function loading(){
       $id= input('id');
       $result = array('flag'=>0,'data'=>'更新失败');
       if($id){
            $data['status'] = config('normal_status');
            $data['updated_time'] = time();
            $data['admin_id'] =session('admin_id');
            $final = Db::name('Appoint')->where('id='.$id)->update($data);
            if($final){
                $result['flag'] = 1;
                $result['msg'] = '更新成功';
            }
       }
       echo json_encode($result);
    }


    /**
     * 修改跟踪人员
     * @return json
     */
    public function saveFlowMan(){
        $input = input('post.');
        $result = ['code' => 0,'msg' => '修改失败'];
        $data = [];
        if($input['ids']){
            $idsArr = explode(',',$input['ids']);
            foreach ($idsArr as $v) {
                $info = explode('-',$v);
                $originFlowIds = $info[1];
                $origin = Db::name('OrderService')->where('id',$info[0])->field('id,origin_flow_id')->find();
                if(!empty($origin['origin_flow_id'])){
                    if($origin['id'] == $info[0]){
                        $tmpIds = $origin['origin_flow_id'].','.$info[1];
                    }
                    // 去除重复的id重新组装
                    $expIds = explode(',', $tmpIds);
                    $originFlowIds = implode(',',array_unique($expIds));
                }
                $data[] = [
                    'id'             => $info[0],
                    'origin_flow_id' => $originFlowIds,
                    'order_flow_id'  => $input['flow_man']
                ];
            }
            $OrderService = new OrderService;
            $saveAll = $OrderService->saveAll($data);
            if($saveAll){
                $result = ['code' => 1,'msg' => '修改成功'];
            }
        }

        echo json_encode($result);
    }





 }

