<?php
namespace web\rite\controller;
use think\Controller;
use think\Db;
use think\Request;
use web\extra\controller\Base;
use web\extra\model\Store;
use think\Cookie;//cookie类
use web\extra\model\MsgCode; //发送短信
use web\search\controller\Search;

/**
 * 礼仪服务类
 * author
 */
class Rite extends Base{  
	
    /*
     * 礼仪服务列表
     *
     */
    public function lists(){
        $input = input();
        $selectCity = 0;
        if(!empty($input['province']) && $input['province'] != 0){
            $selectCity = $input['province'];
            $where['province_id'] = $selectCity;
        }
        $selectDist = 0;
        if(!empty($input['dist']) && in_array($input['dist'],config('store_length'))){
            $selectDist = $input['dist'];
            $storeLength = config('store_length');
            $where['distance'] = $this->betweenWhere($selectDist,$storeLength);
        }
        
        if(!empty($input['name'])){
            $search = new Search();
            $resultdata = $search->searchName($input['name'],config('rite_category'));
            $where['id'] = ['in',$resultdata];
        }
        $where['category_id'] = config('rite_category');
        $where['status'] = config('normal_status');

        $data = Store::withCount(array('OrderService'),false)->where($where)->field('thumb_image,image,name,address,phone,hits,actual_hits,id,category_id')->order('created_time desc')->paginate(config('page_size'),false,['query' => $input]);
        $page = $data->render();

        $countData = Db::name('store')->where($where)->count();
        //获取白事常识
        $bscs = $this->article(config('article_sense'),6);
        

        //获取省份
         
        $allprovince = $this->getRegionData(array('status'=>config('normal_status'),'pid'=>config('china_num')),array('id','name'),'',true);
        //dump($allprovince);die;
        
        
        $findProWhere['category_id'] = config('rite_category');
        $findProWhere['status'] = config('normal_status');
        $prodata = Db::name('Store')->where($findProWhere)->field('province_id')->group('province_id')->select();
        $seachdata = array();
        foreach($prodata as $val){
            $seachdata[$val['province_id']] = $allprovince[$val['province_id']];
        }
        //dump($seachdata);
        //dump($prodata);die;
        
        //排行榜商家
        if(!empty($where['distance'])){
            unset($where['distance']);
        }
        $store =Store::withCount(array('OrderGrave'),false)->where($where)->field('id,name')->select();

        $countNum = count($store);
        for ($i=1; $i <$countNum ; $i++) { 
            for ($j=0; $j <$countNum-$i ; $j++) { 
                if($store[$j]['order_grave_count'] < $store[$j+1]['order_grave_count']){
                    $tmp = $store[$j];
                    $store[$j] = $store[$j+1];
                    $store[$j+1] = $tmp;
                }
            }
        }

        //seo
        $seo = $this->getseo(config('seo_type.funeral_liyi'));

        $this->assign('selectCity',$selectCity);
        $this->assign('selectDist',$selectDist);
        $this->assign('input',$input);
        $this->assign('countDist',count(config('store_length')));
        $this->assign('seo',$seo);
        $this->assign('store',$store);
        $this->assign('bscs',$bscs);
        $this->assign('seachprovince',$seachdata);
        $this->assign('page',$page);
        $this->assign('data',$data);
        $this->assign('countData',$countData);
        return $this->fetch();
    }

      /**
     * 获取where区间条件
     * @param  array $selectData 筛选条件
     * @param  array $conf 配置常量
     * @return array
     */
    private function betweenWhere($selectData,$conf){
        $cond = [];
        $min = current($conf);
        $max = end($conf);
        if($selectData == $min){
            $cond = ['elt',$min];
        }else if($selectData == $max){
            $cond = ['egt',$max];
        }else{
            $data = explode('-',$selectData);
            if(is_array($data)){
                $count = count($data);
                if($count > 1){
                    $cond = ['between',[(int)$data[0],(int)$data[1]]];
                }
            }
        }

        return $cond;
    }

