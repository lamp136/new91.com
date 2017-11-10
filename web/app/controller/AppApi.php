<?php 
namespace web\app\controller;
use think\Controller;
use think\Db;
use think\Request;
use web\extra\model\Store;
use web\extra\model\OrderGrave;

/**
 * 91移动端app
 */
class AppApi extends Controller
{
    /**
     * 商家列表
     * @return json[string]
     */
    public function storeList(){
        $this->_publicHeader();
        
        $city = input('city');
        $category = input('category');
        $type = input('type');
        $page = input('page');
        $storeName = input('storeName');
        $where['status'] = config('normal_status');
        $page = $page=='' ? 1 : $page;
        $pageSize = 20;

        $result = array('flag'=>0,'data'=>'','page'=>$page);
        if(!empty($city)){
            $cityArr = explode('-',$city);
            if($cityArr[0]==2){
                $where['province_id'] = $cityArr[1];
            }else{
                $where['city_id'] = $cityArr[1];
            }
        }
        
        if(!empty($storeName)){
            $where['name'] = array('like','%'.$storeName.'%');
        }

        if(!empty($category)){
            $where['category_id'] = $category;
        }
        if(!empty($type)){
            $where['member_status'] = 0;
            if($type==1){
                $where['member_status'] = array('neq',0);
            }
        }

        $region = Db::name('region')->column('id,name');
        $category = Db::name('category')->column('id,name');
        $res = Db::name('store')->where($where)->field('id,name,member_status,category_id,city_id,province_id')->limit(($page-1)*$pageSize,$pageSize)->order('member_status desc')->select();
        
        foreach ($res as $key => $value) {
            $res[$key]['category_name'] = $category[$value['category_id']];
            $res[$key]['province_name'] = $region[$value['province_id']];
            if($value['city_id']){
                $res[$key]['city_name'] = $region[$value['city_id']];
            }
        }
        
        if($res){
            $result = array('flag'=>1,'data'=>$res,'page'=>$page);
        }
        echo json_encode($result);
    }

    /**
     * 商家详情页
     * @return json[string] 
     */
    public function storedetail(){
        $this->_publicHeader();
        $storeId = input('storeId');
        // $storeId = 91;
        $result = array('flag'=>0,'data'=>'');
        $data = Store::with(['province','city','profiles','category'])->where('id',$storeId)->field('id,name,summary,profiles_id,category_id,content,province_id,city_id,address,member_status,min_price,max_price,pick_up_address')->find()->toArray();

        $where['store_id'] = $storeId;
        $where['status'] = config('normal_status');
        $contact = Db::name('store_contact')->where('store_id',$storeId)->field('contact_name,mobile,tel')->select();
        if($data){
            $result = array('flag'=>1,'data'=>$data,'contact'=>$contact);
        }
        echo json_encode($result);

    }

    /**
     * 订单列表信息
     * @return [type] [description]
     */
    public function orderList(){
        $this->_publicHeader();
        $city = input('city');
        $status = input('status');
        $time = input('time');
        // $time = '2016-08-01,2017-08-16';
        $page = input('page');  
        $page = $page=='' ? 1 : $page;
        $pageSize = 20;
        $where['state'] = array('neq',config('delete_status'));

        $result = array('flag'=>0,'data'=>'','page'=>$page);
        if(!empty($city)){
            $cityArr = explode('-',$city);
            if($cityArr[0]==2){
                $where['province_id'] = $cityArr[1];
            }else{
                $where['city_id'] = $cityArr[1];
            }
        }

        if(!empty($time)){
            $arrtime = explode(',',$time);
            $arrtime[0] = strtotime($arrtime[0]);
            $arrtime[1] = strtotime($arrtime[1]);
            $fileTime = 'created_time';
            
            //完成订单的时间过滤
            if($status == config('order_status.success')){
                $fileTime = 'payfor_us_time';
            }
            $where[$fileTime] = array('between',$arrtime);
        }

        if(!empty($status)){
            $where['status'] = $status;
        }

        $list = OrderGrave::with(['province','city'])->where($where)->field('id,province_id,city_id,store_name,store_status,created_time')->limit(($page-1)*$pageSize,$pageSize)->order('created_time desc')->select();
    
        foreach ($list as $key => $value) {
            $list[$key]['created_time'] = date('Y-m-d',$value['created_time']);
        }
        
        if($list){
            $result = array('flag'=>1,'data'=>$list,'page'=>$page);
        }

        echo json_encode($result);

    }

