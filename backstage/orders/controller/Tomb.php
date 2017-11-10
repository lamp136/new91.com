<?php
namespace back\orders\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use common\sendmsg\SendMsg;
use think\Session;//session类
use back\extra\model\OrderGrave;
use back\extra\model\OrderViewTomb;
use back\extra\model\AppointCar;
use back\extra\model\Store;
use back\extra\model\OrderGraveMsg;
use common\phpmailer\phpmailer;



/**
 * 墓地订单 订单控制器
 */
class Tomb extends Base
{   

    public function getCondition(){
        $where = array();
        $getdata = input('get.');
        if($getdata){
            if(!empty($getdata['store_name'])){
                $where['store_name'] = $getdata['store_name'];
            }
            if(!empty($getdata['mobile'])){
                $where['mobile'] = $getdata['mobile'];
            }
            if(!empty($appointment_time)){
                $where['appoint_time'] = strtotime($appointment_time);
            }
            //商务跟踪权限判断
            $tmp = session('businessPrivilege');

            if(!empty($tmp)){
                $where['order_flow_id'] = session('admin_id');
            }
            return $where;
        }
    }


    /**商务跟踪判断**/
    public function power(){
        $where = array();
        if(session('?admin_id')){
          $ret=  Db::name('RoleUser')->where('user_id='.session('admin_id'))->field('role_job_id')->find();
          $level = Db::name('Role')->where('id='.$ret['role_job_id'])->field('level')->find(); 
          if($level['level'] == 3){
            $where = session('admin_id');
          }
        }
        return $where;
    }
    /**
     * 新增订单
     *
     * @return void
     */
    public function add() {
        if(Request::instance()->isPost()){
            $data = input('post.');
            $flag = '1';
            $this->notvipextend($data,$flag);
        }else{
            $where['level'] = config('normal_status');
            $where['status'] = config('normal_status');
            $province = Db::name('region')->where($where)->column('id,name');
            $this->assign('province',$province);
            return $this->fetch('add');
        }
    }

     /**
      * 封装非会员添加*
      * @param  [array] $data [传入的数组]
      * @param  [int]   $flag [flag=1添加操作;flag=2更新操作]
      * @return void
      */
    private function notvipextend($data,$flag){
        $info =  $data['info'];

        if($info['call_type'] ==  '1'){//购墓
            $orderinfo['status'] = config('order_status.default');
        }else{
            $orderinfo['status'] = config('order_status.other');
        }
        $store = Db::name('store')->where('id',$info['store_id'])->field('id,name,member_status')->find();
            if($store['member_status'] ==0){  
                $orderInfo['province_id'] = $info['province_id'];
                $orderInfo['city_id'] = $info['city_id'];
                $orderInfo['store_id'] = $info['store_id'];
                $orderInfo['store_name'] = $store['name'];
                $orderInfo['store_status'] = $store['member_status'];
                $orderInfo['reservation_person'] = $info['reservation_person'];
                $orderInfo['reservation_phone'] = $info['reservation_phone'];
                $orderInfo['reservation_landline'] = $info['reservation_landline'];
                // $orderInfo['status'] = config('default_status');
                $orderInfo['order_grave_sn'] = makeSn();
                $orderInfo['order_type'] = $info['order_type'];
                $orderInfo['reservation_sex'] = $info['reservation_sex'];
                $orderInfo['reservation_age'] = $info['reservation_age'];
                $orderInfo['reservation_wechat'] = $info['reservation_wechat'];
                $orderInfo['reservation_qq'] = $info['reservation_qq'];
                $orderInfo['remark'] = $info['remark'];
                $orderInfo['budgeted_price'] = $info['budgeted_price'];
                $orderInfo['degree'] = $info['degree'];
                $orderInfo['tomb_user'] = $info['tomb_user'];
                $orderInfo['tomb_user_age'] = $info['tomb_user_age'];
                $orderInfo['tomb_user_sex'] = $info['tomb_user_sex'];
                $orderInfo['is_live'] = $info['is_live'];
                if(!empty($info['is_return_visit'])){
                     $orderInfo['is_return_visit'] = $info['is_return_visit'];
                }
                $orderInfo['return_visit_time'] = $info['return_visit_time'];
                $orderInfo['demand'] = $info['demand'];
                if(!empty($info['is_alive'])){
                     $orderInfo['is_alive'] = $info['is_alive'];
                }
                $orderInfo['created_time'] = time();
                if(!empty($info['appoint_time'])){
                     $orderInfo['appoint_time'] = strtotime($info['appoint_time']);
                }
                $orderInfo['order_flow_id'] = session('admin_id');
                $order = Db::name('order_grave')->insert($orderInfo);
                if($order){
                    $this->success('操作成功',url('orders/Tomb/notvip'));        
                }                    
            }else{
                $this->extend($data,$store,$flag);
            }
     }
     /**
     * 封装会员添加
     */
    private function extend($data,$store,$flag) {
        $info = $data['info'];

        if($info['call_type'] ==  '1'){//来电类型  -购墓
            $info['status'] = config('order_status.default');
        }else{
            $info['status'] = config('order_status.other');//其它订单
        }
        if($info['return_visit_time']){
            $info['return_visit_time'] = strtotime($info['return_visit_time']); 
        }

        $info['appoint_time'] = strtotime($info['appoint_time']); 
        $info['order_flow_id'] = session('admin_id');
        $info['store_status'] = $store['member_status']; 
        $info['option_id'] = session('admin_id');
        $info['remark']  =  $info['remark'];

        $memberInfo = Db::name('member')->where("mobile='".$info['reservation_phone']."'")->find();//查询用户是否存在
        // 开始事务
        Db::startTrans();
        if(!empty($memberInfo)){
            //用户存在 不用插入用户
            $info['member_id'] = $memberInfo['id'];
        }else{
            //用户不存在,则重新插入用户
            $memberInfo['member_type']='2';
            $memberInfo['name']=$info['reservation_person'];
            $memberInfo['mobile']=$info['reservation_phone'];
            $memberInfo['check_mobile']= 1;
            $memberInfo['password']= encryptHome($info['reservation_phone']);
            $memberInfo['status']=config('normal_status');
            $memberInfo['created_time']= date("Y-m-d H:i:s",time());
            $memberInfo['updated_time']= date("Y-m-d H:i:s",time());

            $resultdata = Db::name('member')->insertGetId($memberInfo);
            if(!$resultdata){
                $this->error('用户新增失败');die;
            }else{
                $info['member_id'] = $resultdata;
            }
            
        }
        //订单的信息增加
        $info['store_id'] = $store['id'];
        $info['store_name'] = $store['name'];
        $info['created_time'] = time();
        // $info['status'] = config('order_status.default');
        $info['order_grave_sn'] = makeSn();
        if($flag=='1'){
            $orderdata =  Db::name('order_grave')->insertGetId($info);
            if($orderdata){
                $order_id = $orderdata;
                $customMsg['order_id'] = $order_id;

            }else {
                Db::rollback();
                $this->error('操作失败');die;
            }
        }else{//更新操作
            $order_id = $data['id'];
            $info['updated_time'] = time();
            $orderupdate = Db::name('order_grave')->where('id='.$order_id)->update($info);
            if(!$orderupdate){
                Db::rollback();
                $this->error('更新失败');die;
            }
        }
        //客户短信的插入
        $customMsg['order_id'] = $order_id;
        $customMsg['classify'] = '1';
        $customMsg['name'] = $info['reservation_person'];
        $customMsg['mobile'] = $info['reservation_phone'];
        $customMsg['msg'] = $data['client_msg'];
        $customMsg['status'] = config('default_status');
        $customMsg['created_time'] = time();
        $customMsg['admin_id'] = session('admin_id');
        if($flag == '1'){
            if($data['client_msg']!='') {
                if(!(Db::name('order_grave_msg')->insert($customMsg))){
                    Db::rollback();
                    $this->error('操作失败');die;
                }
            }
        }else{//更新客户短信信息操作
            $customMsg['created_time'] = time();
            $customwhere['order_id'] = $order_id;
            $customwhere['classify'] = '1';

            //根据id查询是否存在
            $islive = Db::name('order_grave_msg')->where($customwhere)->find();
            if($islive){
                $customMsgresult= Db::name('order_grave_msg')->where($customwhere)->update($customMsg);
            }else{
                $customMsgresult= Db::name('order_grave_msg')->insert($customMsg);
            }
            if(!$customMsgresult){
                    Db::rollback();
                    $this->error('操作失败');die;
            }
            
        }
        //商家短信的插入/更新
        $num = count($data['contact_mobile']);
        $membwhere['order_id'] = $order_id;
        $membwhere['classify'] = '2';
        unset($islive);
        $islive = Db::name('order_grave_msg')->where($membwhere)->find();    
        if($num == 1){
            $memberMsg['order_id'] = $order_id;
            $memberMsg['classify'] = '2';
            $memberMsg['name'] = $data['contact_name'][0];
            $memberMsg['mobile'] = $data['contact_mobile'][0];
            
            $memberMsg['msg'] =  $data['contact_msg'][0];
            $memberMsg['status'] = config('default_status');
            $memberMsg['created_time'] = time();
            $memberMsg['admin_id'] = session('admin_id');
            if($flag =='1'){
                $resultOrder = Db::name('order_grave_msg')->insert($memberMsg);
                if(!$resultOrder){
                    Db::rollback();
                    $this->error('添加商家短信失败');die;
                }
            }else{
                
                $memberMsg['created_time'] = time();
                $memberwhere['order_id'] = $order_id;
                $memberwhere['classify'] = '2';
                if($islive){
                     $resultOrder = Db::name('order_grave_msg')->where($memberwhere)->update($memberMsg);
                 }else{
                     $resultOrder = Db::name('order_grave_msg')->insert($memberMsg);
                 }

                if(!$resultOrder){
                    Db::rollback();
                    $this->error('更新商家短信失败');die;
                }
            }
        }elseif($num > 1){
            for($i=0;$i<$num;$i++){
                $memberMsg[$i]['order_id'] = $order_id;
                $memberMsg[$i]['classify'] = '2';
                $memberMsg[$i]['name'] = $data['contact_name'][$i];
                $memberMsg[$i]['mobile'] = $data['contact_mobile'][$i];
                $memberMsg[$i]['msg'] =  $data['contact_msg'][$i];
                $memberMsg[$i]['status'] = config('default_status');
                $memberMsg[$i]['created_time'] = time();
                $memberMsg[$i]['admin_id'] = session('admin_id');       
             }
             if($flag == '1'){
                 if(!Db::name('order_grave_msg')->insertAll($memberMsg)){
                    Db::rollback();
                    $this->error('添加商家短信失败');die;
                }
             }else{
                $sermap['order_id'] = $data['id'];
                $sermap['classify'] = 2;
                $server = Db::name('OrderGraveMsg')->where($sermap)->select();
                if($server){
                    $ser_status['status'] = config('delete_status');
                    Db::name('order_grave_msg')->where($sermap)->update($ser_status); 
                }
                $ordermsg = new OrderGraveMsg;
                if(!$ordermsg->saveAll($memberMsg)){
                    Db::rollback();
                    $this->error('更新商家短信失败');die;
                }
             }
        }
   
        Db::commit();
        $this->success('操作成功',url('/orders/Tomb/appoint'));
    }

