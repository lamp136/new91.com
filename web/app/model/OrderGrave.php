<?php
namespace web\app\model;
use think\Model;

class OrderGrave extends Model
{
    /**
     * 约车信息
     * @return object
     */
    public function tomb(){
        return $this->hasOne('OrderViewTomb','order_id','id')->field('order_id,appointer,rider_number,rider_phone,arrive_time,riding_address,store_name,is_special_rider,service_status,buyer_intention,accompany_info,result,pay_type,total_price,deposit,store_discount,91_discount,tomb_location,tomb_price,remark,is_repeat,repeat_apoint_remark,visit_store');
    }  

    /**
     * 沟通信息
     */
    public function need(){
        return $this->hasOne('OrderGraveNeed','order_id','id')->field('order_id,wirter_type,content,created_time');
    }
}