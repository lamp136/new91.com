<?php
namespace back\extra\model;
use think\Model;

class Store extends Model
{
    /**
     * 省份
     * @return object
     */
    public function province(){
        return $this->hasOne('Region','id','province_id')->where(['status' => config('normal_status'),'level' => config('normal_status')])->field('id,name');
    }

    /**
     * 市区
     * @return object
     */
    public function city(){
        return $this->hasOne('Region','id','city_id')->where(['status' => config('normal_status'),'level' => 2])->field('id,name');
    }

    /**
     * 商家联系人
     * @return object
     */
    public function storecontact(){
        return $this->hasMany('StoreContact','store_id','id')->where('status',config('normal_status'))->field('id,store_id,contact_name,mobile,tel,remark');
    }

    /**
     * 合同
     * @return object
     */
    public function storeprofile(){
        return $this->hasOne('StoreProfiles','id','profiles_id')->field('id,return_amount');
    }

    /**
     * 档案价格图片
     * @return object
     */
    public function priceimage(){
        return $this->hasMany('StoreProfilesImage','profiles_id','profiles_id')->where('type',config('profiles_price_image'))->field('id,profiles_id,image_name,image,thumb_image');
    }

    /**
     * 商家墓位
     * @return object
     */
    public function tombs(){
        return $this->hasMany('Tombs','store_id','id')->where('status',config('normal_status'))->field('store_id,tomb_name,material,size,aspect,meridians,unit,maket_price,sales_price,category_id,category_pid,remarks');
    }

    /**
     * 名人墓地
     * @return object
     */
    public function celebrity(){
        return $this->hasMany('CelebrityCemetery','store_id','id')->where('status',config('normal_status'))->field('store_id,name');
    }
}