     /**
     * 获取市区列表(添加)
     * @return json
     */
    public function getcity(){
        $pid = input('get.pid');
        $result = ['code' => 0,'data' => []];
        if($pid){
            $cityData = $this->cityList($pid);
            if($cityData){
                $result = ['code' => 1,'data' => $cityData];
            }
        }

        echo json_encode($result);
    }

    /**
     * 获取陵园列表(添加)
     */
    public function getcemetery(){
        $result = array('flg'=>0,'data'=>'');
        $info = input('post.');
        $where['province_id'] = $info['provinceId'];
        $where['city_id'] = $info['cityId'];
        $where['category_id'] = config('category_cemetery_id');
        $where['status'] = config('normal_status');
        $list = Db::name('store')->where($where)->field('id,name,member_status')->select();
        if($list){
            $result = array('flg'=>1,'data'=>$list);
        }

        echo json_encode($result);
    }

    /**
     * 获取陵园详情(添加)
     */
    public function getcemeterydetail(){
        $storeId = input('storeId');
        $array = array('flag'=>0,'data'=>'');
        if($storeId){
            $storedetail = Store::with(['storecontact'])->where('id',$storeId)->field('id,profiles_id,member_status')->find();
            $profiles = Db::name('store_profiles')->where('id',$storedetail['profiles_id'])->field('return_amount')->find();
            $storedetail['return_amount'] = $profiles['return_amount'];
            if($storedetail){
                $array = array('flag'=>1,'data'=>$storedetail);
            }
        }
        echo json_encode($array);

    }

    /**
     * 添加购买人
     */
    public function addbuyer(){
        $data = input('post.');
        $info = $data['info'];

        $arr = array('flag'=>0,'msg'=>'添加失败');
        if($info['id']){
            $res = Db::name('order_grave')->update($info);
            if($res){
                $arr = array('flag'=>1,'msg'=>'添加成功');
            }else if($res ==0){
                $arr = array('flag'=>1,'msg'=>'没有任何更改');
            }
        }
        echo json_encode($arr);
    }



    /**
     * 预约订单
     * 
     * @return void
     */
    public function appoint() {
        $where['status'] = config('order_status.default');
        $where['store_status'] = array('gt',0);//商家状态大于0
        $where['state'] = array('gt',-1);//未删除
        $time = 'created_time';//按创建时间排序
        $bool = true;//是否开启商务跟踪条件   
        $this->_comlist($where,$time,$bool);//调用公共方法
        //紧急程度
        $jinji = config('degree');
        $call_type = config('call_type');
        $this->assign('jinji',$jinji);
        $this->assign('call_type',$call_type);
        return $this->fetch();
    }

    /**
     * 列表页共同方法
     * @param  $where 条件
     * @param  $get   input('get.');分页用
     * @param  $time  按什么时间排序
     * @param  return array();
     * 
     */
    public function comlist($where,$get,$time){
        $order = Db::name('OrderGrave');
        $searchCondition = $this->getCondition();
        if(!empty($searchCondition['mobile'])){
            $where['reservation_phone'] = $searchCondition['mobile'];
        }
        if(!empty($searchCondition['store_name'])){
            $where['store_name'] = $searchCondition['store_name'];
        }
        $pageSize = Config('page_size');
        $data = OrderGrave::with(array('findmember'))->where($where)->order(''.$time.' desc')->paginate($pageSize,false,['query' => $get]);
        return $data;
    }

    
    // /**
    //  * 有意向订单列表
    //  * @param  input|void;
    //  * @return  output|void;
    //  */
    // public function interest() {
    //     $where['status'] = config('order_status.interesting');
    //     $where['store_status'] = array('gt',0);//商家状态大于0
    //     $where['state'] = array('gt',-1);//未删除
    //     $time = 'interest_time';//按创建时间排序
    //     $bool = true;//是否开启商务跟踪条件   
    //     $this->_comlist($where,$time,$bool);
    //     return $this->fetch();
    // }