    /**
     * 礼仪服务详情页
     */
    public function detail(){
        $id = input('id');
        $get = input('get.');
        $condition = [
            'id' => $id,
            'status' => config('normal_status')
        ];
        Db::name('store')->where($condition)->setInc('actual_hits');
        $data =Store::withCount(array('OrderService'),false)->where($condition)->field('id,content,name,phone,hits,actual_hits,address,thumb_image,image,province_id,city_id,service_city,seo_title,seo_keywords,seo_description')->find();
         //字符串替换
        $data['content'] = str_replace('src="/public/','src="/',$data['content']);
        
        $regions = $this->getRegionData(array('status'=>config('normal_status')),array('id','name'),'',true);
        $cityarray = array_filter(explode(',',$data['service_city']));
        $Tmp = array();
        foreach($cityarray as $val){
            $Tmp[$val] = $regions[$val];
        }
        $data['service_city'] = $Tmp;
        
        
        //浏览量
        if($data['hits'] == 0){
            $hits = config('page_view');
            $data['hits'] = $hits;
            Db::name('store')->where('id',$id)->update(['hits' => $hits]);
        }
        
        //获取评论
        $comtwhere['store_id'] = $id;
        $comtwhere['comment_status'] = config('normal_status');

        $comment = Db::name('comment')->where($comtwhere)->order('created_time desc')->limit(0,config('page_size'))->select();
       
        //总数据 和 总页数
        $commentNum = Db::name('comment')->where($comtwhere)->count();
        $comtCountPage = ceil($commentNum/config('page_size'));

        //计算分数平均值
        $serviceScore = 0;
        $environmentScore =0;
        $priceScore =0;
        $trafficScore =0;
        foreach($comment as $key=>$value){
            $serviceScore += $value['service'];
            $environmentScore += $value['environment'];
            $priceScore += $value['price'];
            $trafficScore += $value['traffic'];
            $comment[$key]['ave'] = ceil(($value['service']+$value['environment']+$value['price']+$value['traffic'])/3);
            $comment[$key]['mobile'] = substr_replace($comment[$key]['mobile'],'****',3,4);
        }
        $aveScore['service'] = 0;
        $aveScore['environment'] = 0;
        $aveScore['price'] =0;
        $aveScore['traffic'] = 0;
        $aveScore['total'] = 0;
        $aveScore['commentNum'] = 0;
        if($commentNum != 0){
            $aveScore['service'] = round($serviceScore/$commentNum);
            $aveScore['environment'] = round($environmentScore/$commentNum);
            $aveScore['price'] = round($priceScore/$commentNum);
            $aveScore['traffic'] = round($trafficScore/$commentNum);
            $total = $aveScore['service']+$aveScore['environment']+$aveScore['price']+$aveScore['traffic'];
            $aveScore['total'] = round($total/3);
            $aveScore['commentNum'] = $commentNum;
        }

        //获取套餐
        // $combo = Db::name('etiquette_combo')->where('store_id',$id)->field('id,store_id,combo_sn,combo_name,combo_price,platform_price,thumb_image')->paginate(config('page_size'),false,['query' => $get]);
        // $page = $combo->render();
        $comboWhere['store_id'] = $id;
        $comboWhere['status'] = config('normal_status');

        $combo = Db::name('etiquette_combo')->where($comboWhere)->field('id,store_id,combo_sn,combo_name,combo_price,platform_price,thumb_image')->limit(0,config('page_size'))->order('created_time desc')->select();
       
        //总数据 和 总页数
        $comboNum = Db::name('etiquette_combo')->where($comboWhere)->count();
        $comboCountPage = ceil($comboNum/config('page_size'));

        //附近陵园
        $where['province_id'] = $data['province_id'];
        // $where['city_id'] = $data['city_id']; ??是否带市
        $where['category_id'] = config('category_cemetery_id');
        $where['member_status'] = array('gt',0);
        $where['status'] = config('normal_status');
        $cemetery = Db::name('store')->where($where)->field('id,name,thumb_image,image')->select();
        //随机取6个
        if(count($cemetery) > 3){ 
            $rand = array_rand($cemetery,3);
            foreach ($rand as $key => $value) {
                $randcemetery[] = $cemetery[$value];
            }
        }else{
            $randcemetery = $cemetery;
        }

        //获取白事常识
        $bscs = $this->article(config('article_sense'),6);
        
        $seo = array(
            'seo_title'       =>$data['seo_title'],
            'seo_keywords'    =>$data['seo_keywords'],
            'seo_description' =>$data['seo_description']
        );
       
        $this->assign('seo',$seo);
        $this->assign('data',$data);
        $this->assign('bscs',$bscs);
        $this->assign('randcemetery',$randcemetery);
        // $this->assign('page',$page);
        $this->assign('comboNum',$comboNum);             //套餐总条数
        $this->assign('comboCountPage',$comboCountPage); //套餐总页数
        $this->assign('combo',$combo);
        $this->assign('comtCountPage',$comtCountPage);  //评论总页数
        $this->assign('comment',$comment);
        $this->assign('aveScore',$aveScore);
        return $this->fetch();
    }

