<?php
namespace web\login\controller;
use think\Controller;
use web\extra\controller\Base;
use think\Db;
use think\Request;
use common\sendmsg\SendMsg;
use think\Session;//session类
use think\Cookie;//cookie类

/**
 * 登录注册控制器
 * author heqingyu;
 * date   17/7/13 13:30:00;
 */
class Login extends Base{ 
    public function _initialize(){
        //用户登录信息name
        $member_name = decode(session('name'));
        $this->assign('member_name',$member_name);
       
        //获取当前路径
        $path  =  Request::instance()->controller();
        $this->assign('path',$path);
        $action = request()->action();
        $this->assign('action',$action);
        //查找省份 
        $province = array();
        $regions = $this->getRegionData(array('status'=>config('normal_status'),'pid'=>config('china_num')),array('id','name','flag','abbreviate'),'',false,'flag asc');
        foreach($regions as $val){
            $province[$val['flag']][$val['id']] = [
                'name' => $val['name'],
                'abbr' => strtolower($val['abbreviate'])
            ];
        }
        $this->assign('province',$province);
        
        //推荐关键词
        $currentProvinceId = decode(cookie::get('ip_region_id'));
        $this->getRecommendWords($currentProvinceId);
    }
    
    /*
     * 用户注册
     * @param   void;
     * @return  void;
     */
    public function register()
    {
        return $this->fetch();
    }
    /*
     * 发送手机验证码
     * @param   void;
     * @return  string(json);
     */
    public function sendmsg(){
        $result = array('flag'=>0,'msg'=>'false');
        $mobile = input('mobile');
        if(!empty($mobile)){
            $data['mobile'] = $mobile;
            $data['code'] = rand(100000,999999);
            $data['type'] = config('msg_code')['register'];
            $data['status'] = config('default_status');
            $data['created_time'] = time();
            if(Db::name('MsgCode')->data($data)->insert()){
                $result['flag'] = 1;
                $result['msg'] = 'ok';
                //$result['code'] = $data['code'];
            }
            //发送信息
            $sendmsgobj = new SendMsg();
            $msg = '验证码:'.$data['code'].' 【91搜墓网】';
            $sendmsgobj->sendMsg($data['mobile'],$msg);
        }
        echo json_encode($result);
    }
    /*
     * 提交注册
     * @param   void;
     * @return  string(json);
     */
    public function regsubmit(){
        $result = array('flag'=>0,'msg'=>'提示:提交失败!');
        $input = input('post.');
        if(!empty($input)){
            $codeWhere['code'] = $input['code'];
            $codeWhere['mobile'] = $input['mobile'];
            $codeWhere['status'] = config('default_status');
            $codeWhere['type'] = config('msg_code')['register'];
            $time = time()-config('code_time');
            $codeWhere['created_time'] = array('gt',$time);
            $findcode = Db::name('MsgCode')->where($codeWhere)->find();
            if(empty($findcode)){
                $result['flag'] = 1;
                $result['msg'] = '提示:验证失败';
            }else{
                //更新验证码
                Db::name('MsgCode')->where($codeWhere)->data('status',2)->update();
                //注册信息
                $data['member_type'] = config('member_front_register');
                $data['name'] = $input['name'];
                $data['mobile'] = $input['mobile'];
                $data['password'] = encryptHome($input['password']);
                $data['status'] = config('normal_status');
                $ip = request()->ip();
                $data['reg_ip'] = ip2long($ip);
                $data['created_time'] = date('Y-m-d H:i:s',time());
                $data['check_mobile'] = 1;//是否手机验证
                $memberid = Db::name('Member')->insertGetId($data);
                if($memberid){
                    $result['flag'] = 2;
                    $result['msg'] = '成功注册';
                    $memberdata = Db::name('Member')->where('id',$memberid)->find();
                    session('name',encode($memberdata['name']));
                    session('_yos',encode($memberdata['mobile']));
                    session('_uid',encode($memberdata['id']));
                }
            }
        }
        echo json_encode($result);
    }
    /*
     * 用户名唯一验证
     * @param   void;
     * @return  string(json);
     */
    public function namecheck(){
        $result = array('flag'=>0,'msg'=>'操作失败');
        $name = input('name');
        if(!empty($name)){
            $finddata = Db::name('Member')->where('name',$name)->find();
            if(!empty($finddata)){
                $result= array('flag'=>1,'msg'=>'ok');;
            }
        }
        echo json_encode($result);
    }
    /*
     * 手机号唯一验证
     * @param   void;
     * @return  string(json);
     */
    public function mobilecheck(){
        $result = array('flag'=>0,'msg'=>'操作失败');
        $mobile = input('mobile');
        if(!empty($mobile)){
            $finddata = Db::name('Member')->where('mobile',$mobile)->find();
            if(!empty($finddata)){
                $result= array('flag'=>1,'msg'=>'ok');;
            }
        }
        echo json_encode($result);
    }
    
    
    /*
     * 用户登录
     * @param   void;
     * @return  void;
     */
    public function login()
    {
        return $this->fetch();
    }
    /*
     * 提交登录
     * @param   void;
     * @return  string(json);
     */
    public function sublogin(){
        $result = array('flag'=>0,'msg'=>'登录失败');
        $input = input('post.');
        if(!empty($input)){
            $where['mobile|name'] = $input['mobile'];
            $where['password'] = encryptHome($input['password']);
            $member = Db::name('Member')->where($where)->find();
            if(!empty($member)){
                if($member['status']==config('normal_status')){
                    session('_uid',encode($member['id']));
                    session('name',encode($member['name']));
                    session('_yos',encode($member['mobile']));
                    $result['flag'] = 1;
                    $result['msg'] ='登陆成功';
                }else{
                    $result['flag'] = 0;
                    $result['msg'] ='提示:该账号被禁用,请联系客服人员';
                }
            }else{
                $result['flag'] = 0;
                $result['msg'] ='提示:该账号未注册!';
            }
        }
        echo json_encode($result);
    }
    /*
     * 手机登录发送证码
     * @param   void;
     * @return  string(json);
     */
    public function sendcode(){
        $result = array('flag'=>0,'msg'=>'false');
        $mobile = input('mobile');
        if(!empty($mobile)){
            $data['mobile'] = $mobile;
            $data['code'] = rand(100000,999999);
            $data['type'] = config('msg_code')['mobile_login'];
            $data['status'] = config('default_status');
            $data['created_time'] = time();
            if(Db::name('MsgCode')->data($data)->insert()){
                $result['flag'] = 1;
                $result['msg'] = 'ok';
                //$result['code'] = $data['code'];
            }
            //发送信息
            $sendmsgobj = new SendMsg();
            $msg = '验证码:'.$data['code'].' 【91搜墓网】';
            $sendmsgobj->sendMsg($data['mobile'],$msg);
        }
        echo json_encode($result);
    }
    