    /**
     * 订单类核心方法
     * @param  array   $where 查询条件
     * @param  string  $time  时间排序字段
     * @param  boolean $bool  列表查看权限，默认true
     * @return void
     */
    private function _comlist($where,$time,$bool=true){
        //搜索条件
        $getdata = input('get.');
        if($getdata){
            if(!empty($getdata['storename'])){
                $where['store_name'] = array('like','%'.$getdata['storename'].'%');
            }
            if(!empty($getdata['phone'])){
                $where['mobile|reservation_phone'] = array('like','%'.$getdata['phone'].'%');
            }
            if(!empty($getdata['store_id'])){
                $where['store_id'] = $getdata['store_id'];
            }
           
        }
        //跟踪条件
        if($bool){
            $adminname  = session('login_name');
            if($adminname != config('admin_name')){
                $admindata = $this->getadminid();
                if(is_array($admindata)){
                    $where['order_flow_id'] = array('in',$admindata);
                }
            }
        }
        //数据处理
        $pageSize = Config('page_size');
        $field = 'id,order_grave_sn,store_id,reservation_person,reservation_phone,reservation_landline,created_time,interest_time,store_name,store_status,pushed_status,order_flow_id,degree,mobile,appoint_time,status,tomb_price,brokerage_percent,brokerage_money,paid_in_amount,return_percent,return_money,payfor_us_time,return_fact_money,back_reason,updated_time,reason,apply_return_status,member_id,send_finance_status,buyer,contract_time,back_money,back_fact_money,back_apply_time,store_fact_name,store_fact_id,store_fact_status';
        $data = OrderGrave::with('findmember')->where($where)->field($field)->order(''.$time.' desc')->paginate($pageSize,false,['query' => $getdata]);
        $datacount = count($data);
        
        $orderId = array();
        for($i=0;$i<$datacount;$i++){
            $orderId[] = $data[$i]['id'];
        }
        $datalist = array();
        if(!empty($orderId)){
            $appWhere['id'] = array('in',$orderId);
            $datalist = OrderGrave::withCount('AppointCar',false)->field('id')->where($appWhere)->select();
            $ordercount = count($data);
            $appcount = count($datalist);
            for($i=0;$i<$ordercount;$i++){
                for($j=0;$j<$appcount;$j++){
                    if($data[$i]['id'] == $datalist[$j]['id']){
                        $data[$i]['appcount'] = $datalist[$j]['appoint_car_count']; 
                    }
                }
            }
        }
        $page = $data->render();
        $alladmin = $this->getBusinessMen(false,false);
        $alladmin[0] = '';
        $this->assign('page',$page);
        $this->assign('order_flow',$alladmin);
        $this->assign('storeMember',getStoreMember());
        $this->assign('data',$data);
    }

    /**
     * 获取登录者下面所有用户ID(包括自己)
     * @return array
     */
    private function getadminid(){
        $adminid  = session('admin_id');
        $data = array();
        if($adminid){
            $userWhere['user_id'] = $adminid;
            // $role = Db::name('RoleUser')->where($userWhere)->field('role_job_id')->find();//todo????
            $role = Db::name('RoleUser')->where($userWhere)->column('role_id,role_job_id');//todo????           
            // $roleid= $role['role_job_id'];
            $roleid= $role; //???

            $roledata = Db::name('role')->field('id,pid,level')->select();
            //判断登录用户所在职位是不是顶级职位
            foreach($roledata as $val){
                if($val['level'] == 0 &&  in_array($val['id'],$roleid)){
                    $data = false;
                }
            }
            //如果不是顶级职位则只能看自己及下边的订单
            if(is_array($data)){
                $rolepartid = $this->getroleid($roledata,$roleid);
                $roleWhere['role_job_id'] = ['in',$rolepartid];
                $datas= Db::name('RoleUser')->where($roleWhere)->field('user_id')->select();
                foreach($datas as $val){
                    $data[] = $val['user_id'];
                }           
                $data[] = $adminid;
            }
        }
        return $data;  
    }

    /**
     * 获取所有后代roleID
     * @param array $roleData
     * @param int $pid
     * @return array
     */
    private function getroleid($roleData,$pid=array()) {
        static $datas;
        foreach ($roleData as $val) {
            if(in_array($val['pid'],$pid)){
                $datas[] = $val['id'];
            }
        }

        return $datas;
    }

    /**
     * 交定金列表
     * @param  input|void;
     * @return  output|void;
     */
    public function deposit() {
        $where['status'] = config('order_status.deposit');
        $where['store_status'] = array('gt',0);//商家状态大于0
        $where['state'] = array('gt',-1);//未删除
        $time = 'created_time';//按创建时间排序
        $bool = true;//是否开启商务跟踪条件   
        $this->_comlist($where,$time,$bool);
        return $this->fetch();
    }

    /**
     * 审核订单列表
     * @param  input|void;
     * @return  output|void;
     */
    public function check() {
        $where['status'] = array('in',array(Config('order_status')['ok'],Config('order_status')['check_success']));
        $where['send_finance_status'] = Config('send_finance_status')['default'];
        $where['store_status'] = array('gt',0);//商家状态大于0
        $where['state'] = array('gt',-1);//未删除
        $time = 'created_time';//按创建时间排序
        $bool = true;//是否开启商务跟踪条件   
        $this->_comlist($where,$time,$bool);
        return $this->fetch();
    }

    /**
     * 审核数据
     * @param  input|void;
     * @return  string(json);
     */
    public function audit(){
        $result = array('flag'=>0,'msg'=>'操作失败');
        $orderId = input('orderId');
        if(!empty($orderId)){
            if(Request::instance()->isPost()){
                //开户事务
                Db::startTrans();
                $post = input('post.');
                $info = $post['info'];
                $info['updated_time']  = time();
                $info['status'] = Config('order_status')['check_success'];
                //上传图片
                if($_FILES['image']['name']['0']){
                    $imagesRet = upload('image',Config('order_bill'),array(array(225,220,6)));
                    if($imagesRet['ok'] != 0){
                        $imageData['order_grave_id'] = $orderId;
                        $imageData['type'] = 2;
                        $imageData['updated_time']  = time();
                        $imageData['created_time']  = time();
                        foreach($imagesRet['images'] as $k=>$v){
                            $imageData['bill_image'] = $v;
                            foreach($imagesRet['thumb'][$k] as $value){
                                $imageData['bill_thumb_image'] = $value;
                            }
                            $res = Db::name('OrderGraveBill')->insert($imageData);
                            if(!$res){
                                // 回滚事务
                                Db::rollback();
                                echo json_encode($result);die;
                            }
                        }
                    }
                }
                //订单表数据
                if(Db::name('OrderGrave')->where('id',$orderId)->update($info)){
                    // 提交事务
                    Db::commit();
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功！';
                }else{
                    // 回滚事务
                    Db::rollback();
                    echo json_encode($result);die;
                }
            }else{
                //查找订单信息
                $field = 'id,total_price,tomb_price,brokerage_percent,brokerage_money,apply_return_status,return_percent,return_money,bank_name,bank_member_name,bank_id,reason';
                $data = Db::name('OrderGrave')->where('id',$orderId)->field($field)->find();
                //根据商家查找合同表中的佣金
                $storeId = input('storeId');
                if(!empty($storeId)){
                    $profiles = Db::name('store')->where('id',$storeId)->field('profiles_id')->find();
                    if(!empty($profiles['profiles_id'])){
                        $brokeragePercent = Db::name('StoreProfiles')->where('id',$profiles['profiles_id'])->field('return_amount')->find();
                    }
                }
                if(!empty($brokeragePercent)){
                    $data['brokerage_percent'] = $brokeragePercent['return_amount'];
                }
                $result['flag'] = 1;
                $result['msg'] = '操作成功！';
                $result['data'] = $data;
            }
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
            $data['status'] = config('order_status.check_success');
            $data['send_finance_status'] = Config('send_finance_status')['send_finance'];
            $data['send_finance_time']  = time();
            $data['updated_time']  = time();
            if(Db::name('OrderGrave')->where('id',$orderId)->update($data)){
                $result['flag'] = 1;
                $result['msg'] = '操作成功!';
            }
        }
        echo json_encode($result);
    }

    /**
     * 审核列表退单
     * @param  input|void;
     * @return  outout|string(json);
     */
    public function checkback(){
        $result = array('flag'=>0,'msg'=>'操作失败');
        $orderId = input('orderId');
        if(!empty($orderId)){
            $data['status'] = config('order_status.back_success');
            $data['updated_time']  = time();
            $data['back_reason'] = input('back_reason');
            if(Db::name('OrderGrave')->where('id',$orderId)->update($data)){
                $result['flag'] = 1;
                $result['msg'] = '操作成功!';
            }
        }
        echo json_encode($result);
    }
        