    /**
     * 服务套餐详情
     */
    public function comboDetail(){
        $id = input('id');
        $combo = Db::name('etiquette_combo')->where('id',$id)->find();
         //字符串替换
        $combo['content'] = str_replace('src="/public/','src="/',$combo['content']);
        $combo['service_course'] = str_replace('src="/public/','src="/',$combo['service_course']);
        //$combo['content'] = explode('#', $combo['content']);
        
        $store = Db::name('store')->where('id',$combo['store_id'])->field('id,address,phone')->find();
        //服务订单数量
        $combo['ordercount'] = 0;
        $orderwhere['status'] = array('neq','-1');
        $orderwhere['state'] = array('eq','1');
        $orderwhere['store_id'] = $store['id'];
        $combo['ordercount'] = Db::name('OrderService')->where($orderwhere)->count();
        
        //浏览量
        if($combo['page_view'] == 0){
            $hits = config('page_view');
            Db::name('etiquette_combo')->where('id',$id)->update(['page_view' => $hits]);
        }
        Db::name('etiquette_combo')->where('id', $id)->setInc('actual_page_view');
        //seo
        $seo = array(
            'seo_title'       =>$combo['seo_title'],
            'seo_keywords'    =>$combo['seo_keywords'],
            'seo_description' =>$combo['seo_description']
        );
        
        $this->assign('seo',$seo);
        $this->assign('store',$store);
        $this->assign('combo',$combo);
        return $this->fetch('combodetail');
    }

    /**
     * 添加评论
     * @return   string(json)
     */
    public function addcomment(){
        $data = input('post.');
        $info = $data['info'];
        $result = array('flag'=>0,'msg'=>'添加评论失败');
        if($info){
            $info['member_id'] = 889;
            if($info['member_id']){
                $member = Db::name('member')->where('id',$info['member_id'])->field('name,mobile')->find();
                $info['member_name'] = $member['name'];
                $info['mobile'] = $member['mobile'];
            }
            $info['comment_status'] = config('default_status');
            $info['created_time'] = time();
            $info['comment_time'] = date('Y-m-d H:i:s');
            $res = Db::name('comment')->insert($info);
            if($res){
                $result = array('flag'=>1,'msg'=>'添加评论成功');
            }
        }
        echo json_encode($result);
    }

    /**
     * 点击分页获取评论内容
     * @return   [string](json)
     */
    public function selectComment(){
        $result = array('flag'=>0,'data'=>'');
        $storeId = input('storeId');  
        $pageNum = input('pageNum');

        $commentNum = ($pageNum-1)*config('page_size');
        if(!(empty($pageNum)&&empty($storeId))){
            $commentWhere['store_id'] = $storeId;
            $commentWhere['comment_status'] = config('normal_status');
            $commentData =  Db::name('comment')->where($commentWhere)->order('created_time desc')->limit($commentNum,config('page_size'))->select();

            $commentNum = count($commentData);
            for($i=0;$i<$commentNum;$i++){
                $commentData[$i]['mobile'] = substr_replace($commentData[$i]['mobile'],'****',3,4);
                $commentData[$i]['ave'] =ceil(($commentData[$i]['service']+$commentData[$i]['environment']+$commentData[$i]['traffic']+$commentData[$i]['price'])/3);
                // $arrayId[] = $commentData[$i]['id'];
            }
            $result['flag'] = 1;
            $result['data'] = $commentData;;
            // dump($commentData);die;
        }
        echo json_encode($result);
    }

