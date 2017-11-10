<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 主要用于首页和导航的URL拼接
 *
 * @param string $prefix
 *
 * @return String
 */
/*function getHomeUrl($prefix='') {
    $abbr = strtolower(Session::get('ip_region_abbr'));
    $qgAbbr = strtolower(C('CHINA_ABBR'));
    if (empty($abbr) || $abbr == $qgAbbr) {
        if ($prefix) {
            $url = '/'.$prefix;
        } else {
            $url = '/';
        }

    } else {
        if ($prefix) {
            $url = '/'.$prefix.'/'.$abbr;
        } else {
            $url = '/'.$abbr;
        }
    }

    return url($url);
}*/
/**
 * 前台密码加密
 * @param string $pwd 用户输入的数据
 * @return string
 */
function encryptHome($pwd) {
    $prefix = substr(md5(config('h_prefix')), 0, 16);
    $suffix = substr(md5(config('h_suffix')), 0, 16);
    $encryptHPwd = md5($prefix.md5($pwd).$suffix);
    return $encryptHPwd;
}

/**
* 字符串截取，支持中文和其他编码
* @static
* @access public
* @param string $str 需要转换的字符串
* @param int      $start 开始位置
* @param int      $length 截取长度
* @param string $charset 编码格式
* @param string $suffix 截断显示字符
*
* @return string
*/
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=false) {
    if(function_exists("mb_substr"))
       $slice = mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
       $slice = iconv_substr($str,$start,$length,$charset);
    }else{
       $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
       $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
       $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
       $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
       preg_match_all($re[$charset], $str, $match);
       $slice = join("",array_slice($match[0], $start, $length));
    }
    if(mb_strlen($str,'UTF-8') < $length){
        return $slice;
    }else{
        return $slice.'...';
    }
}

/**
 * 获取排班邮箱
 * @return array
 */
 function weeksEmail($type){
     $where = [];
     $where['type'] = $type;
     $email  = think\Db::name('EmailSheet')->where($where)->whereOr(['type' => array_search('所有邮件（超管）',config('business_email_msg'))])->field('email_address,admin_id,phone,type,is_sendmsg,name')->select();
     return $email;
 }

// function weeksEmail(){
//     //判断时间 如果当前时间大于19:00 则直接更改今天的天数
//     $hours = date("G");
//     $today = date('w');
//     $where = [];
//     $where['week'] =$today;
//     if($hours > 19){
//         $where['week'] = $today + 1;
//         if($today == '6'){
//             $where['week'] = '0';
//         }
//     }
//     $email  = think\Db::name('weekends')->where($where)->field('email_address,admin_id')->find();
//     return $email;
// }

/*
 * 生成订单SN 12位数字(年月日8位+类型1位+递增数3位)eg:201708182100 
 * 
 * @return string
 */
function makeSn(){
    $options = [ 
        'type' => 'File',
        'path' => config('order_sn_cache_path'),
    ]; 
    // 缓存初始化
    think\Cache::init($options);
    $timestring = date('Ymd', time());
    $key = 'order_sn_'.config('ordersn_type.tombs').$timestring;
    $orderSN = cache($key);
    if ($orderSN) {
        $orderSN +=rand(1,9);
    } else {
        $num = rand(100,200);
        $orderSN = $timestring.config('ordersn_type.tombs').$num;
    }
    cache($key,$orderSN,config('order_sn_cache_time'));
    return (int)$orderSN;
}

/*
 * 邮件发送函数
 * @input $to|邮箱地址（群发为数组）;
 * @input $subject|邮件标题;
 * @input $content|邮件内容;
 * @return boolean;
 */

