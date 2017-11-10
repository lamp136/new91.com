<?php
namespace back\extra\model;
use think\Model;

class Member extends Model{
    /**操作人**/
    public function memberbank(){
        return $this->hasOne('MemberBank','member_id','id')->field('id,member_id,bank_type,bank,bank_account,bank_member,status');
    }

}