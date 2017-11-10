<?php
namespace back\extra\model;
use think\Model;

class WebGuideApply extends Model
{
    /**
     * 省份
     * @return object
     */
    public function province(){
        return $this->hasOne('region','id','province_id')->field('id,name');
    }

    /**
     * 市区
     * @return object
     */
    public function city(){
        return $this->hasOne('region','id','city_id')->field('id,name');
    }

    /**
     * 按省份查找市区
     * @return object
     */
    public function citys(){
        return $this->hasMany('region','pid','province_id')->field('id,pid,name');
    }
}