<?php
namespace back\extra\model;
use think\Model;

class Comment extends Model{

	/**查询陵园联系人**/
	public function findstore(){
		return $this->hasOne('Store','id','store_id')->field('name,id');
	}


	
	public function findcombo(){
		return $this->hasOne('EtiquetteCombo','id','goods_id')->field('combo_name,id');
	}
	
	
}	