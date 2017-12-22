<?php 
namespace web\app\controller;
use think\Controller;
use think\Db;
use think\Request;
use web\extra\model\MsgCode;
use web\app\model\OrderGrave;
use web\extra\controller\PushMesage;
/**
 * 91客户端app
 */
class ClientApp extends Controller
{
	/**
     * 获取验证码
     * @return json
     */
    public function verifyCode(){
        $input = input('post.');
        $type = $input['type'];
        $classify = $input['classify'];
        $result = ['code' => 0,'msg' => ''];
        $mobile = $input['mobile'];
        $msgCode = new MsgCode;
        $maxNum = $msgCode->isMaxSendNum($mobile);
        if($maxNum){
            $result = ['code' => 0,'msg' => '请不要恶意点击'];
        }else{
            $randCode = rand(100000,999999);
            $message = "动态验证码 " . $randCode . " 工作人员不会向你索要，请勿向任何人泄露。" . date("Y-m-d H:i:s", time()) . "【91搜墓网】";
            $msgObj = new \common\sendmsg\SendMsg;
            // $send = true;
            $send = $msgObj->sendMsg($mobile,$message);
            $result = ['code' => 0,'msg' => '验证码发送失败'];
            if($send){
                $codeData = [
                    'mobile'       => $mobile,
                    'code'         => $randCode,
                    'type'         => $type,
                    'created_time' => time()
                ];
                $addCodeMsg = Db::name('MsgCode')->data($codeData)->insert();
                if($addCodeMsg){
                    $msgLogData = [
                        'classify'     => $classify,
                        'mobile'       => $mobile,
                        'msg'          => $message,
                        'status'       => config('msg_send_status'),
                        'created_time' => time(),
                        'send_time'    => time()
                    ];
                    $msgLog = Db::name('MsgLog')->data($msgLogData)->insert();
                    if($msgLog){
                        $result = ['code' => 1,'msg' => $randCode];
                    }
                }
            }
        }
        echo json_encode($result);
    }

    //检测验证码
    public function checkCode(){
    	$input = input('post.');
    	$type = config('msgcode_type.register');
    	if($input){
    		$msgCodeObj = new MsgCode;
    		$res = $msgCodeObj->checkMessageCode($type,$input['mobile'],$input['code']);
    		echo json_encode($res);
    	}
    }

    /*
     * 用户名唯一验证
     * @param   void;
     * @return  string(json);
     */
    public function checkUser(){
        $result = array('flag'=>0,'msg'=>'该账户已存在');
        $mobile = input('mobile');
        if(!empty($mobile)){
            $finddata = Db::name('employee')->where('mobile',$mobile)->find();
            if(empty($finddata)){
                $result= array('flag'=>1,'msg'=>'ok');;
            }
        }
        echo json_encode($result);
    }

    /**
     * 省市联动数据
     * @return [json] [string]
     */
    public function getProvince(){        
        $region = Db::name('region')->where('pid != 0')->field('id,pid,name')->select();
        $result = [];
        foreach ($region as $key => $val) {
            if($val['pid'] == 2){
                $result[$val['id']] = [
                    'value' => $val['id'],
                    'text' => $val['name']
                ];
            }else{
                $result[$val['pid']]['children'][] = [
                    'value' => $val['id'],
                    'text' => $val['name']
                ];
            }
        } 
        $res = [];
        foreach ($result as $key => $value) {
            $res[] = $value;
        }
        // var_dump($res[0]);die;       
        echo json_encode($res);
    }

    /**
     * 获取站点的方法
     */
    public function serviceSite(){
        $res = Db::name('order_service_site')->where('status',config('normal_status'))->field('id,site_name')->select();
        $result = array();
        if($res){
            foreach ($res as $key => $value) {
                $result[$key] = ['value'=>$value['id'],'text'=>$value['site_name']];
            }
        }
        echo json_encode($result);
    }

