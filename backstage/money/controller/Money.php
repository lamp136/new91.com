<?php
namespace back\money\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Session;//session类
use back\extra\model\OrderGrave;

/**
 * 财务控制器
 */
class Money extends Base
{
	


	 /**
     * 列表页共同方法
     * @param  $where 条件
     * @param  $get   input('get.');分页用
     * @param  $time  按什么时间排序
     * @param  return array();
     * 
     */
    public function comlist($where,$get,$time){
        $order = db::name('OrderGrave');

        $pageSize = Config('page_size');
         //搜索条件
        $getdata = input('get.');
        if($getdata){
            if(!empty($getdata['storename'])){
                $where['store_name'] = array('like','%'.$getdata['storename'].'%');
            }
            if(!empty($getdata['phone'])){
                $where['mobile|reservation_phone'] = array('like','%'.$getdata['phone'].'%');
            }
            
        }
        $data = $order->where($where)->order(''.$time.' desc')->paginate($pageSize,false,['query' => $get]);
        return $data;
    }

	/**
	 * 财务退单列表
	 */
	public function backmoneylist(){
		$get = input('get.');
        $time = 'back_apply_time';
        $arr = array(config('order_status.back_success'),config('order_status.allow'));
        $where['status'] = array('in',$arr); 
        $data = $this->comlist($where,$get,$time);
        $page = $data->render();
        $status = config('order_status_change');
        $this->assign('data',$data);
        $this->assign('page',$page);
        $this->assign('status',$status);
        $this->assign('storeMember',getStoreMember());
        return $this->fetch('backmoneylist');
	}

    /**
     * 陵园支付列表
     */
    public function storepaylist(){
        $get = input('get.');
        $where['status'] = config('order_status.check_success');//3
        $where['state'] = array('gt',-1);

        //已发送财务
        $where['send_finance_status'] = config('send_to_finance.success');
        $time = 'send_finance_time';

        $member = Db::name('admin')->where('status',config('normal_status'))->column('id,name');
      
        $data = $this->comlist($where,$get,$time);
        $page = $data->render();
        
        $this->assign('member',$member);
        $this->assign('data',$data);
        $this->assign('page',$page);
        $this->assign('storeMember',getStoreMember());
        return $this->fetch('storepaylist');
    }

    /**
     * 查看跟踪信息
     */
    public function checkmessage(){
        $id= input('id');
        $arr = array('flag'=>0,'data'=>'');
        $revisit = Db::name('order_revisit')->where('order_id',$id)->order('created_time desc')->select();

        foreach ($revisit as $key => $value) {
            $revisit[$key]['created_time'] = date('Y-m-d H:i:s',$value['created_time']);
        }
        if($revisit){
            $arr = array('flag'=>1,'data'=>$revisit );
        }

        echo json_encode($arr);
    }

    /**
     * 添加回访跟踪信息
     */
    public function addmessage(){
        $data = input('post.');
        $info = $data['info'];
        $admin_id = Session::get('admin_id');
        $info['admin_id'] = $admin_id;
        $admin_name = Db::name('admin')->where('id',$admin_id)->field('name')->find();
        $info['admin_name'] = $admin_name['name'];
        $info['created_time'] = time();
        
        $res = Db::name('order_revisit')->insert($info);
        if($res){
            $result = array('flag'=>1,'msg'=>'添加成功');
        }else{
            $result = array('flag'=>0,'msg'=>'添加失败');
        }

        echo json_encode($result);
    }