    /**
     * 订单详情
     * @return [type] [description]
     */
    public function orderdetail(){
        $this->_publicHeader();
        $orderId = input('orderId');
        // $orderId = 2138;
        $result = array('flag'=>0,'data'=>'');
        $orderData = OrderGrave::with(['province','city','revisit'])->where('id',$orderId)->field('id,province_id,city_id,reservation_person,reservation_phone,buyer,mobile,demand,store_name,store_id,store_status,tomb_price,status,deposit,deposit_give_time,appoint_time,brokerage_percent,brokerage_money,paid_in_amount,return_percent,return_fact_money,order_type,payfor_us_time')->find()->toArray();

        //商家联系人
        $storeContact = Db::name('store_contact')->where('store_id',$orderData['store_id'])->field('contact_name,mobile')->find();

        $orderData['store_contact'] = $storeContact;
        //格式化时间
        $orderData['appoint_time'] = date('Y-m-d',$orderData['appoint_time']);
        if($orderData['payfor_us_time']){
            $orderData['payfor_us_time'] = date('Y-m-d',$orderData['payfor_us_time']);
        }
        if($orderData['deposit_give_time']){
            $orderData['deposit_give_time'] = date('Y-m-d',$orderData['deposit_give_time']);
        }

        $orderData['status'] = config('order_status_change')[$orderData['status']];
        $orderData['order_type'] = config('order_type')[$orderData['order_type']];
        
        if($orderData){
            $result = array('flag'=>1,'data'=>$orderData);
        }

        // var_dump($orderData);die;
        echo json_encode($result);
    }

    /**
     * 获取地区
     */
    public function getRegion(){
        $list = Db::name('region')->where('status',config('normal_status'))->select();
        $result = array('flag'=>0,'msg'=>'');
        if($list){
            $result = array('flag'=>1,'msg'=>$list);
        }
        return json_encode($result);
    }

    /**
     * 封装订单分析时间
     * @param  input|field;//字段名
     * @param  input|$monthlength;//开始时间为空时当前几个月[默认(0)当月]
     * @return  output|array;
     */
    private function ordertime($field,$monthlength = 0){
        $where = array();
        $start_time = input('start_time');
        $end_time = input('end_time');
        // $start_time = '2017-08-01';
        // $end_time = '2017-08-22';
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
        return $where;
    }

    /**
     * 订单统计列表
     * @return [type] [description]
     */
    public function orderStatistics(){
        $result = array('flag'=>0,'data'=>'');
        
        $where = $this->ordertime('created_time');
        $where['state'] = array('neq',config('delete_status'));
        $where['status'] = array('neq',config('order_status.success'));
        $creatDate = Db::name('order_grave')->where($where)->field('id,province_id')->select();

        //完成的订单
        $where = $this->ordertime('payfor_us_time');
        $where['state'] = array('neq',config('delete_status'));
        $where['status'] = config('order_status.success');
        $successDate = Db::name('order_grave')->where($where)->field('id,province_id')->select();
        $list = array_merge($creatDate,$successDate);

        $countNum = count($list);
        $region = Db::name('region')->where('pid',2)->column('id,name');
        
        $lastData = array();
        foreach ($list as $key => $value) {
            if(array_key_exists($value['province_id'], $lastData)){
                $lastData[$value['province_id']]['all_total'] +=1;
            }else{
                $lastData[$value['province_id']]['province_name'] = $region[$value['province_id']];
                $lastData[$value['province_id']]['all_total'] =1;
            }
        }
        
        if($lastData){
            $result = array('flag'=>1,'data'=>$lastData,'countNum'=>$countNum);
        }

        echo json_encode($result);      
    }