    /**
     * 注册方法
     * @return [type] json string
     */
    public function register(){
        $result = array('flag'=>0,'msg'=>'添加失败!');
        if($_POST['car_data'] && $_POST['user_data']){
            // 启动事务
            Db::startTrans();    
            $carObj = json_decode($_POST['car_data']);
            $usrObj = json_decode($_POST['user_data']);           
          
            $usrData['id_card'] = $this->uploadFile('id_card_img');
            $usrData['driving_licence'] = $this->uploadFile('driving_img');
            $carData['vehicle_travel_image'] = $this->uploadFile('vtl_img');
            $carData['models_image'] = $this->uploadFile('car_img');
            $usrData['name'] = $usrObj->name;
            $usrData['sex'] = $usrObj->sex;
            $usrData['mobile'] = $usrObj->mobile;
            $usrData['password'] = encryptHome($usrObj->pass);
            $usrData['id_number'] = $usrObj->id_number;
            $usrData['address'] = $usrObj->address;
            $usrData['status'] = config('default_status');
            $usrData['site_name'] = $usrObj->site_name;
            $usrData['site_id'] = $usrObj->site_id;
            $usrData['created_time'] = time();
            $usrData['updated_time'] = time();
            $driver_id = Db::name('employee')->insertGetId($usrData);
            if($driver_id){
                
                $carData['driver_id'] = $driver_id;
                $carData['plate_number'] = $carObj->plate_number;
                $carData['vin'] = $carObj->vin;
                $carData['engine_number'] = $carObj->engine;
                $carData['status'] = config('default_status');
                $carData['created_time'] = time();
                $carData['updated_time'] = time();

                $carRes = Db::name('car_info')->insert($carData);

                if($carRes){
                    $result = array('flag'=>1,'msg'=>'添加成功!');
                    Db::commit();
                }else{
                    Db::rollback();
                }

            }
        }
        echo json_encode($result);
    }

    /**
     * 图片上传
     * @param  [type] $image 图片名称
     * @return [type]        string
     */
    public function uploadFile($image){
        $file = false;
        if($_FILES[$image]['error'] == 0 && !empty($_FILES[$image]['tmp_name'])){            
            $result = uploadOne($image,config('clientapp_image'),false,false);
            if($result['ok'] == 1){
                $file = $result['images'][0];               
            }
        }
        return $file;
    }

    /**
     * 登录
     */
    public function login(){
        $password = input('pass');
        $phone = input('account'); 
        $cid = input('cid');      
        $result = array('flag'=>0,'msg'=>'用户名或密码错误!');
        if($password && $phone){
            $where['mobile'] = $phone;
            $where['password'] = encryptHome($password);
            $info = Db::name('employee')->field('id,name,mobile,is_online,status')->where($where)->find();
            if($info){
                if($info['status'] != config('normal_status')){
                    $result = array('flag'=>0,'msg'=>'信息审核中,请稍后在登录!');
                }else{                   
                    if($cid){
                        Db::name('employee')->where('mobile',$phone)->update(['client_id'=>$cid]);
                    }
                    $result = array('flag'=>1,'msg'=>'','info'=>$info);
                }
            }
        }
        echo json_encode($result);
    }

    /**
     * 个人中心查找用户信息
     * @return [json] 
     */
    public function findUserMessage(){
        $id = input('id');
        $result = array('flag'=>0,'msg'=>'查询信息失败');
        if($id){
            $userData = Db::name('employee')->where('id',$id)->field('name,id_card,driving_licence,id_number,site_name,address,sex')->find();
            $carData = Db::name('car_info')->where('driver_id',$id)->field('vehicle_travel_image,models_image,plate_number,vin,engine_number')->find();
            $result = array('flag'=>1,'userData'=>$userData,'carData'=>$carData);
        }
        echo json_encode($result);
    }

    /**
     * 修改密码
     * @return [type] [description]
     */
    public function updateMessage(){
        $oldpass = input('oldpass');
        $id = input('id');
        $pass = input('pass');
        if($id){
            $final = array('flag'=>0,'msg'=>'修改失败');
            $passwhere['id'] = $id;
            $passwhere['password'] = encryptHome($oldpass);
            $res = Db::name('employee')->where($passwhere)->find();
            if($res){  
                $data['password'] = encryptHome($pass);          
                $result = Db::name('employee')->where('id',$id)->update($data);
                if($result){
                    $final = array('flag'=>1,'msg'=>'修改成功');
                }
            }else{
                $final = array('flag'=>2,'msg'=>'原密码错误');
            }

            echo json_encode($final);
        }
    }

    
    /**
     * 手机号修改
     */
    public function updatePhone(){
        $input = input('post.');
        $type = config('msgcode_type.register');
        $final = array('flag'=>0,'msg'=>'修改失败');
        if($input){
            $msgCodeObj = new MsgCode;
            $res = $msgCodeObj->checkMessageCode($type,$input['mobile'],$input['code']);
            if($res){
                $result = Db::name('employee')->where('id',$input['id'])->update(['mobile'=>$input['mobile']]);
                if($result){
                    $final = array('flag'=>1,'msg'=>'修改成功');
                }
            }else{
                $final = array('flag'=>2,'msg'=>'验证码错误');
            }
        }

        echo json_encode($result);
    }


