<?php
namespace back\extra\model;
use think\Model;

class CarDriver extends Model{
    public function cars(){
        return $this->hasMany('CarInfo','driver_id','id')->field('id,driver_id,models,models_image,plate_number');
    }
}