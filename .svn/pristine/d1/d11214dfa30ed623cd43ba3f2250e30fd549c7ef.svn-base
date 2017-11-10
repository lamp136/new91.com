<?php
namespace web\extra\model;
use think\Model;

class TombZone extends Model
{
    /**
     * 商家墓位
     * @return object
     */
    public function tombs(){
        return $this->hasMany('Tombs','tomb_zone_id','id')->where('status',config('normal_status'))->field('tomb_zone_id');
    }

}