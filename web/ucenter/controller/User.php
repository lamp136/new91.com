<?php
namespace web\ucenter\controller;
use think\Controller;
use think\Db;
use web\extra\controller\Base;
use think\Session;
use web\extra\model\Member;
use web\extra\model\OrderGrave;
use web\extra\model\OrderService;
use think\captcha\Captcha;

class User extends Base
{
    public function _initialize(){
        parent::_initialize();
        $orderStatus = config('order_status_change');
        $this->assign('orderStatus',$orderStatus);
        if(!Session::has('_uid')){
            $this->redirect('/login');
        }
    }
    /**
     * 用户资料
     * @return void
     */
    public function users(){
        $mobile = decode(Session::get('_yos'));
        $member = [];
        if($mobile){
            $member = Member::with('bank')->where('mobile',$mobile)->field('id,name,mobile')->find();
        }
        $seo = [
            'seo_title'       => '个人中心-个人资料_91搜墓网',
            'seo_keywords'    => '个人中心,个人资料',
            'seo_description' => '个人中心,个人资料'
        ];
        $this->assign([
            'member' => $member,
            'seo'    => $seo
        ]);
        return $this->fetch();
    }

    /**
     * 修改用户信息
     * @return json
     */
    public function alter(){
        $input = input('post.');
        $result = ['code' => 0,'msg' => '操作失败'];
        $user = $input['member'];
        $bank = $input['bank'];
        $user['updated_time'] = date('Y-m-d H:i:s');
        $saveUser = Db::name('member')->update($user);
        if($saveUser){
            Session::set('name',encode($user['name']));
            Session::set('_yos',encode($user['mobile']));
            $result = ['code' => 1,'msg' => '修改成功'];
            if(!empty($bank['bank_type']) && $bank['bank_type'] != 0){
                if(strpos($bank['bank_account'], '**') !== false){
                    unset($bank['bank_account']);
                }
                $bankData = $bank;
                $bankType = explode('-',$bank['bank_type']);
                $bankData['bank_type'] = $bankType[0];
                $bankData['bank'] = $bankType[1];
                $bankData['status'] = config('normal_status');
                $bankCount = Db::name('MemberBank')->where(['member_id' => $user['id'],'status' => config('normal_status')])->count();
                if($bankCount > 0){
                    $handleBank = Db::name('MemberBank')->where('member_id',$user['id'])->update($bankData);
                }else{
                    $bankData['created_time'] = date('Y-m-d H:i:s');
                    $bankData['member_id'] = $user['id'];
                    $bankData['region_ip'] = request()->ip(1);
                    $handleBank = Db::name('MemberBank')->insert($bankData);
                }
                if($handleBank){
                    $result = ['code' => 1,'msg' => '修改成功'];
                }
            }
        }
        echo json_encode($result);
    }

    /**
     * 修改密码
     * @return void
     */
    public function change(){
        $mobile = decode(Session::get('_yos'));
        $user['id'] = '';
        if($mobile){
            $user = Db::name('member')->where('mobile',$mobile)->field('id')->find();
        }
        $seo = [
            'seo_title'       => '个人中心-修改密码_91搜墓网',
            'seo_keywords'    => '个人中心,修改密码',
            'seo_description' => '个人中心,修改密码'
        ];
        $this->assign([
            'userId' => $user['id'],
            'seo' => $seo
        ]);
        return $this->fetch();
    }

    /**
     * 修改密码
     * @return json
     */
    public function alterpwd(){
        $input = input('post.');
        $result = ['code' => 0,'msg' => '操作失败'];
        if($input['id']){
            $captcha = new Captcha;
            $check = $captcha->check($input['code']);
            if(!$check){
                $result = ['code' => 2,'msg' => '验证码错误'];
            }else{
                $oldPwd = encryptHome($input['old_pwd']);
                $member = Db::name('member')->where('id',$input['id'])->field('password')->find();
                if(empty($member) || $oldPwd != $member['password']){
                    $result = ['code' => 3,'msg' => '原始密码错误'];
                }else{
                    $data = [
                        'id' => $input['id'],
                        'password' => encryptHome($input['new_pwd'])
                    ];
                    $savePwd = Db::name('member')->data($data)->update();
                    if($savePwd){
                        Session::delete('name');
                        Session::delete('_yos');
                        $result = ['code' => 1,'msg' => '修改成功'];
                    }
                }
            }
        }
        echo json_encode($result);
    }