    /**
     * 佣金支付成功
     */
    public function payforsuccess(){
        $data = input('post.');
        $info = $data['info'];
        $array = array('flag'=>0,'msg'=>'操作失败');
        $order = Db::name('order_grave')->where('id='.$data['order_id'])->find();
        if(!empty($data['combo'])){
            $comboinfo['pay_price'] = $info['pay_price'];
            $comboinfo['pay_time'] = $info['pay_time'];
            $comboinfo['updated_time'] = time();
            $comboinfo['success_time'] = time();
            $comboinfo['status'] = config('order_status.success');//订单状态--已完成
            $res = Db::name('OrderService')->where('id='.$data['order_id'])->update($comboinfo);
        }else{
              
            $info['store_pay_admin_id'] = session('admin_id'); //确认陵园支付的人的ID
            $info['payfor_us_time'] = strtotime($info['payfor_us_time']); 
            $info['updated_time'] = time();
            if(($order['apply_return_status']) == config('apply_return_status.default_status')) { 
                //不需要返现
                $info['success_time'] = $info['payfor_us_time'];
                $info['status'] = config('order_status.success');//订单状态--已完成
            }else{  //需要返现 
                $info['status'] = config('order_status.get_money'); //佣金状态--> 已支付
                $info['apply_return_status']  = config('apply_return_status.ok_check_return_status');//审核完成

            }
                $res = Db::name('order_grave')->where('id',$data['order_id'])->update($info); 

                if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){

                $imagesRet = uploadOne('image',Config('order_bill'),config('store_image_size.thumb'),true);
                if($imagesRet['ok'] == 0){
                    $this->error($imagesRet['error']);
                }elseif($imagesRet['ok'] ==1){
                    $delImg = Db::name('order_grave_bill')->where('order_grave_id='.$order['id'])->field('bill_image,bill_thumb_image')->find();
                        if(!empty($delImg['image']) && is_file('.'.config('public_path').$delImg['image'])){
                            unlink('.'.config('public_path').$delImg['image']);
                        }
                        if(!empty($delImg['thumb_image']) && is_file('.'.config('public_path').$delImg['thumb_image'])){
                            unlink('.'.config('public_path').$delImg['thumb_image']);
                        }
                        $billData['bill_image'] = $imagesRet['images'][0];
                        $billData['bill_thumb_image'] = $imagesRet['images'][1];
                        $billData['type'] = $data['type'];
                        $billData['order_grave_id'] = $order['id'];
                        $billData['order_grave_sn'] = $order['order_grave_sn'];
                        $billData['bill_status'] = 22;
                        $billData['updated_time'] = time();
                        $billData['created_time'] = time();
                }
                $result = Db::name('order_grave_bill')->insert($billData);
            } 

        }

        //上传票据
        

        if($res){
            $array = array('flag'=>1,'msg'=>'操作成功');
        }