function sendMail($to, $subject, $content) {
    // Vendor('PHPMailer.classphpmailer');
    $mail = new \common\phpmailer\phpmailer;
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
        for($i = 0; $i < $to_num; $i++){
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
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装） 
 * @return mixed
 */
function get_client_ip($type = 0,$adv=false) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 获取用户信息
 * @param  int     $mobile 用户手机号
 * @param  varchar $name   用户名字
 * @return int
 */
function getMemberId($mobile,$name){
    $member = think\Db::name('member')->where('mobile',$mobile)->find();
    // 如果用户不存在注册新用户
    if(empty($member)){
        /**
         * 密码：手机后6位
         * @var string
         */
        $password =  encryptHome(substr($mobile,-6));
        $userData = [
            'mobile'       => $mobile,
            'name'         => $name,
            'member_type'  => config('member_front_register'),
            'check_mobile' => config('normal_status'),
            'password'     => $password,
            'reg_ip'       => request()->ip(1,true),
            'status'       => config('normal_status'),
            'created_time' => date('Y-m-d H:i:s')
        ];
        $register = think\Db::name('member')->insertGetId($userData);
        if($register){
            session('_uid',encode($register));
            session('name',encode($name));
            session('_yos',encode($mobile));
            return $register;
        }else{
            return 0;
        }
    }
    session('_uid',encode($member['id']));
    session('name',encode($member['name']));
    session('_yos',encode($member['mobile']));
    return $member['id'];
}

/**
 * 获取商家会员信息
 *
 * @param bool $keyVal 判断获取的数据格式，默认是 true ,返回值：array('20'=>'商家会员'),如果是： false，返回值： array(20)
 * @param string $type 获取商家会员是显示优惠 还是现实V，商家会员20 和 个人会员是 优惠，以及广告会员
 *
 * @return array
 */
function getStoreMember($keyFlag=true, $dataFlag = '') {
    if ($keyFlag) {
        switch ($dataFlag) {
            case 'sj'://获取商家的会员 
                $storeMembers = array(
                    config('store_member') => config('store_member_msg'),
                );
                break;
            case 'gr'://获取商家的会员 
                $storeMembers = array(
                    config('store_member_person') => config('store_member_person_msg'),
                );
                break;
            case 'ad'://只获取广告
                $storeMembers = array(
                    config('store_member_ad') => config('store_member_ad_msg')
                );
                break;
            case 'all'://所有会员
            default:
                $storeMembers = array(
                    config('store_member') => config('store_member_msg'),
                    config('store_member_person') => config('store_member_person_msg'),
                    config('store_member_ad') => config('store_member_ad_msg'),
                );
        }
    } else {
        switch ($dataFlag){
            case 'business'://商家会员
                $storeMembers = array(
                    config('store_member')
                );
                break;
            case 'ad'://广告会员
                $storeMembers = array(
                    config('store_member_ad')
                );
                break;
            case 'gr'://广告会员
                $storeMembers = array(
                    config('store_member_person')
                );
                break;
            case 'yh'://个人会员和商家会员
                $storeMembers = array(
                    config('store_member'),
                    config('store_member_person'),
                );
                break;
            case 'all'://所有会员
            default:
                $storeMembers = array(
                    config('store_member'),
                    config('store_member_person'),
                    config('store_member_ad')
                );
        }
    }

    return $storeMembers;

}

/**
 * 加密
 * @param String $string 需要加密的字串
 * @param String $skey 加密EKY
 * @return String
 */
function encode($string='',$skey = 'cxphp') {
    $strArr = str_split(base64_encode($string));
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key < $strCount && $strArr[$key].=$value;
    return str_replace(array('=', '+', '/'), array('OoooO', 'o0o0o', '0o0o0'), join('', $strArr));
}
/**
 * 解密
 * @param String $string 需要解密的字串
 * @param String $skey 解密KEY
 * @return String
 */
function decode($string='',$skey = 'cxphp') {
    $strArr = str_split(str_replace(array('OoooO', 'o0o0o', '0o0o0'), array('=', '+', '/'), $string), 2);
    $strCount = count($strArr);
    $tmpdata = str_split($skey);
    if($strCount > 1){
         foreach ($tmpdata as $key => $value){
            $key <= $strCount && isset($strArr[$key])&& $strArr[$key][1] === $value&& $strArr[$key] = $strArr[$key][0];
         }
    }
    return base64_decode(join('', $strArr));
}

/**
 * 服务订单sn
 */
function makeServiceSn(){
    $options = [ 
        'type' => 'File',
        'path' => config('order_sn_cache_path'),
    ];  
    // 缓存初始化
    think\Cache::init($options);
    $timestring = date('Ymd', time());
    $key = 'order_sn_'.config('ordersn_type.service').$timestring;
    $orderSN = cache($key);
    if ($orderSN) {
        $orderSN +=rand(1,9);;
    } else {
        $num = rand(100,200);
        $orderSN = $timestring.config('ordersn_type.service').$num;
    }
    cache($key,$orderSN,config('order_sn_cache_time'));
    return (int)$orderSN;
}
/**
 * 套餐sn
 */
function makeComboSn(){
    $options = [ 
        'type' => 'File',
        'path' => config('order_sn_cache_path'),
    ]; 
    // 缓存初始化
    think\Cache::init($options);
    $timestring = date('Ymd', time());
    $key = 'order_sn_'.config('ordersn_type.combo').$timestring;
    $orderSN = cache($key);
    if ($orderSN) {
        $orderSN +=rand(1,9);;
    } else {
        $num = rand(100,200);
        $orderSN = $timestring.config('ordersn_type.combo').$num;
    }
    cache($key,$orderSN,config('order_sn_cache_time'));
    return (int)$orderSN;
}

/**
 * 阴历转换
 * @param  [string] $riqi [传入年月日(20170112)]
 * @return [array]       [day是天的阴历 monthDay是月加天]
 */
function nongli($riqi)
{
//优化修改 20160807 FXL 
$nian=date('Y',strtotime($riqi));
$yue=date('m',strtotime($riqi));
$ri=date('d',strtotime($riqi));
  
 #源码部分原作者：沈潋(S&S Lab) 
 #农历每月的天数 
 $everymonth=array( 
          0=>array(8,0,0,0,0,0,0,0,0,0,0,0,29,30,7,1), 
          1=>array(0,29,30,29,29,30,29,30,29,30,30,30,29,0,8,2), 
          2=>array(0,30,29,30,29,29,30,29,30,29,30,30,30,0,9,3), 
          3=>array(5,29,30,29,30,29,29,30,29,29,30,30,29,30,10,4), 
          4=>array(0,30,30,29,30,29,29,30,29,29,30,30,29,0,1,5), 
          5=>array(0,30,30,29,30,30,29,29,30,29,30,29,30,0,2,6), 
          6=>array(4,29,30,30,29,30,29,30,29,30,29,30,29,30,3,7), 
          7=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,4,8), 
          8=>array(0,30,29,29,30,30,29,30,29,30,30,29,30,0,5,9), 
          9=>array(2,29,30,29,29,30,29,30,29,30,30,30,29,30,6,10), 
          10=>array(0,29,30,29,29,30,29,30,29,30,30,30,29,0,7,11), 
          11=>array(6,30,29,30,29,29,30,29,29,30,30,29,30,30,8,12), 
          12=>array(0,30,29,30,29,29,30,29,29,30,30,29,30,0,9,1), 
          13=>array(0,30,30,29,30,29,29,30,29,29,30,29,30,0,10,2), 
          14=>array(5,30,30,29,30,29,30,29,30,29,30,29,29,30,1,3), 
          15=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,2,4), 
          16=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,3,5), 
          17=>array(2,30,29,29,30,29,30,30,29,30,30,29,30,29,4,6), 
          18=>array(0,30,29,29,30,29,30,29,30,30,29,30,30,0,5,7), 
          19=>array(7,29,30,29,29,30,29,29,30,30,29,30,30,30,6,8), 
          20=>array(0,29,30,29,29,30,29,29,30,30,29,30,30,0,7,9), 
          21=>array(0,30,29,30,29,29,30,29,29,30,29,30,30,0,8,10), 
          22=>array(5,30,29,30,30,29,29,30,29,29,30,29,30,30,9,11), 
          23=>array(0,29,30,30,29,30,29,30,29,29,30,29,30,0,10,12), 
          24=>array(0,29,30,30,29,30,30,29,30,29,30,29,29,0,1,1), 
          25=>array(4,30,29,30,29,30,30,29,30,30,29,30,29,30,2,2), 
          26=>array(0,29,29,30,29,30,29,30,30,29,30,30,29,0,3,3), 
          27=>array(0,30,29,29,30,29,30,29,30,29,30,30,30,0,4,4), 
          28=>array(2,29,30,29,29,30,29,29,30,29,30,30,30,30,5,5), 
          29=>array(0,29,30,29,29,30,29,29,30,29,30,30,30,0,6,6), 
          30=>array(6,29,30,30,29,29,30,29,29,30,29,30,30,29,7,7), 
          31=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,8,8), 
          32=>array(0,30,30,30,29,30,29,30,29,29,30,29,30,0,9,9), 
          33=>array(5,29,30,30,29,30,30,29,30,29,30,29,29,30,10,10), 
          34=>array(0,29,30,29,30,30,29,30,29,30,30,29,30,0,1,11), 
          35=>array(0,29,29,30,29,30,29,30,30,29,30,30,29,0,2,12), 
          36=>array(3,30,29,29,30,29,29,30,30,29,30,30,30,29,3,1), 
          37=>array(0,30,29,29,30,29,29,30,29,30,30,30,29,0,4,2), 
          38=>array(7,30,30,29,29,30,29,29,30,29,30,30,29,30,5,3), 
          39=>array(0,30,30,29,29,30,29,29,30,29,30,29,30,0,6,4), 
          40=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,7,5), 
          41=>array(6,30,30,29,30,30,29,30,29,29,30,29,30,29,8,6), 
          42=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,9,7), 
          43=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,10,8), 
          44=>array(4,30,29,30,29,30,29,30,29,30,30,29,30,30,1,9), 
          45=>array(0,29,29,30,29,29,30,29,30,30,30,29,30,0,2,10), 
          46=>array(0,30,29,29,30,29,29,30,29,30,30,29,30,0,3,11), 
          47=>array(2,30,30,29,29,30,29,29,30,29,30,29,30,30,4,12), 
          48=>array(0,30,29,30,29,30,29,29,30,29,30,29,30,0,5,1), 
          49=>array(7,30,29,30,30,29,30,29,29,30,29,30,29,30,6,2), 
          50=>array(0,29,30,30,29,30,30,29,29,30,29,30,29,0,7,3), 
          51=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,8,4), 
          52=>array(5,29,30,29,30,29,30,29,30,30,29,30,29,30,9,5), 
          53=>array(0,29,30,29,29,30,30,29,30,30,29,30,29,0,10,6), 
          54=>array(0,30,29,30,29,29,30,29,30,30,29,30,30,0,1,7), 
          55=>array(3,29,30,29,30,29,29,30,29,30,29,30,30,30,2,8), 
          56=>array(0,29,30,29,30,29,29,30,29,30,29,30,30,0,3,9), 
          57=>array(8,30,29,30,29,30,29,29,30,29,30,29,30,29,4,10), 
          58=>array(0,30,30,30,29,30,29,29,30,29,30,29,30,0,5,11), 
          59=>array(0,29,30,30,29,30,29,30,29,30,29,30,29,0,6,12), 
          60=>array(6,30,29,30,29,30,30,29,30,29,30,29,30,29,7,1), 
          61=>array(0,30,29,30,29,30,29,30,30,29,30,29,30,0,8,2), 
          62=>array(0,29,30,29,29,30,29,30,30,29,30,30,29,0,9,3), 
          63=>array(4,30,29,30,29,29,30,29,30,29,30,30,30,29,10,4), 
          64=>array(0,30,29,30,29,29,30,29,30,29,30,30,30,0,1,5), 
          65=>array(0,29,30,29,30,29,29,30,29,29,30,30,29,0,2,6), 
          66=>array(3,30,30,30,29,30,29,29,30,29,29,30,30,29,3,7), 
          67=>array(0,30,30,29,30,30,29,29,30,29,30,29,30,0,4,8), 
          68=>array(7,29,30,29,30,30,29,30,29,30,29,30,29,30,5,9), 
          69=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,6,10), 
          70=>array(0,30,29,29,30,29,30,30,29,30,30,29,30,0,7,11), 
          71=>array(5,29,30,29,29,30,29,30,29,30,30,30,29,30,8,12), 
          72=>array(0,29,30,29,29,30,29,30,29,30,30,29,30,0,9,1), 
          73=>array(0,30,29,30,29,29,30,29,29,30,30,29,30,0,10,2), 
          74=>array(4,30,30,29,30,29,29,30,29,29,30,30,29,30,1,3), 
          75=>array(0,30,30,29,30,29,29,30,29,29,30,29,30,0,2,4), 
          76=>array(8,30,30,29,30,29,30,29,30,29,29,30,29,30,3,5), 
          77=>array(0,30,29,30,30,29,30,29,30,29,30,29,29,0,4,6), 
          78=>array(0,30,29,30,30,29,30,30,29,30,29,30,29,0,5,7), 
          79=>array(6,30,29,29,30,29,30,30,29,30,30,29,30,29,6,8), 
          80=>array(0,30,29,29,30,29,30,29,30,30,29,30,30,0,7,9), 
          81=>array(0,29,30,29,29,30,29,29,30,30,29,30,30,0,8,10), 
          82=>array(4,30,29,30,29,29,30,29,29,30,29,30,30,30,9,11), 
          83=>array(0,30,29,30,29,29,30,29,29,30,29,30,30,0,10,12), 
          84=>array(10,30,29,30,30,29,29,30,29,29,30,29,30,30,1,1), 
          85=>array(0,29,30,30,29,30,29,30,29,29,30,29,30,0,2,2), 
          86=>array(0,29,30,30,29,30,30,29,30,29,30,29,29,0,3,3), 
          87=>array(6,30,29,30,29,30,30,29,30,30,29,30,29,29,4,4), 
          88=>array(0,30,29,30,29,30,29,30,30,29,30,30,29,0,5,5), 
          89=>array(0,30,29,29,30,29,29,30,30,29,30,30,30,0,6,6), 
          90=>array(5,29,30,29,29,30,29,29,30,29,30,30,30,30,7,7), 
          91=>array(0,29,30,29,29,30,29,29,30,29,30,30,30,0,8,8), 
          92=>array(0,29,30,30,29,29,30,29,29,30,29,30,30,0,9,9), 
          93=>array(3,29,30,30,29,30,29,30,29,29,30,29,30,29,10,10), 
          94=>array(0,30,30,30,29,30,29,30,29,29,30,29,30,0,1,11), 
          95=>array(8,29,30,30,29,30,29,30,30,29,29,30,29,30,2,12), 
          96=>array(0,29,30,29,30,30,29,30,29,30,30,29,29,0,3,1), 
          97=>array(0,30,29,30,29,30,29,30,30,29,30,30,29,0,4,2), 
          98=>array(5,30,29,29,30,29,29,30,30,29,30,30,29,30,5,3), 
          99=>array(0,30,29,29,30,29,29,30,29,30,30,30,29,0,6,4), 
          100=>array(0,30,30,29,29,30,29,29,30,29,30,30,29,0,7,5), 
          101=>array(4,30,30,29,30,29,30,29,29,30,29,30,29,30,8,6), 
          102=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,9,7), 
          103=>array(0,30,30,29,30,30,29,30,29,29,30,29,30,0,10,8), 
          104=>array(2,29,30,29,30,30,29,30,29,30,29,30,29,30,1,9), 
          105=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,2,10), 
          106=>array(7,30,29,30,29,30,29,30,29,30,30,29,30,30,3,11), 
          107=>array(0,29,29,30,29,29,30,29,30,30,30,29,30,0,4,12), 
          108=>array(0,30,29,29,30,29,29,30,29,30,30,29,30,0,5,1), 
          109=>array(5,30,30,29,29,30,29,29,30,29,30,29,30,30,6,2), 
          110=>array(0,30,29,30,29,30,29,29,30,29,30,29,30,0,7,3), 
          111=>array(0,30,29,30,30,29,30,29,29,30,29,30,29,0,8,4), 
          112=>array(4,30,29,30,30,29,30,29,30,29,30,29,30,29,9,5), 
          113=>array(0,30,29,30,29,30,30,29,30,29,30,29,30,0,10,6), 
          114=>array(9,29,30,29,30,29,30,29,30,30,29,30,29,30,1,7), 
          115=>array(0,29,30,29,29,30,29,30,30,30,29,30,29,0,2,8), 
          116=>array(0,30,29,30,29,29,30,29,30,30,29,30,30,0,3,9), 
          117=>array(6,29,30,29,30,29,29,30,29,30,29,30,30,30,4,10), 
          118=>array(0,29,30,29,30,29,29,30,29,30,29,30,30,0,5,11), 
          119=>array(0,30,29,30,29,30,29,29,30,29,29,30,30,0,6,12), 
          120=>array(4,29,30,30,30,29,30,29,29,30,29,30,29,30,7,1) 
          ); 