    /**
     * 上下线改变
     * @return [type] [description]
     */
    public function changeOnline(){
        $id = input('id');
        $online = input('online');
        $arr = array('flag'=>0,'msg'=>'失败');
        if($id){
            $res = Db::name('employee')->where('id',$id)->update(['is_online'=>$online]);
            if($res){
                $arr = array('flag'=>1);
            }
        }
        echo json_encode($arr);
    }

    /**
     * 信息推送
     * @param  string $driverId [司机id]
     * @param  string $orderId  [订单id]
     * @return [type]           
     */
    public function pushMsg($driverId='3',$orderId='20'){
        $obj = new PushMesage;
        $driver = Db::name('employee')->where('id',$driverId)->field('id,client_id,is_online')->find();
        if($driver && $driver['is_online'] == 1){ 

            $orderData = Db::name('order_view_tomb')->where('order_id',$orderId)->field('order_id,appointer,rider_number,rider_phone,riding_address,arrive_time,store_name')->find();

            $content = "客户:".$orderData['appointer']." 预约陵园:".$orderData['store_name']." 预约时间:".date('Y-m-d H:i:s',$orderData['arrive_time']);

            $data = array('appointer'=>$orderData['appointer'],
                          'arrive_time' => date('m-d H:i',$orderData['arrive_time']),
                          'store_name' => $orderData['store_name'],
                          'rider_number' => $orderData['rider_number'],
                          'riding_address' => $orderData['riding_address'],
                          'rider_phone' => $orderData['rider_phone'],
                          'order_id'  => $orderData['order_id']
                );
            $msg = ['content'=>$content,'payload'=>$data];
            $to = ['cid'=>$driver['client_id'],'device_token'=>'','system'=>2];
            $obj->pushIGtMsg(json_encode($msg),$to);
        }
    }

    /**
     * 接单改变订单状态
     * @param int $orderId  订单ID
     * return string
     */
    public function upServerStatus(){
        $orderId = input('orderId');
        $status = input('status');
        $result = array('flag'=>0,'msg'=>'失败');
        if($orderId){  
            if($status){
                $data['service_status'] = $status;         
            }
            if($status == 2){              
                $data['km_start_image'] = $this->uploadFile('km_start_image');
            } 
            if($status == 1){
                $data['incar_time'] = date('Y-m-d H:i:s',time());               
            }
            if($status ==3){
                $data['arrive_start_time'] = date('Y-m-d H:i:s',time());
            }
            if($status ==10){
                $data['km_end_image'] = $this->uploadFile('km_start_image');
                $data['over_time'] = date('Y-m-d H:i:s',time());
            }
            $data['updated_time'] = time();
            $res = Db::name('order_view_tomb')->where('order_id',$orderId)->update($data);
           
            if($res){
                $orderData = '';
                if($status == '1'){
                    $orderData = Db::name('order_view_tomb')->where('order_id',$orderId)->find();
                    $orderData['arrive_time'] = date('m-d H:i',$orderData['arrive_time']);
                }
                $result = array('flag'=>1,'msg'=>'成功','data'=>$orderData);
            }
        }
        echo json_encode($result);

    }

    /**
     * 查询订单数据
     */
    public function getOrderMsg(){
        $order_id = input('orderId');
        $result = array('flag'=>0);
        if($order_id){    
            $where['order_id'] = $order_id;
            $where['service_status'] = array('in','0,1,2,3');       
            $res = Db::name('order_view_tomb')->where($where)->find();
            if($res){
                $res['arrive_time'] = date('m-d H:i',$res['arrive_time']);
                $result = array('flag'=>1,'data'=>$res);
            }
        }
        echo json_encode($result);
    }

