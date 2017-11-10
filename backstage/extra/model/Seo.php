<?php
namespace back\extra\model;
use think\Model;

class Seo extends Model{
    

    /**查询陵园联系人**/
	public function Category(){
		return $this->hasOne('Category','id','category_id')->field('name,is_show,pid,id');
	}

	/**
     * 省份
     * @return object
     */
    public function Province(){
        return $this->hasOne('Region','id','province_id')->where(['status' => config('normal_status'),'level' => config('normal_status')])->field('id,name');
    }
}