    /**
     * 点击分页获取套餐
     */
    public function selectCombo(){
        $result = array('flag'=>0,'data'=>'','countPage'=>'');
        $storeId = input('storeId');  
        $pageNum = input('pageNum');
        $type = input('type');
        $price = input('price');

        if(!empty($price)){   
            $priceArr = explode('-',$price);
            $arrlength = count($priceArr);
            if($arrlength>1){
                $comboWhere['platform_price'] = array('between',$priceArr);
            }else{
                $comboWhere['platform_price'] = array('lt',$priceArr[0]);
            }
        }
        if(!empty($type)){
            $comboWhere['combo_type'] = $type;
        }
        // dump($storeId);die;
        $comboNum = ($pageNum-1)*config('page_size');
        if(!(empty($pageNum)&&empty($storeId))){
            $comboWhere['store_id'] = $storeId;
            $comboWhere['status'] = config('normal_status');
            $comboData =  Db::name('etiquette_combo')->where($comboWhere)->order('created_time desc')->limit($comboNum,config('page_size'))->select();

            if($comboData){
                $result['flag'] = 1;
                $result['data'] = $comboData;
            }
            //算出总页数
            $comboNum = Db::name('etiquette_combo')->where($comboWhere)->count();
            $comboCountPage = ceil($comboNum/config('page_size'));
            $result['countPage'] = $comboCountPage;
            $result['comboNum'] = $comboNum;
        }
        echo json_encode($result);
    }

    /**
     * 留言预约服务商
     */
    public function appoint(){
        $input = input('post.');
        $result = ['code' => 0,'msg' => '预约失败'];
        $name = $input['name'];
        $mobile = $input['mobile'];
        $date = date('Y-m-d').' 00:00:00';
        $time = strtotime($date);

        $where = [
            'mobile' => $mobile,
            'category_id' => config('category_etiquette_id'),
            'created_time' => ['gt',$time]
        ];
        $count = Db::name('appoint')->where($where)->count();
        //验证IP
        $ip = request()->ip(1);
        $res=Db::name('appoint')->where("ip=".$ip." and created_time>".$time." and category_id =".config('category_etiquette_id'))->count();
        if($count > 0){
            $result = ['code' => 0,'msg' => '您已预约过了'];
        }else if($res > config('book_every_ip_num')){
            $result = ['code' => 0,'msg' => '今天预约次过多'];
        }else{
            $type = array_search('预约服务商',config('business_email_msg'));
            $emailData = weeksEmail($type);
            $admin_id = '';
            if(!empty($emailData)){
                $content = '预约时间：'.date('Y-m-d H:i:s',time()).'，预约人：'.$name.' 预约人手机号：'.$mobile.' 请及时联系！本邮件系统自动发送请忽回复！';
                foreach ($emailData as $val) {
                    $emails[] = $val['email_address'];
                    if($val['is_sendmsg']==1){
                        $admin_id = $val['admin_id'];
                    }
                }

                $status = 0;
                $send_time = '';
                if(sendMail($emails,'预约服务商',$content)){
                    $status = 1;
                    $send_time = date('Y-m-d H:i:s');
                }
                foreach ($emails as $v) {
                    $emailLog[] = [
                        'type'          => $type,
                        'email_address' => $v,
                        'title'         => '预约服务商',
                        'content'       => $content,
                        'status'        => $status,
                        'send_time'     => $send_time,
                        'creat_time'    => date('Y-m-d H:i:s')
                    ];
                }
                Db::name('EmailLog')->insertAll($emailLog);
            }
            $appointData = [
                'ip'           => request()->ip(1),
                'buyer'        => $name,
                'mobile'       => $mobile,
                'order_flow_id' => $admin_id,
                'created_time' => time(),
                'updated_time' => time(),
                'category_id'  => config('category_etiquette_id'),
                'status'       => config('default_status')
            ];
            $appoint = Db::name('appoint')->data($appointData)->insert();
            if($appoint){
                $result = ['code' => 1,'msg' => '预约成功!'];
            }
        }

        echo json_encode($result);
    }

    /**
     * 预约服务
     */
    public function reservation(){
        $id = input('id');
        $type=input('type');
        $store = [];
        if($id && $type==1){
            $store = Db::name('store')->where('id',$id)->field('id,name')->find();
        }else if($id && $type==2){
            $store = Db::name('etiquette_combo')->where('id',$id)->field('id,combo_name')->find();
        }
        $seo = $this->getseo();
        $member_mobile = decode(session('_yos'));
        $member_name = decode(session('name'));
        $this->assign([
            'seo' => $seo,
            'store' => $store,
            'type' => $type,
            'member_mobile' => $member_mobile,
            'member_name' => $member_name
        ]);

        return $this->fetch();
    }

