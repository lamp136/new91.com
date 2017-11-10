<?php
namespace back\extra\model;
use think\Model;

class OrderViewTomb extends Model{
    
    /**查询出车信息**/
    public function appointcar(){
        return $this->hasOne('AppointCar','id','appoint_car_id');
    }
    
    /**查询车辆信息**/
    public function carinfo(){
        return $this->hasOne('CarInfo','id','card_id');
    }
}