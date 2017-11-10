<?php
namespace back\extra\model;
use think\Model;

class News extends Model{

    /**操作人**/
    public function nickname(){
        return $this->hasOne('admin','id','admin_id')->field('id,name');
    }

    /**所属分类名**/
    public function catname(){
        return $this->hasOne('category','id','category_id')->field('id,name');
    }
}   