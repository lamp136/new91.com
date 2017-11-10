<?php
namespace back\extra\model;
use think\Model;

class RoleUser extends Model
{

 	/**
     * 查询Admin
     * @return object
     */
    public function relation(){
        return $this->hasOne('Admin','id','user_id')->field('id,name,email,status');
    }
    
    //
    public function admin(){
        return $this->hasOne('Admin','id','user_id')->field('id,name,status');
    }







}