    /**
     * 陵园订单
     * @return void
     */
    public function cemeteryorders(){
        $seo = [
            'seo_title'       => '个人中心-预约看墓订单_91搜墓网',
            'seo_keywords'    => '个人中心,预约看墓订单',
            'seo_description' => '个人中心,预约看墓订单'
        ];
        $orderList = [];
        if(Session::has('_uid')){
            $uid = decode(Session::get('_uid'));
            $where = [
                'member_id' => $uid,
                'state' => config('normal_status')
            ];
            $orderList = OrderGrave::with('store')->where($where)->field('id,order_grave_sn,member_id,store_id,store_name,reservation_person,reservation_phone,buyer,mobile,appoint_time,status,deposit')->order('appoint_time desc')->paginate(config('page_size'),false);
            $page = $orderList->render();
        }
        $this->assign([
            'page' => $page,
            'orderList' => $orderList,
            'seo' => $seo,
        ]);
        return $this->fetch();
    }

    /**
     * 前台删除订单
     * @return json
     */
    public function delorder(){
        $id = input('get.id');
        $result = ['code' => 0,'msg' => '删除失败'];
        if($id){
            $ret = Db::name('OrderGrave')->where('id',$id)->setField('state',config('appoint_front_delete'));
            if($ret){
                $result = ['code' => 1,'msg' => '删除成功'];
            }
        }

        echo json_encode($result);
    }

    /**
     * 删除礼仪服务订单
     * @return json
     */
    public function deletique(){
        $id = input('get.id');
        $result = ['code' => 0,'msg' => '删除失败'];
        if($id){
            $ret = Db::name('OrderService')->where('id',$id)->setField('state',config('etique_front_delete'));
            if($ret){
                $result = ['code' => 1,'msg' => '删除成功'];
            }
        }

        echo json_encode($result);
    }

    /**
     * 礼仪服务订单
     * @return void
     */
    public function etiquetteorders(){
        if(Session::has('_uid')){
            $uid = decode(Session::get('_uid'));
            $where = [
                'member_id' => $uid,
                'state' => config('normal_status')
            ];
            $list = OrderService::with('store')->where($where)->field('id,order_service_sn,member_id,store_id,store_name,reservation_person,reservation_phone,created_time,status,deposit')->order('appoint_time desc')->paginate(config('page_size'),false);
            $page = $list->render();

            $seo = [
                'seo_title'       => '个人中心-礼仪服务订单_91搜墓网',
                'seo_keywords'    => '个人中心,礼仪服务订单',
                'seo_description' => '个人中心,礼仪服务订单'
            ];
            $this->assign([
                'page' => $page,
                'list' => $list,
                'seo' => $seo,
            ]);
            return $this->fetch();
        }
    }

    /**
     * 购墓订单评论
     * @return void
     */
    public function orderseval(){
        $orderId = input('id');
        $info = [];
        if($orderId){
            $info = OrderGrave::with('store')->where('id',$orderId)->field('id,order_grave_sn,member_id,store_id,store_name,reservation_person,reservation_phone,buyer,mobile,appoint_time,status,deposit')->find();
        }
        $seo = [
            'seo_title'       => '个人中心-预约看墓订单评论_91搜墓网',
            'seo_keywords'    => '个人中心,预约看墓订单评论',
            'seo_description' => '个人中心,预约看墓订单评论'
        ];
        $this->assign([
            'info' => $info,
            'seo' => $seo,
        ]);
        return $this->fetch();
    }

    /**
     * 礼仪服务订单评论
     * @return void
     */
    public function etiqueeval(){
        $orderId = input('id');
        $info = [];
        if($orderId){
            $info = OrderService::with('store')->where('id',$orderId)->field('id,order_service_sn,member_id,store_id,store_name,reservation_person,reservation_phone,appoint_time,status,deposit')->find();
        }
        $seo = [
            'seo_title'       => '个人中心-礼仪服务订单评论_91搜墓网',
            'seo_keywords'    => '个人中心,礼仪服务订单评论',
            'seo_description' => '个人中心,礼仪服务订单评论'
        ];
        $this->assign([
            'info' => $info,
            'seo' => $seo,
        ]);
        return $this->fetch();
    }

    /**
     * 提交评论
     * @return json
     */
    public function subeval(){
        $input = input('post.');
        $result = ['code' => 0,'msg' => '评论失败'];
        $eval = $input['eval'];
        $evalData = [
            'member_id'      => decode(Session::get('_uid')),
            'member_name'    => decode(Session::get('name')),
            'mobile'         => decode(Session::get('_yos')),
            'environment'    => !empty($eval['hj']) ? (int)$eval['hj'] : 0,
            'price'          => !empty($eval['jg']) ? (int)$eval['jg'] : 0,
            'traffic'        => !empty($eval['jt']) ? (int)$eval['jt'] : 0,
            'service'        => !empty($eval['fw']) ? (int)$eval['fw'] : 0,
            'content'        => $eval['content'],// ???非法词过滤
            'comment_time'   => date('Y-m-d H:i:s'),
            'store_id'       => $eval['store_id'],
            'created_time'   => time()
        ];
        $insert = Db::name('comment')->data($evalData)->insert();
        if($insert){
            $result = ['code' => 1,'msg' => '评论成功'];
        }
        echo json_encode($result);
    }
}