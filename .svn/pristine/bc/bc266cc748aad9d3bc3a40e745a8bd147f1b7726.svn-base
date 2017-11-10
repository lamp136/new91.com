<?php
namespace web\extra\model;
use think\Model;

class MsgCode extends Model
{
    /**
     * 判断一个手机号的短信发送次数是否达到了最大的发送次数
     * @return boolean
     */
    public function isMaxSendNum($mobile){
        $start = date("Y-m-d")." 00:00:00";
        $end = date("Y-m-d")." 23:59:59";
        $where = [
            'mobile' => $mobile,
            'created_time' => ['between',[$start,$end]]
        ];
        $total = $this->where($where)->count();
        if ((int)$total == config('everyday_send_message_num')) {
            return TRUE;
        }else{
            return FALSE;
        }
    }

    /**
     * 判断验证码是否正确，如果正确的话返回true,否则返回false
     * @param int $type                 验证码的类型
     * @param int $mobile               手机号
     * @param string $message_code      验证码
     * @return boolean
     */
    public function checkMessageCode($type,$mobile,$code){
        $where = [
            'type'         => $type,
            'mobile'       => $mobile,
            'code'         => $code,
            'status'       => config('message_code_available'),
            'created_time' => ['gt',(time() - 600)]
        ];
        $data = $this->field('mobile')->where($where)->find();
        if(empty($data)){
            return false;
        }else{
            //将状态设置为已失效。
            $result = $this->where($where)->setField('status',config('message_code_used'));
            return $result;
        }
    }
}