    /**
     * 订单列表
     * @return json
     */
    public function orderlist(){
        $input = input('get.');
        $page = $input['page'];
        $uid = $input['uid'];
        $pageNum = config('page_size');
        $start = ($page - 1) * $pageNum;
        $result = ['code' => 0];
        
        if($uid){           
            $carOrder = Db::name('order_view_tomb')->where('driver_id',$uid)->field('order_id,store_name,over_time,riding_address,service_status,total_km,created_time')->limit($start,$pageNum)->select();

            foreach ($carOrder as $key => $value) {
                $carOrder[$key]['service_type'] = config('service_status')[$value['service_status']];
                $time = strtotime($value['over_time']);
                $carOrder[$key]['created_time'] = date('m-d',$value['created_time']);
                $carOrder[$key]['over_time'] = date('m-d',$time);
            }
            $pages = ceil(count($carOrder) / $pageNum);
            $result = ['code' => 1,'data' => $carOrder,'pages' => $pages];
        }
        echo json_encode($result);
    }

    /**
     * 订单详情
     */
    public function orderDetail(){
        $orderId = input('orderId');
        // $orderId = 20;
        $array = array('flag'=>0);
        if($orderId){
            $data = OrderGrave::with(['tomb'])->where('id',$orderId)->field('id,reservation_sex,budgeted_price,tomb_user_sex,store_fact_name,store_fact_id')->find()->toArray();

            $need = Db::name('order_grave_need')->where('order_id',$orderId)->field('wirter_type,order_id,content,created_time')->select();
            if($need){
                foreach ($need as $key => $value) {
                    $need[$key]['wirter_type'] = config('wirter_type')[$value['wirter_type']];
                    $time = explode(' ',$value['created_time']);
                    $need[$key]['created_time'] = $time[0];
                }
            }

            //支付凭证图片
            $photo = Db::name('order_grave_photo')->where('order_id',$orderId)->field('bill_image')->select();
            $data['photo'] = '';
            if($photo){
                $data['photo'] = $photo;
            }

            if($data){
                $data['reservation_sex'] = config('reservation_sex')[$data['reservation_sex']];
                $data['tomb_user_sex'] = config('tomb_user_sex')[$data['tomb_user_sex']];
                $data['tomb']['arrive_time'] = date('Y-m-d',$data['tomb']['arrive_time']);
                $data['tomb']['service_type'] = config('service_status')[$data['tomb']['service_status']];
                if($data['tomb']['buyer_intention'] !== NULL){
                    $data['tomb']['buyer_intention_name'] = config('buyer_intention')[$data['tomb']['buyer_intention']];
                }
                if($data['tomb']['result'] !== NULL){
                    $data['tomb']['result_name'] = config('result')[$data['tomb']['result']];
                }
                if($data['tomb']['pay_type'] !== NULL){
                    $data['tomb']['pay_type_name'] = config('success_type')[$data['tomb']['pay_type']];
                }
                if($data['tomb']['is_repeat'] !== NULL){
                    $data['tomb']['is_repeat_name'] = config('is_repeat')[$data['tomb']['is_repeat']];
                }
                $data['tomb']['discount_91'] = $data['tomb']['91_discount'];
                $array = array('flag'=>1,'data'=>$data,'need'=>$need);
            }
        }
        // var_dump($data);die;
        echo json_encode($array);
    }   

