<?php
namespace back\extra\model;
use think\Model;

class SearchKeywords extends Model
{
    /**
     * 省份
     * @return object
     */
    public function province(){
        return $this->hasOne('Region','id','province_id')->where(['status' => config('normal_status'),'level' => config('normal_status')])->field('id,name');
    }
}