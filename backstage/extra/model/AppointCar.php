<?php
namespace back\extra\model;
use think\Model;

class AppointCar extends Model
{
    public function driverinfo(){
        return $this->hasOne('CarInfo','id','car_id');
    }
}