    /**
     * 订单详情
     * 
     * @return void
     */
    public function detail() {
        $orderId = input('orderId');
        $items = input('items');
        $title = db('OrderGrave')->where('id='.$orderId)->find();
        if(!empty($orderId)){
            switch($items){
                case 'orderDetail'://订单信息
                    $this->orderDetail($orderId);
                    $this->assign('endstr','订单信息');
                break;
                case 'reVisit': //回访跟踪
                    $this->reVisit($orderId);
                    $this->assign('endstr','回访跟踪');
                break;
                case 'viewTomb'://看墓记录
                    $this->viewTomb($orderId);
                    $this->assign('endstr','看墓记录');
                break;
                case 'messages'://短信
                    $this->messages($orderId);
                    $this->assign('endstr','短信');
                break;
                case 'appointCar'://车辆预约
                    $this->appointCar($orderId);
                    $this->assign('endstr','车辆预约');
                break;
                case 'addViewTomb'://填写看墓记录
                    $this->addViewTomb($orderId);
                    $this->assign('endstr','填写看墓记录');
                break;
                case 'buyerlist':
                    $this->buyerlist($orderId);
                    $this->assign('endstr','添加购买人');
            }
                
        }else{
            $this->error('操作失败！');
        }

        //面包屑导航
        $server = input('server.');
        $httpHost = 'http://'.$server['HTTP_HOST'].'/';
        $preUrl = $server['HTTP_REFERER'];
        if(input('returnurl')){
            $preUrl = input('returnurl');
        }
        $path = str_replace('.html','',str_replace($httpHost,'',$preUrl));
        if(strpos($path,'page')){
            $cutUrl = explode('?',$path);
            $path = $cutUrl[0];
        }
        $string = Db::name('privilege')->where('name',$path)->field('title')->find();
        $bread = array('url'=>$preUrl,'string'=>$string['title']);


        $this->assign('path',$path);
        $this->assign('bread',$bread);
        $this->assign('business',$this->getBusinessMen());
        $this->assign('title',$title);
        $this->assign('orderId',$orderId);
        $this->assign('items',$items);
        return $this->fetch('detail', ['id'=>$orderId]);
    }

    //添加购买人
    private function buyerlist($id){
        $buyer = Db::name('order_grave')->field('buyer,mobile,buyer_wechat,buyer_qq,buyer_landline')->where('id',$id)->find();
       
        if($buyer){
            $this->assign('buyer',$buyer);
        }

    }

    //订单信息
    private function orderDetail($id){
        $order = db::name('OrderGrave');
        $where['id'] = $id;
        //关联墓位信息  车辆信息 工作人员信息
        $data = OrderGrave::with(array('findviewtomb'))->where($where)->order('created_time desc')->select();
        $businessMan = $this->getBusinessMen(false,false);
        $originFlowMans = '';
        if(!empty($data[0]['origin_flow_id'])){
            $expArr = explode(',', $data[0]['origin_flow_id']);
            foreach ($expArr as $key => $val) {
                if(array_key_exists($val, $businessMan)){
                    $impString[] = $businessMan[$val];
                }
            }
            $originFlowMans = implode(',', $impString);
        }
        $data[0]['origin_flow_man'] = $originFlowMans;
            //查询省、市名称开始
        $list = Db::name('OrderGrave')->where($where)->field('province_id,city_id')->find();
        $provinceid = $list['province_id'];
        $cityid = $list['city_id'];
        $zone = $this->findzone($provinceid,$cityid);
        //查询省、市名称结束
        //获取商务人员id
        $this->assign('order_flow',$businessMan);
        //来电类型
        $call_arr = config('call_type');
        //墓位使用人状态
        $tomb_user_status = config('tomb_user_status');
        //订单来源
        $order_type = config('order_type');
        //意向程度
        $purpose = config('view_tomb_intention');
        //订单状态转换
        $order_status_change = config('order_status_change');
        //性别
        $sex = config('tomb_sex');
        //车辆
        $view_tomb_vehicle = config('view_tomb_vehicle');
        $this->assign('tomb_user_status',$tomb_user_status);
        $this->assign('sex',$sex);
        $this->assign('purpose',$purpose);
        $this->assign('order_status_change',$order_status_change);
        $this->assign('order_type',$order_type);
        $this->assign('view_tomb_vehicle',$view_tomb_vehicle);
        $this->assign('call_arr',$call_arr);
        $this->assign('zone',$zone);
        $this->assign('data',$data);
    }

    /**
     * 原始跟踪人权限
     * @return void
     */
    public function originprivilege(){

    }


    //回访跟踪
    private function reVisit($id){
        $revisit = Db::name('order_revisit')->where('order_id',$id)->order('created_time desc')->select();//回访信息列表
        $revisitcount = count($revisit);
        $orderSn = Db::name('order_grave')->where('id',$id)->field('order_grave_sn,demand')->find();
        $this->assign('demand',$orderSn['demand']);
        $this->assign('orderSn',$orderSn['order_grave_sn']);
        $this->assign('revisit',$revisit);
        $this->assign('revisitcount',$revisitcount);

    }
    //看墓记录
    private function viewTomb($id){
        $viewWhere['order_id'] = $id;
        $viewDatas = Db::name('OrderViewTomb')->where($viewWhere)->order('created_time asc')->select();
        $appointCarId = array();
        foreach($viewDatas as $val){
            $appointCarId[] = $val['appoint_car_id'];
        }
        $appointCarId = array_flip(array_flip($appointCarId));
        $appointWhere['id'] = array('in',$appointCarId);
        $appointCarData = array();
        if(!empty($appointCarId)){
            $appointCarData = AppointCar::with('driverinfo')->where($appointWhere)->select();
        }        

        $this->assign('viewDatas',$viewDatas);
        $this->assign('appointCarData',$appointCarData);
        $this->assign('viewNum',count($appointCarData));
    }

    //短信
    private function messages($id){
        $msg = db('OrderGraveMsg')->where('order_id='.$id)->select();
        $this->assign('msg',$msg);            
    }
    
    /**
     * 车辆预约
     * @param  int $id 订单id
     * @return void
     */
    private function appointCar($id){
        if($id){
            $list = AppointCar::with('driverinfo')->where('order_id',$id)->select();
            $carWhere['status'] = config('normal_status');
            $carRegion = Db::name('CarInfo')->where($carWhere)->field('province_id,province_name')->select();
            $province = '';
            if($carRegion){
                foreach($carRegion as $val){
                    $province[$val['province_id']] = $val['province_name'];
                }
            }
            $where = [
                'level' => config('normal_status'),
                'status' => config('normal_status')
            ];
            $allProvince = Db::name('region')->where($where)->column('id,name');
            $this->assign([
                'allProvince' => $allProvince,
                'province'    => $province,
                'list'        => $list
            ]);
        }
    }

    /**
     * 获取车辆信息
     * @return json
     */
    public function getCarInfo(){
        $input = input('get.');
        $result = ['code' => 0,'data' => ''];
        if(!empty($input['province'])){
            $where['province_id'] = $input['province'];
            if(!empty($input['city'])){
                $where['city_id'] = $input['city'];
            }
            $carList = Db::name('CarInfo')->where($where)->field('id,plate_number,driver,city_id,city_name')->select();
            if($carList){
                $result = ['code' => 1,'data' => $carList];
            }
        }
        echo json_encode($result);
    }

    /**
     * 获取车辆所在市区
     * @return json
     */
    public function getCarCity(){
        $province = input('get.province');
        $result = ['code' => 0,'data' => ''];
        $carCity = Db::name('CarInfo')->where('province_id',$province)->field('city_id,city_name')->distinct(true)->select();
        if($carCity){
            $result = ['code' => 1,'data' => $carCity];
        }
        echo json_encode($result);
    }

    /**
     * 添加约车记录
     * @return json
     */
    public function addappointcar(){
        if(request()->isPost()){
            $result = ['code' => 0,'msg' => '添加失败'];
            $input = input('post.');
            $data = $input;
            if($input['company_counselor']){
                $counselor = explode('-',$input['company_counselor']);
                $data['company_counselor'] = $counselor[0];
                $data['company_counselor_name'] = $counselor[1];
            }
            if($input['company_person']){
                $person = explode('-',$input['company_person']);
                $data['company_person'] = $person[0];
                $data['company_person_name'] = $person[1];
            }
            $data['created_time'] = $data['updated_time'] = time();
            $data['arrive_time'] = strtotime($input['arrive_time']);
            $ret = Db::name('AppointCar')->data($data)->insert();
            if($ret){
                $result = ['code' => 1,'msg' => '添加成功'];
            }
            echo json_encode($result);
        }
    }