############################## 
 #农历天干 
 $mten=array("null","甲","乙","丙","丁","戊","己","庚","辛","壬","癸"); 
 #农历地支 
 $mtwelve=array("null","子(鼠)","丑(牛)","寅(虎)","卯(兔)","辰(龙)", 
         "巳(蛇)","午(马)","未(羊)","申(猴)","酉(鸡)","戌(狗)","亥(猪)"); 
 #农历月份 
 $mmonth=array("闰","正","二","三","四","五","六", 
        "七","八","九","十","十一","十二","月"); 
 #农历日 
 $mday=array("null","初一","初二","初三","初四","初五","初六","初七","初八","初九","初十", 
       "十一","十二","十三","十四","十五","十六","十七","十八","十九","二十", 
       "廿一","廿二","廿三","廿四","廿五","廿六","廿七","廿八","廿九","三十"); 
############################## 
 #星期 
 $weekday = array("星期日","星期一","星期二","星期三","星期四","星期五","星期六"); 
 #阳历总天数 至1900年12月21日 
 $total=11; 
 #阴历总天数 
 $mtotal=0; 
############################## 
 #获得当日日期 
 //$today=getdate(); //获取今天的日期
 if($nian<1901 || $nian>2020) die("年份出错！"); 
 //$cur_wday=$today["wday"]; //星期中第几天的数字表示
 for($y=1901;$y<$nian;$y++) { //计算到所求日期阳历的总天数-自1900年12月21日始,先算年的和 
    $total+=365; 
    if ($y%4==0) $total++; 
 } 
 switch($yue) { //再加当年的几个月 
     case 12: 
       $total+=30; 
     case 11: 
       $total+=31; 
     case 10: 
       $total+=30; 
     case 9: 
       $total+=31; 
     case 8: 
       $total+=31; 
     case 7: 
       $total+=30; 
     case 6: 
       $total+=31; 
     case 5: 
       $total+=30; 
     case 4: 
       $total+=31; 
     case 3: 
       $total+=28; 
     case 2: 
       $total+=31; 
 } 
 if($nian%4 == 0 && $yue>2) $total++; //如果当年是闰年还要加一天 
 $total=$total+$ri-1; //加当月的天数 
 $flag1=0; //判断跳出循环的条件 
 $j=0; 
 while ($j<=120){ //用农历的天数累加来判断是否超过阳历的天数 
   $i=1; 
   while ($i<=13){ 
      $mtotal+=$everymonth[$j][$i]; 
      if ($mtotal>=$total){ 
         $flag1=1; 
         break; 
      } 
      $i++; 
   } 
   if ($flag1==1) break; 
   $j++; 
 } 
 if($everymonth[$j][0]<>0 and $everymonth[$j][0]<$i){ //原来错在这里，对闰月没有修补 
   $mm=$i-1; 
 } 
 else{ 
   $mm=$i; 
 } 
 if($i==$everymonth[$j][0]+1 and $everymonth[$j][0]<>0) { 
   $nlmon=$mmonth[0].$mmonth[$mm];#闰月 
 } 
 else { 
   $nlmon=$mmonth[$mm].$mmonth[13]; 
 } 
 #计算所求月份1号的农历日期 
 $md=$everymonth[$j][$i]-($mtotal-$total); 
 if($md > $everymonth[$j][$i]) 
   $md-=$everymonth[$j][$i]; 
 $nlday=$mday[$md]; 
   
 // $nowday=date("Y年n月j日 ")."w".$weekday[$cur_wday]." ".$mten[$everymonth[$j][14]].$mtwelve[$everymonth[$j][15]]."年".$nlmon.$nlday; 
 // $nowday=$mten[$everymonth[$j][14]].$mtwelve[$everymonth[$j][15]]."年 ".$nlmon.$nlday;
 $nowday = array('day'=>$nlday,'monthDay'=>$nlmon.$nlday); 
 return $nowday;
}