        echo json_encode($array);

    }

    /**
     * 客户返现列表
     */
    public function returnpesentlist(){
        $get = input('get.');
         //客户购墓完成
        $sendarr = array(config('send_to_finance.success'),config('send_to_finance.success_finance'));
        //佣金收到
        $where['status'] = config('order_status.get_money');
        $where['state'] = array('gt',-1);

        //推送财务状态--已发送
        $tuisong = array(config('apply_return_status.need_return_status'),config('apply_return_status.ok_check_return_status'));
        $where['send_finance_status'] = array('in',$sendarr);//已发送至财务
        $where['apply_return_status'] = array('in',$tuisong);//客户返现状态--审核完成或者申请返现
        $member = Db::name('admin')->where('status',config('normal_status'))->column('id,name');
        $time = 'payfor_us_time';
        $data = $this->comlist($where,$get,$time);
        $page = $data->render();
        
        $this->assign('member',$member);
        $this->assign('data',$data);
        $this->assign('page',$page);
        $this->assign('storeMember',getStoreMember());
        return $this->fetch('returnpesentlist');
    }

    /**
     * 查询返现客户信息
     */
    public function returnpesentmsg(){
        $id = input('id');
        if($id){
            $msg = Db::name('order_grave')->where('id',$id)->field('id,buyer,mobile,bank_name,bank_member_name,bank_id,return_money,reservation_person,reservation_phone,paid_in_amount')->find();
            if($msg){
                $array = array('flag'=>1,'data'=>$msg);
            }else{
                $array = array('flag'=>0,'data'=>'暂无数据');
            }
            echo json_encode($array);
        }
    }

    /**
     * 返现完成提交修改
     */
    public function returnpesentsave(){
        $data = input('post.');
        $array = array('flag'=>1,'msg'=>'操作失败');
        $order = Db::name('order_grave')->where('id='.$data['id'])->find();
     
        if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){

            $imagesRet = uploadOne('image',Config('order_bill'),config('store_image_size.thumb'),true);
            if($imagesRet['ok'] == 0){
                $this->error($imagesRet['error']);
            }elseif($imagesRet['ok'] ==1){
                $delImg = Db::name('order_grave_bill')->where('order_grave_id='.$order['id'])->field('bill_image,bill_thumb_image')->find();
                    if(!empty($delImg['image']) && is_file('.'.config('public_path').$delImg['image'])){
                        unlink('.'.config('public_path').$delImg['image']);
                    }
                    if(!empty($delImg['thumb_image']) && is_file('.'.config('public_path').$delImg['thumb_image'])){
                        unlink('.'.config('public_path').$delImg['thumb_image']);
                    }
                $billData['bill_image'] = $imagesRet['images'][0];
                $billData['bill_thumb_image'] = $imagesRet['images'][1];
                $billData['type'] = $data['type'];
                $billData['order_grave_id'] = $order['id'];
                $billData['order_grave_sn'] = $order['order_grave_sn'];
                $billData['bill_status'] = 22;
                $billData['updated_time'] = time();
                $billData['created_time'] = time();
            }
            $result = Db::name('order_grave_bill')->insert($billData);
        }

        $data['return_pay_admin_id'] = session('admin_id'); //确认返现的人的ID
        $data['status'] = config('order_status.success');//返现完成--订单完成
        $data['success_time'] = time();
        $data['apply_return_status'] = config('apply_return_status.ok_check_return_status');//返现状态--完成
        $data['payfor_customer_time'] = time();//返现给客户的时间
        
        unset($data['type']);
        $res = Db::name('order_grave')->update($data);
        if($res){
            $array = array('flag'=>1,'msg'=>'操作成功');
        }

        echo json_encode($array); 
    }


    /*******************财务退单列表功能开始****************/
    /**
     * 点击确认支付弹出模态框
     */
     public function paymoney(){
            $id = input('id');
            $data = db('OrderGrave')->where('id='.$id)->field('id,brokerage_percent,brokerage_money,paid_in_amount,return_fact_money,back_money')->find();
            $result['data'] = $data;
            $result['flag'] = 1; 
            echo json_encode($result);
     }
     /**
      * 提交支付数据和票据
      */
     public function paydata(){
        $data = input('post.');
        if($data){
            //开启事务
            Db::startTrans();
            //上传票据图片
            if ($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])) {
                $ret = uploadOne('image', Config('order_bill'),config('store_image_size.thumb'),true);
                if ($ret['ok'] == 0) {
                    $this->error = $ret['error'];
                    $result = array('flag'=>0,'msg'=>'操作(票据上传)失败！');
                    echo json_encode($result);die;
                }elseif($imagesRet['ok'] ==1){
                    $delImg = Db::name('order_grave_bill')->where('order_grave_id='.$data['id'])->field('bill_image,bill_thumb_image')->find();
                        if(!empty($delImg['image']) && is_file('.'.config('public_path').$delImg['image'])){
                            unlink('.'.config('public_path').$delImg['image']);
                        }
                        if(!empty($delImg['thumb_image']) && is_file('.'.config('public_path').$delImg['thumb_image'])){
                            unlink('.'.config('public_path').$delImg['thumb_image']);
                        }
                        $info['bill_image'] = config('img_host') . $ret['images'][0];
                        $info['order_grave_id'] = $data['id'];
                        $info['type'] = config('bill_type.back');
                        $info['created_time'] = time();
                        $billRes = Db::name('OrderGraveBill')->insert($info);
                        if(!$billRes){
                            $result = array('flag'=>0,'msg'=>'图片信息写入失败！');
                            echo json_encode($result); die;
                        }
                    }
            }
            $datas['back_fact_money'] = $data['back_fact_money'];
            $datas['status'] = config('order_status.back_success');
            $datas['updated_time'] = time();
            $datas['back_money_time'] = date('y-m-d h:i:s',time());
            $ending = Db::name('order_grave')->where('id='.$data['id'])->update($datas);
            if($ending){
                Db::commit();
                $result = array('flag' => 1, 'msg' => '操作成功！');
                echo json_encode($result);
            }else {
                Db::rollback();
            } 
        }
    }


    /*******************财务退单列表功能结束****************/

    /**
     * 财务完成订单
     * @return [type] [description]
     */
    public function finish(){
        $get = input('get.');
        $arr = [
            config('order_status.success'),
            config('order_status.stop')
        ];
        if(!empty($get['store_name'])){
            $where['store_name'] = ['like','%'.$get['store_name'].'%'];
        }
        if(!empty($get['mobile'])){
            $where['reservation_phone|mobile'] = ['like','%'.$get['mobile'].'%'];
        }
        $where['store_status'] = ['gt',0];
        $where['status'] = ['in',$arr]; //订单状态  --已完成
        $where['state'] = ['gt',-1];
        $time = 'payfor_us_time';
        $list = $this->comlist($where,$get,$time);
        $page = $list->render();
        $this->assign('order_flow',$this->getBusinessMen(false,false));
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('storeMember',getStoreMember());
        return $this->fetch();
    }

    /**
     * 订单详情
     */
    public function finishdetail(){
        $orderId = input('orderId');
        $title = Db::name('OrderGrave')->where('id='.$orderId)->find();
        $this->orderDetail($orderId);
        $this->assign('business',$this->getBusinessMen());
        $this->assign('title',$title);
        $this->assign('orderId',$orderId);
        return $this->fetch('finishdetail');
    }

    //订单信息
    private function orderDetail($id){
        $order = Db::name('OrderGrave');
        $where['id'] = $id;
        //关联墓位信息  车辆信息 工作人员信息
        $data = OrderGrave::with(['findviewtomb'])->where($where)->find();
        $provinceid = $data['province_id'];
        $cityid = $data['city_id']; 
        if($provinceid){
            $zone['province'] = Db::name('region')->where('id',$provinceid)->field('name')->find();
            if($cityid){
                $zone['city'] = Db::name('region')->where('id',$cityid)->field('name')->find();
            }
        }
        $server = input('server.');
        $httpHost = 'http://'.$server['HTTP_HOST'].'/';
        $preUrl = $server['HTTP_REFERER'];
        $path = str_replace('.html','',str_replace($httpHost,'',$preUrl));
        $this->assign('path',$path);
        //获取商务人员id
        $this->assign('order_flow',$this->getBusinessMen(false,false));
        $this->assign('tomb_user_status',config('tomb_user_status'));
        $this->assign('sex',config('tomb_sex'));
        $this->assign('purpose',config('view_tomb_intention'));
        $this->assign('order_status_change',config('order_status_change'));
        $this->assign('order_type',config('order_type'));
        $this->assign('view_tomb_vehicle',config('view_tomb_vehicle'));
        $this->assign('call_arr',config('call_type'));
        $this->assign('zone',$zone);
        $this->assign('data',$data);
    }


    /**
     * 套餐费用支付列表
     */
    public function payforcombo(){
          $getdata = input('get.');
          if($getdata){
            if(!empty($getdata['order_service_sn'])){
                $where['order_service_sn'] = array('like','%'.$getdata['order_service_sn'].'%');
            }
            if(!empty($getdata['member_contact'])){
                $where['member_contact'] = array('like','%'.$getdata['member_contact'].'%');
            }
         }

         
         $where['state'] = array('gt',-1);//未删除
         $where['status'] = config('order_status.check_success');
         $where['send_finance_status'] = config('send_finance_status.send_finance');
         $pageSize = Config('page_size');
         $data = Db::name('OrderService')->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $getdata]);
         $page = $data->render();
         $alladmin = $this->getBusinessMen(false,false);
         $alladmin[0] = '';
         $this->assign('storeMember',getStoreMember());
         $this->assign('order_flow',$alladmin);
         $this->assign('data', $data); // 赋值数据集
         $this->assign('page', $page);
         return $this->fetch();

    }
}