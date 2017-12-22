<?php
namespace web\extra\model;
use think\Model;

class CarDriver extends Model
{
    public function orders(){
        return $this->hasMany('OrderGrave','driver_id','id')->field('id,order_grave_sn,status,vehicle_status,reservation_person,reservation_landline,reservation_phone,driver_id,store_id,store_name');
    }
}