    /**
     * 添加预约订单
     */
    public function addreservation(){
        $data = input('post.');
        $result = array('flag'=>0,'msg'=>'操作失败');
        if($data['type']==2){
            //预约套餐
            $combo = Db::name('etiquette_combo')->where('id',$data['id'])->find();
            $data['id'] = $combo['store_id'];
            $orderData['goods_id']  =  $combo['id'];
            $orderData['combo_sn']  =  $combo['combo_sn'];
            $orderData['combo_name']  =  $combo['combo_name'];
            $orderData['combo_type']  =  $combo['combo_type'];
        }
        //商家信息
        $store = Db::name('store')->where('id',$data['id'])->field('id,name,province_id,city_id,member_status,store_sn')->find();
        if(empty($store)){
            $result['flag'] = '0';
            $result['msg'] = '商家不存在';
        } else {
            //获取当前日期并生成时间戳
            $date = date("Y-m-d").' 00:00:00';
            $time = strtotime($date);
            //先判断该用户预约的次数
            $selectWhere['reservation_phone'] = $data['mobile'];
            $selectWhere['store_id'] = $store['id'];
            $selectWhere['created_time'] = array('gt',$time);
            $selectWhere['state'] = config('normal_status');
            $selectWhere['status'] = config('default_status');
            $appointNum = Db::name('order_service')->where($selectWhere)->count();
            if(!empty($appointNum)){
                $result['flag'] = '0';
                $result['msg'] = '该陵园您已预约！';
                echo json_encode($result); die;
            } else {
                 //查询相同手机号当天预约次数并进行判断
                $res = Db::name('order_service')->where("reservation_phone='".$data['mobile']."' and created_time>".$time)->count();
                if($res > config('book_every_ip_num')){
                    $result['flag'] = '0';
                    $result['msg'] = '今天预约次数过多';
                    echo json_encode($result); die;
                } else {
                    //如果用户输入的手机号和session中的手机号不一样，那么需要判断验证码是正确
                    if(is_null(session('_yos'))){
                        $msgcode = $data['code'];
                        $type = config('msgcode_type')['appoint'];
                        $msgModel = new MsgCode();
                        $code_result = $msgModel->checkMessageCode($type,$data['mobile'],$msgcode);
                        if(!$code_result){
                            $result['flag'] = '0';
                            $result['msg'] = '动态校验码错误';
                            echo json_encode($result);die;
                        } else {
                            //预约时如果账号没有注册过就从新注册
                            $member_id = getMemberId($data['mobile'],$data['name']);
                        }
                    } else {
                        //查询id
                        $memberWh['mobile'] = decode(session('_yos'));
                        $memberWh['name'] = decode(session('name'));
                        $member = Db::name('member')->where($memberWh)->field('id,name')->find();   
                        $member_id = $member['id'];
                    }

                    //商家联系人
                    $contact = Db::name('store_contact')->where('store_id',$data['id'])->field('contact_name,mobile,default_person,tel')->select(); 
                    if($contact){
                        //获取商家联系人信息
                        $contacStr = '';
                        //默认联系人
                        foreach($contact as $values){
                            if($values['default_person'] == 1){
                                $contacStr =  '商家联系人：'.$values['contact_name']; 
                                if(!empty($values['mobile'])){
                                    $contacStr .= ' 手机号：'.$values['mobile'];
                                }
                                if(!empty($values['tel'])){
                                    $contacStr .= ' 电话：'.$values['tel'];
                                }
                            }else{
                                foreach($contact as $val){
                                    $contacStr .= '商家联系人：'.$val['contact_name']; 
                                    if(!empty($val['mobile'])){
                                        $contacStr .= ' 手机号：'.$val['mobile'];
                                    }
                                    if(!empty($val['tel'])){
                                        $contacStr .= ' 电话：'.$val['tel'];
                                    }
                                }
                            }
                        }
                    } else {
                        $contacStr = "该商家暂无联系人,请相关人员进行核实";
                    }

                    //调用判断周几发送给谁的
                    $type = array_search('预约服务商',config('business_email_msg'));
                    $emailData = weeksEmail($type);
                    $admin_id = '';
                    $reslog = '';
                    if(!empty($emailData)){
                        $emailAdd = array();
                        $phoneAdd = array();
                        foreach($emailData as $val){
                            $emailAdd[] = $val['email_address']; 
                            if($val['is_sendmsg']==1 || $val['type']==1){
                                $phoneAdd[] = $val['phone']; 
                            }
                            if($val['is_sendmsg']==1){
                                $admin_id = $val['admin_id'];
                            }
                        }
                        
                        $allMember = getStoreMember();
                        if($store['member_status'] == 0){
                            $member = '非会员';
                        }else{
                            if(array_key_exists($store['member_status'], $allMember)){
                                $member = $allMember[$store['member_status']];
                            }
                        }
                        //发送邮件
                        $content = '预约时间：'.date('Y-m-d H:i:s',time()).'  预约人：'.$data['name'].'   预约人手机号：'.$data['mobile'].'   预约服务商:'.$store['name'].'('.$member.'),'.$contacStr;
                        if(sendMail($emailAdd,'预约服务商',$content)){
                            $status = 1;
                            $send_time = date('Y-m-d H:i:s');
                        }else{
                            $status = 0;
                        }

                        //插入数据库
                        foreach($emailData as $val){
                            $addData[] = array('type'=>$type,'email_address'=>$val['email_address'],'title'=>'预约服务商','content'=>$content,'status'=>$status,'send_time'=>$send_time,'creat_time'=>date('Y-m-d H:i:s'));
                        }
                        $reslog = Db::name('email_log')->insertAll($addData);
                    }

                    //组装订单信息start
                    $orderData['order_service_sn'] = makeServiceSn();
                    $orderData['order_type'] = 5;
                    $orderData['store_id'] = $store['id'];
                    $orderData['store_name'] = $store['name'];
                    $orderData['store_sn'] = $store['store_sn'];
                    $orderData['store_status'] = $store['member_status'];
                    $orderData['province_id'] = $store['province_id'];
                    $orderData['city_id'] = $store['city_id'];
                    $orderData['member_id'] = $member_id;
                    $orderData['reservation_person'] = $data['name'];
                    $orderData['reservation_phone'] = $data['mobile'];
                    $orderData['status'] = config('default_status');
                    $orderData['state'] = config('normal_status');
                    $orderData['price'] = 0;
                    $orderData['price'] = 0;
                    $orderData['order_flow_id'] = $admin_id;
                    $orderData['created_time'] = time();
                    $orderId = Db::name('order_service')->insertGetId($orderData);    
                    //订单信息end
                    //发送信息
                    if ($orderId) {
                        if($reslog){
                            $storeObj = new Store;
                            $storeObj->sendToBusiness($orderId, $data['name'], $data['mobile'], $store['name'],$contacStr,$phoneAdd);
                        }
                   
                        $result['flag'] = '10';
                        $result['msg'] = '预约成功';
                    } else{
                        $result['flag'] = '0';
                        $result['msg'] = '预约失败，请联系客服人员';
                    }

                }
            }

        }    
        echo json_encode($result);
    }

     /**
     * 获取验证码
     * @return json
     */
    public function verifyCode(){
        $input = input('post.');
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
            $send = true;
            $send = $msgObj->sendMsg($mobile,$message);
            $result = ['code' => 0,'msg' => '验证码发送失败'];
            if($send){
                $codeData = [
                    'mobile'       => $mobile,
                    'code'         => $randCode,
                    'type'         => config('msgcode_type.appoint'),
                    'created_time' => time()
                ];
                $addCodeMsg = Db::name('MsgCode')->data($codeData)->insert();
                if($addCodeMsg){
                    $msgLogData = [
                        'classify'     => config('msg_log_appoint'),
                        'mobile'       => $mobile,
                        'msg'          => $message,
                        'status'       => config('msg_send_status'),
                        'created_time' => time(),
                        'send_time'    => time()
                    ];
                    $msgLog = Db::name('MsgLog')->data($msgLogData)->insert();
                    if($msgLog){
                        $result = ['code' => 1,'msg' => '操作成功'];
                    }
                }
            }
        }
        echo json_encode($result);
    }

}
