<?php
namespace back\extra\model;
use think\Model;

class OrderGrave extends Model{

	/**查询陵园联系人**/
	public function findmember(){
		return $this->hasOne('StoreContact','store_id','store_id')->field('contact_name,mobile,tel,store_id');
	}

	/**查询出车状态和次数**/
	public function findcarinfo(){
		return $this->hasOne('AppointCar','order_id','id')->field('order_id,status,vehicle,store_names,arrive_time');
	}

	/**查询看墓记录表**/
	public function findviewtomb(){
		return $this->hasOne('OrderViewTomb','order_id','id')->field('order_id,91_discount,store_discount,purpose,location');
	}


	/**查询看墓记录表**/
	public function appointcar(){
		return $this->HasMany('AppointCar','order_id','id')->field('order_id');
	}
	
	/**查询有无票据**/
	public function ordergravebill(){
		return $this->HasMany('OrderGraveBill','order_grave_id','id')->field('order_grave_id');
	}

	/**
	 * 订单信息
	 */
	public function ordergravemsg(){
		return $this->hasMany('OrderGraveMsg','order_id','id')->where(['classify' => ['eq',config('msg_cemetery')],'status' => ['egt',0]]);
	}

	public function flowman(){
		return $this->hasOne('Admin','id','order_flow_id')->field('id,name');
	}
        /**
	 * 商家联系人
	 */
	public function storecontact(){
            return $this->hasOne('storeContact','store_id','store_id')->field('store_id,contact_name,mobile,tel')->where(['status' => ['egt',0]]);
	}
}	