    /**
     * 订单统计详情
     * @return [type] [description]
     */
    public function ordersticdetail(){
        $proId = input('pro');
        // $proId = 301;
        $result = array('flag'=>0,'data'=>'');
     
        $where = $this->ordertime('created_time');
        if(!empty($proId)){
            $where['province_id'] = $proId;
        }

        $where['state'] =  array('neq',config('delete_status'));
        $where['status'] = array('neq',config('order_status.success'));
        $createDate = Db::name('order_grave')->where($where)->field('id,store_name,reservation_person,reservation_phone,buyer,mobile,paid_in_amount,status,appoint_time,payfor_us_time')->select();

        //完成订单统计
        $suWhere = $this->ordertime('payfor_us_time');
        if(!empty($proId)){
            $suWhere['province_id'] = $proId;
        }
        $suWhere['state'] = array('neq',config('delete_status'));
        $suWhere['status'] = config('order_status.success');
        $successDate = Db::name('order_grave')->where($suWhere)->field('store_name,reservation_person,reservation_phone,buyer,mobile,paid_in_amount,status,appoint_time,payfor_us_time')->select();

        $list = array_merge($createDate,$successDate);

        $lastData = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0,'6'=>0,'7'=>0);
        foreach ($list as $key => $value) {
            $list[$key]['status_name'] = config('order_status_change')[$value['status']];
            $list[$key]['appoint_time'] = date('Y-m-d',$value['appoint_time']);
            if($list[$key]['payfor_us_time']){
                $list[$key]['payfor_us_time'] = date('Y-m-d',$value['payfor_us_time']);
            }

            //预约订单
            if($list[$key]['status']==config('order_status.default')){
                $lastData['1'] +=1;
            }
            //核实订单
            if($list[$key]['status']==config('order_status.ok')){
                $lastData['2'] +=1;
            }
            //完成订单
            if($list[$key]['status']==config('order_status.success')){
                $lastData['3'] +=1;
            }
            //待收佣金
            if($list[$key]['status']==config('order_status.check_success')){
                $lastData['4'] +=1;
            }
            //已交定金
            if($list[$key]['status']==config('order_status.deposit')){
                $lastData['5'] +=1;
            }
             //待返现
            if($list[$key]['status']==config('order_status.get_money')){
                $lastData['6'] +=1;
            }
            //其他订单
            if($list[$key]['status']==config('order_status.other')){
                $lastData['7'] +=1;
            }

        }

        //饼状图/柱状图数据
        $jsonData =array(array('name'=>'预约订单','value'=>$lastData['1']),
                    array('name'=>'核实订单','value'=>$lastData['2']),
                    array('name'=>'完成订单','value'=>$lastData['3']),
                    array('name'=>'待收佣金','value'=>$lastData['4']),
                    array('name'=>'已交定金','value'=>$lastData['5']),
                    array('name'=>'待返现','value'=>$lastData['6']),
                    array('name'=>'其他订单','value'=>$lastData['7']));
        if($list){
            $result = array('flag'=>1,'data'=>$list,'imgData'=>$jsonData);
        }
        