    /**
     * 编辑约车记录
     * @return json
     */
    public function editappointcar(){
        if(request()->isPost()){
            $input = input('post.');
            $result = ['code' => 0,'msg' => '修改失败'];
            if($input['id']){
                $data = $input;
                $data['updated_time'] = time();
                $data['arrive_time'] = strtotime($input['arrive_time']);
                $ret = Db::name('AppointCar')->data($data)->update();
                if($ret){
                    $result = ['code' => 1,'msg' => '修改成功'];
                }
            }
        }else{
            $result = ['code' => 0,'data' => []];
            $id = input('get.id');
            if($id){
                $info = Db::name('AppointCar')->where('id',$id)->find();
                $info['arrive_time'] = date('Y-m-d H:i:s',$info['arrive_time']);
                if(!empty($info)){
                    $result = ['code' => 1,'data' => $info];
                }
            }
        }
        echo json_encode($result);
    }

    /**
     * 获取商家
     * @return json
     */
    public function obtainMerchant(){
        $input = input('get.');
        $result = ['code' => 0,'data' => []];
        if(!empty($input['province_id'])){
            $where['province_id'] = $input['province_id'];
            if(!empty($input['city_id'])){
                $where['city_id'] = $input['city_id'];
            }
            $where['status'] = config('normal_status');
            $where['category_id'] = config('category_cemetery_id');
            $storeList = Db::name('store')->where($where)->column('id,name');
            if(!empty($storeList)){
                $result = ['code' => 1,'data' => $storeList];
            }
        }

        echo json_encode($result);
    }

    /**
     * 取消约车
     * @return void
     */
    public function cancelViewCar(){
        $id = input('post.id');
        $result = ['code' => 0,'msg' => '删除失败'];
        if($id){
            $ret = Db::name('AppointCar')->where('id',$id)->delete();
            if($ret){
                $result = ['code' => 1,'msg' => '删除成功'];
            }
        }

        echo json_encode($result);
    }

    /**
     * 约车完成
     * @return json
     */
    public function finishViewCar(){
        $id = input('post.id');
        $result = ['code' => 0,'msg' => '操作失败'];
        if($id){
            $data = [
                'id'           => $id,
                'status'       => config('appoint_car_status.finish'),
                'updated_time' => time()
            ];
            $ret = Db::name('AppointCar')->data($data)->update();
            if($ret){
                $result = ['code' => 1,'msg' => '已完成'];
            }
        }
        echo json_encode($result);
    }

    //填写看墓记录
    private function addViewTomb($id){
        $appointId = Request::instance()->param('appointId');
        if(!empty($appointId)){
            $carWhere['id'] = $appointId;
            $appData = Db::name('AppointCar')->where($carWhere)->find();
            $carData = Db::name('CarInfo')->where('id = '.$appData['car_id'])->field('driver,plate_number')->find();
            $vehicle = $appData['vehicle'];
        }else{
            $carData = array();
            $appData = array();
            $vehicle = 'ture';
        }
        $province = array();
        $where['level'] = config('normal_status');
        $where['status'] = config('normal_status');
        $province = Db::name('region')->where($where)->column('id,name');
        
        $this->assign('province',$province);
        $this->assign('vehicle',$vehicle);
        $this->assign('carData',$carData);
        $this->assign('appData',$appData);
    }

