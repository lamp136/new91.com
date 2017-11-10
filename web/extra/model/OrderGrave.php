<?php
namespace web\extra\model;
use think\Model;

class OrderGrave extends Model
{
    /**
     * 商家
     * @return object
     */
    public function store(){
        return $this->hasOne('Store','id','store_id')->field('id,image,thumb_image');
    }
    /**
     * 省份
     * @return object
     */
    public function province(){
        return $this->hasOne('Region','id','province_id')->where(['status' => 1,'level' => 1])->field('id,name');
    }

    /**
     * 市区
     * @return object
     */
    public function city(){
        return $this->hasOne('Region','id','city_id')->where(['status' => 1,'level' => 2])->field('id,name');
    }
    /**
     * 跟踪信息
     * @return object
     */
    public function revisit(){
        return $this->hasMany('OrderRevisit','order_id','id')->order('created_time desc')->field('order_id,content,admin_name,created_time');
    }
}