        echo json_encode($result);
    }

    /**
     * 平台收支统计列表
     * @return [type] [description]
     */
    public function inpayList(){
        $result = array('flag'=>0,'data'=>'');
        $where = $this->ordertime('payfor_us_time');
    
        $list = OrderGrave::with(['province'])->where($where)->field('province_id,paid_in_amount,province_id')->select();

        
        $provinceIncome = array();
        $countNum = 0;
        foreach ($list as $key => $v) {
            $countNum += $v['paid_in_amount'];
            if(array_key_exists($v['province_id'],$provinceIncome)){
                $provinceIncome[$v['province_id']] = array(
                    'province' => $v['province']['name'],
                    'total'    => $provinceIncome[$v['province_id']]['total'] + $v['paid_in_amount'],
                );
            }else{
                $provinceIncome[$v['province_id']] = array(
                    'province' => $v['province']['name'],
                    'total'    => $v['paid_in_amount']
                );
            }
        }
        if($provinceIncome){
            $result = array('flag'=>1,'data'=>$provinceIncome,'countNum'=>$countNum);
        }

        echo json_encode($result);       
    }

    /**
     * 商务人订单统计
     * @return [type] [description]
     */
    public function businessList(){
        $result = array('flag'=>0,'data'=>'');

        $where = $this->ordertime('created_time');
        $where['state'] = array('neq',config('delete_status'));
        $where['status'] = array('neq',config('order_status.success'));
        $list = Db::name('order_grave')->where($where)->field('id,order_flow_id')->select();
        //完成订单统计
        $suWhere = $this->ordertime('payfor_us_time');
        $suWhere['state'] = array('neq',config('delete_status'));
        $suWhere['status'] = config('order_status.success');
        $suList = Db::name('order_grave')->where($suWhere)->field('id,order_flow_id')->select();

        $array = array_merge($list,$suList);
        $lastData = array();
        $admin = Db::name('admin')->column('id,name');
        foreach ($array as $key => $value) {
            if(array_key_exists($value['order_flow_id'], $lastData)){
                $lastData[$value['order_flow_id']]['all_total'] +=1;               
            }else{
                if($value['order_flow_id'] != 0){
                    $lastData[$value['order_flow_id']]['name'] = $admin[$value['order_flow_id']];
                }
                $lastData[$value['order_flow_id']]['all_total'] =1;                
            }
        }

        if($array){
            $result = array('flag'=>1,'data'=>$lastData);
        }

        echo json_encode($result);
    }

    /**
     * 商务人订单统计详情
     * @return [type] [description]
     */
    public function businessDetail(){

        $result = array('flag'=>0,'data'=>'');
        $flowId = input('flowId');

        // $flowId = 24;        
        $where = $this->ordertime('created_time');
        $where['order_flow_id'] = $flowId;
        $where['state'] =  array('neq',config('delete_status'));
        $where['status'] = array('neq',config('order_status.success'));
        
        $createdData = Db::name('order_grave')->where($where)->field('id,store_name,reservation_person,reservation_phone,buyer,mobile,paid_in_amount,status,appoint_time,payfor_us_time')->select();
        //完成的订单
        $where = $this->ordertime('payfor_us_time');
        $where['order_flow_id'] = $flowId;
        $where['status'] = config('order_status.success');
        $where['state'] =  array('neq',config('delete_status'));
        $successData = Db::name('order_grave')->where($where)->field('id,store_name,reservation_person,reservation_phone,buyer,mobile,paid_in_amount,status,appoint_time,payfor_us_time')->select();

        $list = array_merge($createdData,$successData);

        $lastData = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0,'6'=>0,'7'=>0);
        foreach ($list as $key => $value) {
            $list[$key]['status_name'] = config('order_status_change')[$value['status']];
            $list[$key]['appoint_time'] = date('Y-m-d',$value['appoint_time']);
            if($list[$key]['payfor_us_time']){
                $list[$key]['payfor_us_time'] = date('Y-m-d',$value['payfor_us_time']);
            }

            //预约订单
            if($list[$key]['status']==config('order_status.default')){
                $lastData['1'] +=1;
            }
            //核实订单
            if($list[$key]['status']==config('order_status.ok')){
                $lastData['2'] +=1;
            }
            //完成订单
            if($list[$key]['status']==config('order_status.success')){
                $lastData['3'] +=1;
            }
            //待收佣金
            if($list[$key]['status']==config('order_status.check_success')){
                $lastData['4'] +=1;
            }
            //已交定金
            if($list[$key]['status']==config('order_status.deposit')){
                $lastData['5'] +=1;
            }
             //待返现
            if($list[$key]['status']==config('order_status.get_money')){
                $lastData['6'] +=1;
            }
            //其他订单
            if($list[$key]['status']==config('order_status.other')){
                $lastData['7'] +=1;
            }

        }
        //饼状图/柱状图数据
        $jsonData =array(array('name'=>'预约订单','value'=>$lastData['1']),
                    array('name'=>'核实订单','value'=>$lastData['2']),
                    array('name'=>'完成订单','value'=>$lastData['3']),
                    array('name'=>'待收佣金','value'=>$lastData['4']),
                    array('name'=>'已交定金','value'=>$lastData['5']),
                    array('name'=>'待返现','value'=>$lastData['6']),
                    array('name'=>'其他订单','value'=>$lastData['7']));
    
        if($list){
            $result = array('flag'=>1,'data'=>$list,'imgData'=>$jsonData);
        }

        echo json_encode($result);
    }


    /**
     * 同比环比
     * @return [type] [description]
     */
    public function ordersamecompare(){

        $result = array('flag'=>0);
        $nowYear = input('year');
        //获取年份
        $allYear = array();
        $year = date("Y");
        for($i = 2016;$i<= $year;$i++){
            $allYear[] = $i;
        }
        //默认当年
        if(empty($nowYear)){
            $nowYear = date("Y");
        }
        $where['state'] = config('normal_status');
        $where['created_time'] = array('BETWEEN',array(strtotime(($nowYear-1).'-1-1'),strtotime($nowYear.'-12-31 23:59:59')));
        $OrderData = Db::name('OrderGrave')->field('status,created_time')->where($where)->select();
        $subTime = strtotime($nowYear.'-1-1');
        $benYear = $this->month($nowYear);
        $nextYear = $this->month($nowYear - 1);
        $next_total = array();
        $now_total = array();

        $lastData = array();
        foreach($benYear as $key => $timeVal){
            $now_total[$key] = 0;
        }
        foreach($nextYear as $key => $timeVal){
            $next_total[$key] =0;
        }
        foreach($OrderData as $dataVal){
            if($dataVal['created_time'] > $subTime){
                foreach($benYear as $key => $timeVal){
                    if(($timeVal['start']<$dataVal['created_time'])&&($dataVal['created_time']<$timeVal['end'])){
                        $now_total[$key] +=1;
                    }
                }
            }else{
                foreach($nextYear as $key => $timeVal){
                    if(($timeVal['start']<$dataVal['created_time'])&&($dataVal['created_time']<$timeVal['end'])){
                        $next_total[$key] +=1;
                    }
                }
            }
        }

       
        unset($where['created_time']);
        $where['status'] =  config('order_status.success');
        $where['payfor_us_time'] = array('BETWEEN',array(strtotime(($nowYear-1).'-1-1'),strtotime($nowYear.'-12-31 23:59:59')));
        $successOrderData = Db::name('OrderGrave')->field('success_time')->where($where)->select();
        $now_success_total = array();
        $next_success_total = array();
        foreach($benYear as $key => $timeVal){
            $now_success_total[$key] = 0;
        }
        foreach($nextYear as $key => $timeVal){
            $next_success_total[$key] = 0;
        }
        foreach($successOrderData as $dataVal){
            if($dataVal['success_time'] > $subTime){
                foreach($benYear as $key => $timeVal){
                    if(($timeVal['start']<$dataVal['success_time'])&&($dataVal['success_time']<$timeVal['end'])){
                        $now_success_total[$key] +=1;
                    }
                }
            }else{
                foreach($nextYear as $key => $timeVal){
                    if(($timeVal['start']<$dataVal['success_time'])&&($dataVal['success_time']<$timeVal['end'])){
                        $next_success_total[$key] +=1;
                    }
                }
            }
        }
        if($now_total && $next_total){
            $result = array('flag'=>1,'now_total'=>$now_total,'next_total'=>$next_total,'now_success_total'=>$now_success_total,'next_success_total'=>$next_success_total,'allYear'=>$allYear,'nowYear'=>$nowYear);
        }
        echo json_encode($result);
        // $this->assign('now_success_total',$now_success_total);//今年完成总订单量
        // $this->assign('next_success_total',$next_success_total);//今年完成总订单量
        // $this->assign('now_total',$now_total);//今年总订单量
        // $this->assign('next_total',$next_total);
        // $this->assign('allYear',$allYear);
        // $this->assign('nowYear',$nowYear);
    }

    /**
     * @param  $year|eg;2017; 年份
     * @return  $result|array;
     */
    private function month($year){
        $result = array();
        if(!empty($year)){
            $result = array(
               '1'=> array('start'=>strtotime($year.'-1-1'),'end'=>strtotime($year.'-1-31 23:59:59')),
               '2'=> array('start'=>strtotime($year.'-2-1'),'end'=>strtotime($year.'-2-28 23:59:59')),
               '3'=> array('start'=>strtotime($year.'-3-1'),'end'=>strtotime($year.'-3-31 23:59:59')),
               '4'=> array('start'=>strtotime($year.'-4-1'),'end'=>strtotime($year.'-4-30 23:59:59')),
               '5'=> array('start'=>strtotime($year.'-5-1'),'end'=>strtotime($year.'-5-31 23:59:59')),
               '6'=> array('start'=>strtotime($year.'-6-1'),'end'=>strtotime($year.'-6-30 23:59:59')),
               '7'=> array('start'=>strtotime($year.'-7-1'),'end'=>strtotime($year.'-7-31 23:59:59')),
               '8'=> array('start'=>strtotime($year.'-8-1'),'end'=>strtotime($year.'-8-31 23:59:59')),
               '9'=> array('start'=>strtotime($year.'-9-1'),'end'=>strtotime($year.'-9-30 23:59:59')),
               '10'=> array('start'=>strtotime($year.'-10-1'),'end'=>strtotime($year.'-10-31 23:59:59')),
               '11'=> array('start'=>strtotime($year.'-11-1'),'end'=>strtotime($year.'-11-30 23:59:59')),
               '12'=> array('start'=>strtotime($year.'-12-1'),'end'=>strtotime($year.'-12-31 23:59:59'))
            );
        }
        return $result;
    }

     /**
     * 跨域获取数据需要用到的http报头
     */
    private function _publicHeader(){
        header("Access-Control-Allow-Origin: http://91app.com");// 返回数据的域名
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST, PUT');
    }
}