    //提交看墓记录
    public function submitAddView(){
        if(Request::instance()->isPost()){
            $data = input('post.');
            //用于面包屑
            $target = $data['returnurl'];
            unset($data['returnurl']);
            //dump($data);die;
            if(!empty($data['store_id'])){
                $storeWhere['id'] = $data['store_id'];
                $storedata = Db::name('store')->where($storeWhere)->field('id,name,member_status')->find();
                $data['store_id'] = $storedata['id'];
                $data['store_names'] = $storedata['name'];
                $data['member_status'] = $storedata['member_status'];
                //写进订单数据
                $orderData['store_fact_id'] = $storedata['id'];
                $orderData['store_fact_name'] = $storedata['name'];
                $orderData['store_fact_status'] = $storedata['member_status'];
            }
            $data['admin_id'] = session('admin_id');
            $data['created_time'] = time();
            //上传图片
            if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){
                $imagesRet = uploadOne('image',Config('order_bill'));
                if($imagesRet['ok'] == 0){
                    $this->error($imagesRet['error']);
                }else{
                    /*$imageData['order_grave_id'] = $data['order_id'];
                    $imageData['type'] = 4;
                    $imageData['bill_image'] = $imagesRet['images'][0];
                    $imageData['bill_thumb_image'] = $imagesRet['images'][1]; 
                    $imageData['updated_time']  = time();
                    $imageData['created_time']  = time();
                    Db::name('OrderGraveBill')->insert($imageData);*/
                    $data['bill_image'] = $imagesRet['images'][0];
                }
            }
            //如果成交部分信息反写到订单表中
            if($data['result'] == Config('view_tomb_result_msg')['success']){
                $orderWhere['id'] = $data['order_id'];
                $orderData['store_discount'] = $data['store_discount'];
                $orderData['91_discount'] = $data['91_discount'];
                $orderData['tomb_price'] = $data['tomb_price'];
                $orderData['total_price'] = $data['total_price'];
                $orderData['status'] = Config('order_status')['ok'];
                $orderData['updated_time'] = time();
                $orderData['vehicle'] = $data['vehicle']; 
                $orderData['buyer'] = $data['buyer'];
                $orderData['tomb_address'] = $data['location'];
                $orderData['contract_time'] = time();
                Db::name('OrderGrave')->where($orderWhere)->update($orderData);
            }
            if(Db::name('OrderViewTomb')->insert($data)){
                $this->success('添加成功!',url("orders/Tomb/detail",["orderId"=>$data['order_id'],"items"=>"viewTomb",'returnurl'=>$target]));
            }
        }
        $this->error('添加失败!');
    }
    

    
    /**
     * 陵园支付佣金列表
     * 
     * @return void
     */
    public function storepay() {
        $where['status'] = config('order_status.check_success');
        $where['send_finance_status'] = config('normal_status');
        $where['state'] = array('gt',-1);
        $time = 'send_finance_time';
        $this->_comlist($where,$time,true);

        return $this->fetch('storepay');
    }

    /**
     * 获取票据
     */
    public function getbill(){
        $result = array('flag'=>0,'msg'=>'暂无数据');
        $orderId = input('order_id');
        if(!empty($orderId)){
            $where['order_grave_id'] = $orderId;
            $data = Db::name('order_grave_bill')->where($where)->order('created_time desc')->select();
            if(!empty($data)){
                $count = count($data);
                for($i=0;$i<$count;$i++){
                    $data[$i]['type'] = Config('order_bill_type')[$data[$i]['type']];
                }
                $result['flag'] = 1;
                $result['msg'] = 'ok';
                $result['data'] = $data;
            }
        }

        echo json_encode($result);
    }

    /**
     * 待返现订单列表
     * 
     * @return void
     */
    public function returnpay() {
        //客户购墓完成
        $sendarr = array(config('send_to_finance.success'),config('send_to_finance.success_finance'));
        //佣金收到
        $where['status'] = config('order_status.get_money');
        $where['state'] = array('gt',-1);

        //推送财务状态--已发送
        $tuisong = array(config('apply_return_status.need_return_status'),config('apply_return_status.ok_check_return_status'));
        $where['send_finance_status'] = array('in',$sendarr);//已发送至财务
        $where['apply_return_status'] = array('in',$tuisong);//

        $time = 'payfor_us_time';
        $this->_comlist($where,$time,true);
        /*$member = Db::name('admin')->where('status',config('normal_status'))->column('id,name');
        $this->assign('member',$member);*/
        return $this->fetch('returnpay');
    }

    /**
     * 待回访订单列表
     */
    public function waitrepay(){
        $status = array(config('order_status.default'),config('order_status.interesting'),config('order_status.deposit'));
        $where['status'] = array('in',$status);
        $where['state'] = array('gt',-1);//未删除
        $where['is_return_visit'] = config('normal_status');

        $map = [
            'status' => ['in',['1','2']]
        ];
        /**
         * [$searchStoreList 列表页陵园下拉框数据]
         */
        $searchStoreList = Db::name('store')->where($map)->field('id,name,member_status,status')->select();

        $time = 'created_time';//按创建时间排序
        $this->_comlist($where,$time,false);//调用公共方法
        $this->assign('storeMember',getStoreMember());
        $this->assign('store',$searchStoreList);
        return $this->fetch('waitrepay');
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
        $storeList = Db::name('OrderGrave')->where($where)->column('store_id,store_name');
        if(!empty($input['store_id']) && isset($input['store_id'])){
            $where['store_id'] = $input['store_id'];
        }
        if(!empty($input['mobile']) && isset($input['mobile'])){
            $where['mobile|reservation_phone'] = ['like','%'.$input['mobile'].'%'];
        }
        if(!empty($input['flow_id']) && isset($input['flow_id'])){
            $where['order_flow_id'] = $input['flow_id'];
        }
        $fields = 'id,store_id,member_id,mobile,buyer,store_status,pushed_status,appoint_time,created_time,store_name,apply_return_status,order_flow_id,status,reservation_phone,reservation_person';
        $delegateList = OrderGrave::with('flowman')->where($where)->field($fields)->order('created_time desc')->paginate(config('page_size'),false,['query' => $input]);
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
                $origin = Db::name('OrderGrave')->where('id',$info[0])->field('id,origin_flow_id')->find();
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
            $orderGrave = new OrderGrave;
            $saveAll = $orderGrave->saveAll($data);
            if($saveAll){
                $result = ['code' => 1,'msg' => '修改成功'];
            }
        }

        echo json_encode($result);
    }

    /**
     * 订单完成列表
     */
    public function compelete() {
        $get = input('get.');
        $arr = [
            config('order_status.success'),
            config('order_status.stop')
        ];
        $where['status'] = ['in',$arr]; //订单状态  --已完成
        $where['state'] = ['gt',-1];
        $time = 'payfor_us_time';
        $this->_comlist($where,$time,true);//调用公共方法
        $this->assign('order_flow',$this->getBusinessMen(false,false));
        return $this->fetch('compelete');
    }

    /**
     * 获取银行信息
     * @return json
     */
    public function getbankinfo(){
        if(request()->isPost()){
            $memberId = input('post.member');

            $memberModel  = Db::name('MemberBank');
            $data = $memberModel->field('bank,bank_type,bank_member,bank_account')->where('member_id',$memberId)->find();
            if(!empty($data)){
                $result['code'] = 1;
                $result['data'] = $data;
            }else{
                $result['code'] = 0;
                $result['data'] = '';
            }
            echo json_encode($result);
           
        }
    }

    /**
     *  后期申请返现
     */
    public function appointreturn() {
        if(request()->isPost()){
            $result = ['code'=>0,'msg'=>'操作失败'];
            $postData = input('post.');
            $order_id = $postData['id'];
            $member_id = $postData['member'];
            $orderInfo = Db::name('OrderGrave')->where('id',$order_id)->field('id,member_id,brokerage_percent,tomb_price,reason')->find();
            $memberInfo = Db::name('Member')->field('id')->find($member_id);
            Db::startTrans();
            if(!empty($orderInfo) && !empty($memberInfo) && $memberInfo['id'] == $orderInfo['member_id']){
                if($orderInfo['brokerage_percent'] <= $postData['return_pesent_percent']){
                    $result['code'] = 0;
                    $result['msg'] = '返现比率不能大于佣金比率';
                    echo json_encode($result);die;
                }
                if(!empty($_FILES['image']['tmp_name'])){
                    $imagesRet = upload('image', config('order_bill'));
                    foreach ($imagesRet['images'] as $k => $v){
                        $billData[$k]['order_grave_id'] = $orderInfo['id'];
                        $billData[$k]['bill_image']     = $v;
                        $billData[$k]['type']           = config('bill_type.cash');
                        $billData[$k]['created_time']   = time();
                        $billData[$k]['bill_status']    = config('audit_status.success');
                    }

                    $orderBillRet = Db::name('OrderGraveBill')->insertAll($billData);
                }
                if($postData['bank_info']){
                    $bankInfo = explode('-',$postData['bank_info']);
                    $memberData['bank_type']    = $bankInfo[0];
                    $memberData['bank']         = $bankInfo[1];
                    $orderData['bank_name'] = $bankInfo[1];
                }
                //用户的银行卡信息写入用户的表中
                $memberData['member_id']    = $member_id;
                $memberData['bank_account'] = $postData['bank_account'];
                $memberData['bank_member']  = $postData['bank_member'];
                $memberData['admin_id']     = Session::get('admin_id');
                $memberData['created_time'] = date('Y-m-d H:i:s');
                $member_info = Db::name('MemberBank')->where('member_id',$member_id)->find();
                if(empty($member_info)){
                    $memberRet  = Db::name('MemberBank')->insert($memberData);
                }else{
                    $memberData['id'] = $member_info['id'];
                    $memberRet = Db::name('MemberBank')->data($memberData)->update();
                }
                //返现数据写入订单表
                $orderData['id']                   = $order_id;
                $orderData['return_percent']       = $postData['return_pesent_percent'];
                $orderData['return_money']         = $postData['return_money'];
                $orderData['updated_time']         = time();
                $orderData['status']               = config('order_status.get_money');
                $orderData['apply_return_status']  = config('apply_return_status.ok_check_return_status');
                $orderData['payfor_customer_time'] = time();
                $orderRet = Db::name('OrderGrave')->data($orderData)->update();
                if($orderRet && $memberRet){
                    $result['code'] = 1;
                    $result['msg'] = '操作成功';
                    Db::commit();
                }else{
                    $result['msg'] = '数据写入失败';
                    Db::rollback();
                }
            }
        }else{
            $result['code'] = 0;
            $result['msg'] = '该信息不符合申请返现的条件';
        }
        echo json_encode($result);
    }

    /**
     * 非会员列表
     */
    public function notvip(){
        $where['store_status'] = config('default_status');
        $where['status'] = config('order_status.default');
        $where['state'] = array('gt',-1);
        $time = 'created_time';
        $bool = true;//是否开启商务跟踪条件   
        $this->_comlist($where,$time,$bool);//调用公共方法
        //紧急程度
        $jinji = config('degree');
        $this->assign('jinji',$jinji);
        return $this->fetch();
    }

    /**
     * 订单回收站
     * @return void
     */
    public function callbacks() {
        $where['state'] = config('order_status.fail');
        $time = 'updated_time';
        $bool = true;//是否开启商务跟踪条件   
        $this->_comlist($where,$time,$bool);//调用公共方法
        return $this->fetch();
    }

    /**
     * 前台留言订单列表
     */
    public function leavemessage(){
        $get = input('get.');
        
        $order = Db::name('appoint');
        $arr = array(config('appoint_status.waiting'),config('appoint_status.on_load'),config('appoint_status.refuse'),config('appoint_status.success'));
        $where['status'] = array('in',$arr);
        $where['category_id'] = config('category_cemetery_id');
        $searchCondition = $this->getCondition();
        if(!empty($searchCondition['mobile'])){
            $where['mobile'] = $searchCondition['mobile'];
        }
        $pageSize = Config('page_size');
        $data = $order->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $get]);
        $page = $data->render();
        $this->assign([
            'businessMen'    => [
                'all'  => $this->getBusinessMen(true,false),// 包括离职的商务人员
                'work' => $this->getBusinessMen(true,true)//在职的商务人员
            ], 
        ]);
        $this->assign('order_flow', $this->getBusinessMen(false,false));
        $this->assign('data', $data); // 赋值数据集
        $this->assign('page', $page);
        return $this->fetch('leavemessage');
    }


    /**
     * 修改前台留言订单跟踪人
     */
    public function changeleaveFlow(){
        $result = array('code'=>0,'msg'=>'修改失败');
        $data = input('post.');
        $id = $data['ids'];
        $flow['order_flow_id'] = $data['flow_man'];
        $final = Db::name('Appoint')->where('id='.$id)->update($flow);
        if($final){
            $result['code'] = 1;
            $result['msg'] = '修改成功';
        }
        echo  json_encode($result);
    }

    /**
     * 退单列表
     */
    public function backlist(){
        
        $time = 'back_apply_time';
        $where['status'] = array('in',array(config('order_status.stop'),config('order_status.back_success'),config('order_status.allow'))); 
        $where['state'] = array('gt',-1);//未删除
        
        $bool  = true;
        $this->_comlist($where,$time,$bool);
        $list = OrderGrave::withCount(['ordergravebill'=>function($query){
            $query->where('type',3);
        }])->select();//统计有无退单票据
        $status = config('order_status_change');
        $this->assign('list',$list);
        $this->assign('status',$status);
        return $this->fetch();
    }

 

    /****************陵园列表页各按钮功能开始********************/
    /**
     * 通过province_id和city_id查询省市名称
     * @param  传入  $provinceid , $cityid
     * @param  return 省/市名称
     */
    public function findzone($provinceid,$cityid){
        $data['province'] = db('region')->where('id='.$provinceid)->field('name')->find();
        $data['city'] = db('region')->where('id='.$cityid)->field('name')->find();
        return $data;
    }

    /**
     * 再次预约
     */
    public function appointagain(){
        if(Request::instance()->isPost()){
            $data = input('post.');
            $flag = '1';
            $this->notvipextend($data,$flag);
        }else{
            $id = input('id');
            $data = db('OrderGrave')->where('id='.$id)->find();
            $where['level'] = config('normal_status');
            $where['status'] = config('normal_status');
            $province = Db::name('region')->where($where)->column('id,name');
            $this->assign('data',$data);
            $this->assign('province',$province);
            return $this->fetch('appointagain');
        }

    }
    

    /**
     * 编辑订单信息
     */
    public function edit(){
        if(Request::instance()->isPost()){
            $data = input('post.');
            $flag = '2';
            $this->notvipextend($data,$flag);
        }else{
            $id = input('id');
            $data = OrderGrave::with(['findmember','ordergravemsg'])->where('id',$id)->find();
            if($data['return_visit_time'] != 0){
                $data['return_visit_time'] = $data['return_visit_time'];
            }
            $where['level'] = config('normal_status');
            $where['status'] = config('normal_status');
            $province = Db::name('region')->where($where)->column('id,name');
            //查询市
            if(!empty($data['province_id'])){
                $storeWhere['category_id'] = config('category_cemetery_id');
                $storeWhere['province_id'] = $data['province_id'];
                $city = Db::name('region')->where('pid',$data['province_id'])->column('id,name');
                if(!empty($data['city_id'])){
                    $storeWhere['city_id'] = $data['city_id'];
                }
                $stores = Db::name('store')->where($storeWhere)->field('id,name,member_status')->select();

                $this->assign('stores',$stores);
                $this->assign('city',$city);
            }
            $storeid = Db::name('OrderGrave')->where('id',$id)->field('store_id')->find();
            $membwhere['status'] = config('normal_status');
            $membwhere['store_id'] = $storeid['store_id'];
            $memb  = Db::name('store_contact')->where($membwhere)->field('contact_name,mobile')->select();
            $customMsg = Db::name('OrderGraveMsg')->where(['order_id' => $data['id'],'classify' => config('msg_customer'),'status' => ['egt',0]])->find();
            $this->assign('customMsg',$customMsg);
            $this->assign('data',$data);
            $this->assign('memb',$memb);
            $this->assign('province',$province);
            return $this->fetch('edit');
        }
    }

    /**
     * 陵园列表页---->购买成功
     * @param  post    id  
     * @return json
     */
     public function cooperatesuccess(){
        $postdata = input('post.');
        $orderId = $postdata['id'];
        $orderModel = Db::name('OrderGrave');
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
                        $info['contract_time'] = time();//购墓成交时间 
                        $info['store_fact_name'] = $order['store_name'];
                        $info['store_fact_id'] = $order['store_id'];
                        $info['store_fact_status'] = $order['store_status'];
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
    /*
     * 推送给陵园
     */
    public function push() {
        $postdata = input('post.');
        $orderid = $postdata['orderid'];
        $orderModel = db('orderGrave');
        $data['id'] = $orderid;
        $data['pushed_status'] = config('send_to_store.success');//已推送
        $data['pushed_time'] = time();
        $data['updated_time'] = time();

        if($orderModel->where('id='.$orderid)->update($data)){
            $result['flag']=1;
            $result['msg']='操作成功';
        }else{
            $result['flag']=0;
            $result['msg']='操作失败';
        }
        echo json_encode($result);
    }


     /**
     * 将订单转换为有意向订单
     */
    public function changeinteresting() {
        $data = input('post.');
        $order = db('OrderRevisit');
        $id = $data['id'];
        $datainfo['interest_time'] = strtotime($data['interesting_time']);
        $datainfo['status'] = config('order_status.interesting');
        $datainfo['updated_time'] = time();
        $resultdata = db('OrderGrave')->where('id='.$id)->update($datainfo);
        $info['order_id'] = $id;
        $info['admin_id'] = session('admin_id');
        $info['admin_name'] = session('login_name');
        $info['content'] = $data['reason'];
        $info['created_time'] = date('Y-m-d H:i:s');   
        $finally = $order->insert($info);
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
     * 合作失败
     */
    public function cooperatefail() {
        $postdata = input('post.');
        $orderId = $postdata['id'];
        $orderModel = Db::name('orderGrave');
        $order = $orderModel->where('id='.$orderId)->field('id,store_id,status')->find();
        if (empty($order)) {
            $data['flag'] = 0;
            $data['msg'] = '数据没找到';
        } else {
           $info['reason'] =$postdata['reason'];
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
     * 短信列表编辑功能
     */
    public function editordermsg() {
        $orderMsgModel = db('OrderGraveMsg');
        if(Request::instance()->isPost()){
            $datainfo = input('post.');
            $data['id'] = $datainfo['id'];
            $data['msg'] = $datainfo['msg'];
            $orderinfo = $orderMsgModel->where('id='.$datainfo['id'])->update($data);
            if($orderinfo){
                $result['flag']=1;
                $result['msg']='编辑成功';
            }else{
                $result['flag']=0;
                $result['msg']='编辑失败';
            }
        }else{
            $getdata =  input('get.');
            $id = $getdata['id'];
            $orderMsg = $orderMsgModel->field('msg')->where('id='.$id)->find();
            if(empty($orderMsg)){
                $result['flag']=0;
                $result['data']='操作失败！';
            }else{
                $result['flag']=1;
                $result['data']=$orderMsg;
            }
        }
        echo json_encode($result);
    }
    
    /**
     * 发送短信按钮
     * @author wd
     */
    public function sendmessage(){
        $postdata = input('post.');
        $id = $postdata['id'];
        $orderMsgModel = db('OrderGraveMsg');
        $orderMsg = $orderMsgModel->where('id='.$id)->find();
        if(empty($orderMsg)){
            $result['flag'] = 0;
            $result['msg'] = '操作失败';
        }else{
            $sendmsgobj = new SendMsg();
            $msgLogData['status'] = config('normal_status');
            $msgLogData['admin_id'] = session('admin_id');
            $msgLogData['send_time'] = time();
            $rst = $sendmsgobj->sendMsg($orderMsg['mobile'], $orderMsg['msg']);
            if($rst){
                $resultdata = $orderMsgModel->where('id='.$id)->update($msgLogData);
                if($resultdata){
                    $result['flag'] = 1;
                    $result['msg'] = '发送成功';
                }  else {
                    $result['flag'] = 0;
                    $result['msg'] = '发送失败';
                }
           
            }
            echo json_encode($result);
        }
    }


    /****************陵园列表页各按钮功能结束********************/

    /**
     * 添加回访跟踪信息
     */
    public function addrevisit(){
        $data = input('post.');

        $result = array('flg'=>0,'msg'=>'添加失败');
        if($data['tag']=='revisit'){   
            $info = $data['info'];
            $admin_id = Session::get('admin_id');
            $info['admin_id'] = $admin_id;
            $admin_name = Db::name('admin')->where('id',$admin_id)->field('name')->find();
            $info['admin_name'] = $admin_name['name'];
            $info['created_time'] = time();
            $res = Db::name('order_revisit')->insert($info);


            /**************发送邮件开始*******************************/
            //通过order_id查询  商家名称 联系人 用户名称 联系方式 
            $orderinfo = Db::name('order_grave')->where('id='.$info['order_id'])->field('reservation_person,reservation_phone,store_name,store_id')->find();
            if($orderinfo){
                 $name = Db::name('store_contact')->where('store_id='.$orderinfo['store_id'])->field('contact_name')->find();
                  $emailData = config('order_follow_email');
                  $emaildata['email_address'] =$emailData; 
                  $emaildata['title'] = '陵园跟踪信息'; 
                  $emaildata['content'] ='陵园名称:'.$orderinfo['store_name'].',联系人:'.$name['contact_name'].';<br>客户名称:'.$orderinfo['reservation_person'].',联系方式:'.$orderinfo['reservation_phone'].';<br>订单跟踪人:'.$admin_name['name'].',沟通内容:'.$info['content'].';';
                  $emailresult =  $this->sendMail($emaildata);//发送邮件
            }
            
            /**************发送邮件结束*******************************/
            if($res || $emailresult){
                $result = array('flg'=>1,'msg'=>$info);
            }
        }else{
 
            $info['demand'] = $data['content'];
            $res = Db::name('order_grave')->where('id',$data['order_id'])->update($info);
            if($res){
                $result = array('flg'=>2,'msg'=>'操作成功');
            }
            
        }
        echo json_encode($result);
    }


    /*
     * 发送邮件
     * return string(json);
     */
    public function sendMail($data){
        if(!empty($data)){
           $email  = config('order_follow_email');
           foreach ($email as $key => $value) {
                $addData[] = array('type'=>'3','email_address'=>''.$value.'','title'=>'跟踪信息邮件发送','content'=>''.$data['content'].'','status'=>config('normal_status'),'send_time'=>time(),'creat_time'=>date('Y-m-d H:i:s'));
            }
            $result = Db::name('EmailLog')->insertAll($addData);
            if($result){
                $istrue = $this->sendMailtocustomer($data['email_address'],$data['title'],$data['content']);
                if($istrue){
                    return true;
                }
            }
        }
    }



    /*
     * 邮件发送函数
     * @input $to|邮箱地址（群发为数组）;
     * @input $subject|邮件标题;
     * @input $content|邮件内容;
     * @return boolean;
     */

    public function sendMailtocustomer($to, $subject, $content) {
        $mail = new phpmailer();
        // 装配邮件服务器
        if (config('mail_smtp')) {
            $mail->IsSMTP();
        }
        $mail->Host = config('mail_host');
        $mail->SMTPAuth = config('mail_smtpauth');
        $mail->Username = config('mail_username');
        $mail->Password = config('mail_password');
        $mail->CharSet = config('mail_charset');
        // 装配邮件头信息
        $mail->From = config('mail_username');
            //判断是否为群以邮件
            if(is_array($to)){
                $to_num = count($to);
                for($i=0;$i<$to_num;$i++){
                    $mail->AddAddress($to[$i]);
                }
            }else{
                $mail->AddAddress($to);
            }
        $mail->FromName = config('mail_fromname');
        $mail->IsHTML(config('mail_ishtml'));
        // 装配邮件正文信息
        $mail->Subject = $subject;
        $mail->Body = $content;
        // 发送邮件
        if (!$mail->Send()) {
            return false;
        } else {
            return true;
        }
    }

    /********************陵园回收站按钮功能开始*****************/
    /**
     * @param  int   id 
     * @return json
     * @author wangdong 
     */
    public function backspace(){
        $orderId = input('id');
        $data['state'] = config('normal_status');
        $info = db('OrderGrave')->where('id='.$orderId)->update($data);
        if($info){
            $result['flag'] = 1;
            $result['msg'] = '回撤成功';
        }
        echo json_encode($result);
    }
    /********************陵园回收站按钮功能结束*****************/

    /*******************前台预约订单留言功能开始****************/
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
        $flow['order_type'] = '1';
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
        $order = db('appoint');
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

    /*******************前台预约订单留言功能结束****************/
    /*******************后台完成订单退单功能开始****************/
    /**
     * 查出并提交退单信息
     */
    public function getbackinfo(){
        if(Request::instance()->isPost()){
            $data = input('post.');
            $id = $data['id'];
            $info['bank_name'] = $data['bank'];
            $info['bank_member_name'] = $data['bank_member_name'];
            $info['bank_id'] = $data['bank_id'];
            $info['back_money'] = $data['back_money'];
            $info['back_reason'] = $data['back_reason'];
            $info['back_apply_time'] = time();
            $info['status'] = config('order_status.allow');
            $ending = Db::name('OrderGrave')->where('id='.$id)->update($info);
            if($ending){
                $result['flag'] = 1;
                $result['msg'] = '退单审核通过';
            }
        }else{
            $param = input('get.');
            $id = $param['id'];
            $order = Db::name('OrderGrave')->where('id',$id)->field('id,brokerage_percent,brokerage_money,paid_in_amount,return_fact_money,bank_member_name,bank_id,bank_name')->find();
            if($order){
                $result['order'] = $order;
                $result['flag'] = 1;
            }
        }
        echo json_encode($result);
    }


    //查看删除原因
    public function lookreason(){
        $id = input('id');
        $reason = Db::name('OrderGrave')->where('id='.$id)->field('reason')->find();
        if($reason){
            $result['flag'] = 1;
            $result['data'] = $reason['reason'];
        }
        echo json_encode($result);
    }

    //查看 退单原因
    public function backreason(){
        $id = input('id');
        $reason = Db::name('OrderGrave')->where('id='.$id)->field('back_reason')->find();
        if($reason){
            $result['flag'] = 1;
            $result['data'] = $reason['back_reason'];
        }
        echo json_encode($result);
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
        $res = Db::name('appoint_follow')->insert($info);
        if($res){
            $result = array('flag'=>1,'msg'=>'添加成功');
        }else{
            $result = array('flag'=>0,'msg'=>'添加失败');
        }

        echo json_encode($result);
    }

     /**
     * 查看跟踪信息
     */
    public function checkmessage(){
        $id= input('id');
        $arr = array('flag'=>1,'data'=>'');
        $revisit = Db::name('appoint_follow')->where('appoint_id',$id)->order('created_time desc')->select();

        foreach ($revisit as $key => $value) {
            $revisit[$key]['created_time'] = date('Y-m-d H:i:s',$value['created_time']);
        }
        if(!empty($revisit)){
            $arr = array('flag'=>1,'data'=>$revisit );
        }else{
            $arr = array('flag'=>2 );
        }
        echo json_encode($arr);
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
     * 添加非会员列表跟踪信息
     */
    public function addnotvipmsg(){
        $data = input('post.');
        $info = $data['info'];
        $admin_id = Session::get('admin_id');
        $admin_name = Db::name('admin')->where('id',$admin_id)->field('name')->find();
        $info['admin_name'] = $admin_name['name'];
        $info['admin_id'] = $admin_id;
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
     * 查看跟踪信息
     */
    public function checknotvipmsg(){
        $id= input('id');
        $arr = array('flag'=>1,'data'=>'');
        $revisit = Db::name('order_revisit')->where('order_id',$id)->order('created_time desc')->select();
        foreach ($revisit as $key => $value) {
            $revisit[$key]['created_time'] = date('Y-m-d H:i:s',$value['created_time']);
        }
        if($revisit){
            $arr = array('flag'=>1,'data'=>$revisit );
        }else{
            $arr = array('flag'=>2 );
        }
        echo json_encode($arr);
    }


    /**其他咨询列表**/
    public function otherorder(){
        $where['status'] = config('order_status.other');
        $where['store_status'] = array('gt',0);//商家状态大于0
        $where['state'] = array('gt',-1);//未删除
        $time = 'created_time';//按创建时间排序
        $bool = true;//是否开启商务跟踪条件   
        $this->_comlist($where,$time,$bool);
        $jinji = config('degree');
        $call = config('call_type');
        $this->assign('jinji',$jinji);
        $this->assign('call',$call);
        return $this->fetch();

    }
}