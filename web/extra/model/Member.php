<?php
namespace web\extra\model;
use think\Model;

class Member extends Model{

    /**
     * 银行信息
     * @return object
     */
    public function bank(){
        return $this->hasOne('MemberBank','member_id','id')->where('status',config('normal_status'))->field('id,member_id,bank_type,bank,bank_account,bank_member');
    }
}