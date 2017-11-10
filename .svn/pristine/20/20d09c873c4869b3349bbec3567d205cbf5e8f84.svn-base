<?php
namespace back\extra\model;
use think\Model;

class StoreRecommend extends Model
{
    /**
     * 商家
     * @return object
     */
    public function store(){
        return $this->hasOne('Store','id','store_id')->field('id,name');
    }
}