    /**
     * 返回/结束行程的公共方法
     * @return [string] [result]
     */
    public function finishRoute(){
        $result = array('flag'=>0);
        if($_POST['data']){
             // 启动事务
            Db::startTrans();
            $dataObj = json_decode($_POST['data']);              
            // $tomb['company_counselor_name'] = $dataObj->company_name; //陪同人员暂时去掉
            if($dataObj->buyer_intention != ''){
                $tomb['buyer_intention'] = $dataObj->buyer_intention;
            }
            if($dataObj->result != ''){
                $tomb['result'] = $dataObj->result;
            }
            if($dataObj->service_status == 10){
                $tomb['over_time'] = date('Y-m-d H:i:s',time());
            }
            $tomb['accompany_info'] = $dataObj->accompany_info;           
            $tomb['service_status'] = $dataObj->service_status;
            $tomb['visit_store'] = $dataObj->store_name;
            $tomb['updated_time'] = time();
            if($dataObj->result == 1){  //已成交               
                if($dataObj->success_status != ''){
                    $tomb['pay_type'] = $dataObj->success_status;
                    $grave['pay_type'] = $dataObj->success_status;
                }
                $tomb['total_price'] = $dataObj->total_price;
                $tomb['deposit'] = $dataObj->deposit;
                $tomb['store_discount'] = $dataObj->store_discount;
                $tomb['91_discount'] = $dataObj->discount_91;
                $tomb['tomb_location'] = $dataObj->tomb_location;
                $tomb['tomb_price'] = $dataObj->tomb_price;
                $tomb['remark'] = $dataObj->remark;
                //反写订单主表
                $grave['store_fact_name'] = $dataObj->store_fact_name;
                ;
                $grave['store_fact_id'] = $dataObj->store_fact_id;
                ;
                $grave['status'] = config('order_status.ok');
                $grave['contract_time'] = time();
                $grave['updated_time'] = time();
                $graveResult = Db::name('order_grave')->where('id',$dataObj->order_id)->update($grave);

                //支付凭证
                if($dataObj->photo_flag){                   
                    $photo['order_id'] = $photo1['order_id'] = $dataObj->order_id;
                    $photo['type'] = $photo1['type'] = 2;
                    $photo['bill_status'] = $photo1['bill_status'] = config('normal_status');
                    $photo['created_time'] = $photo1['created_time'] =time();
                    $photo['updated_time'] = $photo1['updated_time'] = time();
                    $photo['bill_image'] =  $this->uploadFile('bill_image');
                    $photo1['bill_image'] =  $this->uploadFile('bill_image2');
                    $photoData = [$photo,$photo1];
                    if($photo1 && $photo){
                        $res = Db::name('order_grave_photo')->insertAll($photoData);
                    }
                }

            }else{
                //是否再次预约
                $tomb['is_repeat'] = $dataObj->is_repeat;
                $tomb['repeat_apoint_remark'] = $dataObj->repeat_apoint_remark;
            }
            $tombResult = Db::name('order_view_tomb')->where('order_id',$dataObj->order_id)->update($tomb);

            if($tombResult){
                $result = array('flag'=>1);
                Db::commit();
            }else{
                Db::rollback();
            }

        }

        echo json_encode($result);
    } 

    //查找商家信息
    function findStores(){
        $result = array('flag'=>0);
        $post = input('post.');
        $requireData = $post['requireData'];
        $flag = $post['flag'];
        // $requireData = '福建莆田壶山陵园';
        // $flag = 'name';
        if($flag =='name'){
            $data = Db::name('store')->where('name','like','%'.$requireData.'%')->field('id,name')->select();
        }else if($flag == 'province'){
            $where['province_id'] = $requireData[0];
            $where['city_id'] = $requireData[1];
            $data = Db::name('store')->where($where)->field('id,name')->select();
        }
        if($data){
            $result = array('flag'=>1,'data'=>$data);
        }
        
        echo json_encode($result);
    }

    /**
     * 记录轨迹
     */
    public function insertPolyLine(){
        // $post = input('post.');
        $data = input('data');
        $orderId = input('orderId');
        $result = array('flag'=>0);
        $tomb_polyine = Db::name('order_view_tomb')->where('order_id',$orderId)->field('polyline')->find();
        $polylineData = $tomb_polyine['polyline'].';'.$data;
        if($data){          
            $res = Db::name('order_view_tomb')->where('order_id',$orderId)->update(['polyline'=>$polylineData]);
            if($res){
                $result = array('flag'=>1);
            }
        }  
        echo json_encode($result);
    }



    function test(){
        $obj = new PushMesage;
        $msg = ['title'=>'标准格式','content'=>'测试','payload'=>40];
        $to = ['cid'=>'bdea16ae1e8d4997ab6bc281d8e96ddc','device_token'=>'','system'=>2];
        $obj->pushIGtMsg(json_encode($msg),$to);

        // $c = $obj->getCidStatus('bdea16ae1e8d4997ab6bc281d8e96ddc');
        // var_dump($c);
    }

}