<?php
namespace back\extra\model;
use think\Model;

class StoreProfiles extends Model
{
    /**
     * 关联省
     * @return object
     */
    public function Province(){
        return $this->hasOne('Region','id','province_id')->field('id,name');
    }
    /**
     * 关联市
     * @return object
     */
    public function City(){
        return $this->hasOne('Region','id','city_id')->field('id,name');
    }
    /**
     * 关联分类表(商家类型)
     * @return object
     */
    public function Category(){
        return $this->hasOne('Category','id','category_id')->field('id,name');
    }
     /**
     * 关联分类表(商家集团)
     * @return object
     */
    public function CategoryGroup(){
        return $this->hasOne('Category','id','category_group_id')->field('id,name');
    }
}


