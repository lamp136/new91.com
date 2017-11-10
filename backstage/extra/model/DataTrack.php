<?php
namespace back\extra\model;
use think\Model;

class DataTrack extends Model
{
    /**
     * 省份
     * @return object
     */
    public function province(){
        return $this->hasOne('Region','id','province_id')->where(['status' => config('normal_status'),'level' => config('normal_status')])->field('id,name');
    }

    /**
     * 市区
     * @return object
     */
    public function city(){
        return $this->hasOne('Region','id','city_id')->where(['status' => config('normal_status'),'level' => 2])->field('id,name');
    }

    /**
     * 管理员
     * @return object
     */
    public function admin(){
        return $this->hasOne('Admin','id','flow_man')->field('id,name');
    }
}