/**
 * 上传单个文件
 * @param string    $imgName    form表单中文件域的name值
 * @param string    $dirName    图片保存的文件夹
 * @param array     $thumb       array(
  array('600', '600', 1),
  array('350', '350', 1),
  array('130', '130', 1),
  ))  宽、高、缩略图的处理方式
 * @return array
 */
function uploadOne($imgName,$dirName,$thumb=array(),$water=TRUE) {
    $img = request()->file($imgName);
    $maxSize = (int) Config('max_size');
    $ext = Config('ext');
    $umf = (int) ini_get('upload_max_filesize');
    $readSize = min($umf, $maxSize)* 1024 * 1024;
    $path = ROOT_PATH . Config('root_path') . $dirName;

    //上传文件
    $info = $img->validate(['size'=>$readSize,'ext'=>$ext])->move($path);
    $file_name = str_replace("\\", "/", $info->getSaveName());
    $save_path = substr($file_name,0,strpos($file_name,'/'));

    $fileError = $info->getError();
    $saveFile = config('img_host').'/'.$dirName .'/'. $file_name;
    $factFilename = Config('root_path') .$dirName .'/'. $file_name;
    if(!empty($fileError)){
        return $ret = array(
                    'ok' => 0,
                    'error' => $img->getError()
                );
    }else{
        $ret = array(
            'ok' => 1,
            'images' => array($saveFile),
        );
    }


    //添加水印
    if($water){
        $imgobj = new \think\File('.'.$factFilename);

        $image = \think\Image::open($imgobj);
        $image->water(Config('water_pic_path'),Config('water_southeast'),40)->save($path.'/'.$file_name);
    }
    //是否生成缩略图
    if($thumb){
        $image = \think\Image::open($imgobj);
        foreach ($thumb as $k => $v) {
            $_imgName = Config('root_path') .$dirName .'/'.$save_path.'/'. 'thumb_' . $v[0] . 'X' . $v[1] . '_' . $info->getFilename(); 

            // 把这个缩略图的名字放到要返回的图片中
            $ret['images'][] = config('img_host').'/'.$dirName .'/'.$save_path.'/'. 'thumb_' . $v[0] . 'X' . $v[1] . '_' . $info->getFilename();
            $image->thumb($v[0], $v[1], $v[2])->save(ROOT_PATH .$_imgName);

            // $water = \think\Image::open(ROOT_PATH .$_imgName);
            // $water->water(Config('water_pic_path'),Config('water_southeast'))->save(ROOT_PATH .$_imgName);
        }
    }
    return  $ret;
}