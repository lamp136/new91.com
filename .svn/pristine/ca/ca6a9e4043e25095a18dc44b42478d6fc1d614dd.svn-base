<?php
namespace back\extra\model;
use think\Model;

class OrderNeed extends Model
{
    /**
     * 回访记录
     * @return object
     */
    public function revisit(){
        return $this->hasMany('OrderRevisit','need_id','id')->field('admin_name,content,created_time,need_id')->order('created_time desc');
    }
}