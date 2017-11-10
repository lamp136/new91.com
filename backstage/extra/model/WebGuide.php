<?php
namespace back\extra\model;
use think\Model;

class WebGuide extends Model
{
    /**
     * 收录申请
     * @return object
     */
    public function apply(){
        return $this->hasOne('WebGuideApply','id','apply_id')->field('id,qq,email,tel');
    }

    /**
     * 市区
     * @return object
     */
    public function citys(){
        return $this->hasMany('region','pid','province_id')->where('status',config('normal_status'))->field('id,pid,name');
    }
}