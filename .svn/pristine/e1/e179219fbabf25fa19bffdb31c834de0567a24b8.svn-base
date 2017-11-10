<?php
namespace web\extra\model;
use think\Model;

class CelebrityCemetery extends Model
{
    /**
     * 商家
     * @return object
     */
    public function store(){
        return $this->hasOne('Store','id','store_id')->field('id,name,image,thumb_image');
    }
}