    /*
     * 提交手机登录
     * @param   void;
     * @return  string(json);
     */
    public function moblogin(){
        $result = array('flag'=>0,'msg'=>'登录失败');
        $input = input('post.');
        if(!empty($input)){
            $codewhere['mobile'] = $input['mobile'];
            $codewhere['code'] = $input['code'];
            $codewhere['status'] = config('default_status');
            $codeWhere['type'] = config('msg_code')['mobile_login'];
            $time = time()-config('code_time');
            $codeWhere['created_time'] = array('gt',$time);
            $codedata = Db::name('MsgCode')->where($codewhere)->find();
            $data['status'] = config('normal_status');
            Db::name('MsgCode')->where($codewhere)->data($data)->update();
            if(!empty($codedata)){
                $member = Db::name('Member')->where('mobile',$input['mobile'])->find();
                if(empty($member)){
                    $membinfo['member_type'] = config('member_front_register');
                    $membinfo['mobile'] = $membinfo['name'] = $input['mobile'];
                    $membinfo['status'] = config('normal_status');
                    $ip = request()->ip(1);
                    $membinfo['reg_ip'] = $ip;
                    $membinfo['created_time'] = date('Y-m-d H:i:s',time());
                    $membinfo['check_mobile'] = config('normal_status');//是否手机验证
                    $memberid = Db::name('Member')->insertGetId($membinfo);
                    if($memberid){
                        session('name',encode($input['mobile']));
                        session('_yos',encode($input['mobile']));
                        session('_uid',encode($memberid));
                        $result['flag'] = 1;
                        $result['msg'] ='登陆成功';
                    }
                }else{
                    if($member['status']==config('normal_status')){
                        session('name',encode($member['name']));
                        session('_yos',encode($member['mobile']));
                        session('_uid',encode($member['id']));
                        $result['flag'] = 1;
                        $result['msg'] ='登陆成功';
                    }else{
                        $result['flag'] = 0;
                        $result['msg'] ='该账号被禁用,请联系客服人员';
                    }
                }
            }
        }
        echo json_encode($result);
    }
    public function loginout(){
        Session::delete('name');
        Session::delete('_yos');
        Session::delete('_uid');
        $this->redirect('/');
    }
}
