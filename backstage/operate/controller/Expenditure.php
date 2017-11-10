<?php 
namespace back\operate\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;

/**
 * 支出记录
 */
class Expenditure extends Base
{
	
	/**
	 * 推广支出
	 */
	public function index(){
		$disburse = Db::name('disburse');
		$fields = 'id,start_time,end_time,province_id,keyword,consume,status,hit,show,click_rate,average_click_price,category_id,reservation,cost,deal';
		$regionWhere['level'] = 1;
		$region = $this->getRegionData($regionWhere,array('id,name'),'',true);
        $this->assign('moneyUser',$this->getCategoryData(array('pid'=>config('money_user_category_id')),array('id,name'),'',true));
        $this->assign('region',$region);
		$this->_list($disburse,$fields);
        return $this->fetch();

	}



	private function _list($table,$fields){
        //时间段过滤
        $where = array();
        $getdata = input('get.');
        $start_time = '';
        $province_id = '';
        $end_time = '';
        $type = '';
        $info = '';
        if(!empty($getdata['start_time'])){
        	$start_time = $getdata['start_time'];
        }
        if(!empty($getdata['province_id'])){
        	$province_id = $getdata['province_id'];
        }
        if(!empty($getdata['end_time'])){
        	$end_time = $getdata['end_time'];
        } 
        if(!empty($getdata['type'])){
        	$type = $getdata['type'];
        } 
        if(!empty($getdata['order'])){
        	$info = $getdata['order'];
        }
       	
        //排序规则
        if($type &&  $info){
            $order = $type.' '.$info;
        }else{
            $order = 'start_time desc';
        }
        if(!empty($start_time) && empty($end_time)){
            $where['start_time'] = array('egt',strtotime($start_time));
        }
        if(!empty($end_time) && empty($start_time)){
            $where['end_time'] = array('elt',strtotime($end_time.' 23:59:59'));
        }
        if(!empty($start_time) && !empty($end_time)){
            $where['start_time'] = array('egt',strtotime($start_time));
            $where['end_time'] = array('elt',strtotime($end_time.' 23:59:59'));
        }
        if(!empty($province_id)){
            $where['province_id'] = $province_id;
        }
        $arr = array(config('normal_status'),config('delete_status'),config('default_status'));
        $where['status'] = array('in',$arr);
        $pageSize = Config('page_size');
        $list = $table->where($where)->field($fields)->order('end_time desc')->select();
		$data = $table->where($where)->field($fields)->order('end_time desc')->paginate($pageSize,false,['query' => $getdata]);
		$page = $data->render();
        //把点击率转换百分比
        foreach ($list as $key => $value) {
            $list[$key]['click_rate'] = ($value['click_rate'] * 100).'%';
        }
        $this->assign('page',$page);
        $this->assign('list',$list);
	}



	   /**
     * 删除推广支出
     */
    public function delDisburse(){
		$disburseModel = Db::name('disburse');
		$this->_delete($disburseModel);
    }

    private function _delete($table){
    	if(Request::instance()->isPost()){
            $postInfo = input('post.');
            $result = array('flag'=>0,'msg'=>'操作失败');
            if($postInfo['act']=='del'){
                $data['id'] = $postInfo['id'];
                $data['status'] = config('delete_status');
                // 状态改为-1
                if($table->update($data)){
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功';
                }
            }else if($postInfo['act']=='enable'){
                $data['id'] = $postInfo['id'];
                $data['status'] = config('normal_status');
                // 状态改为1
                if($table->update($data)){
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功';
                }
            }else if($postInfo['act'] == 'delAll'){
            	if($table->where('1=1')->delete()){
            		$result['flag'] = 1;
            		$result['msg'] = '操作成功';
            	}
            }
            echo json_encode($result);
        }
    }


    /**
	 * 编辑支出记录
	 */
	public function editDisbures(){
        $disburse = Db::name('disburse');
        if(Request::instance()->isPost()){
        	$postData = input('post.');
        	$postData['start_time'] = strtotime($postData['start_time']);
        	$postData['end_time'] = strtotime($postData['end_time']);
        	$postData['admin_id'] = session('admin_id');
            $result = array('flag'=>0,'msg'=>'操作失败');
            if($disburse->update($postData)){
                $result['flag'] = 1;
                $result['msg'] = '操作成功';
            }
            echo json_encode($result);
        }else if(Request::instance()->isGet()){
        	$getInfo = input('get.');
        	$id = $getInfo['id'];
            $result = array('flag'=>0,'data'=>array());
            if(!empty($id)){
                $data = $disburse->find($id);
                $data['start_time'] = date('Y-m-d',$data['start_time']);
                $data['end_time'] =  date('Y-m-d',$data['end_time']);
                if(!empty($data)){
                    $result['flag'] = 1;
                    $result['data'] = $data;
                }
            }
            echo json_encode($result);
        }
    }


	    /**
     * 获取省市数据
     * @param  array   $where  条件
     * @param  array   $fields 字段
     * @param  int     $num    条数
     * @param  boolean $bool   获取方式
     * @param  string  $sort   排序
     * @return array
     */
    public function getRegionData($where=array(), $fields=array(), $num=null, $bool=false,$sort=false){
        $regionModel = Db::name('region');
        if(!$bool){
            if($num == 1){
                $region = $regionModel->where($where)->field($fields)->find();
            }
            if(empty($num)){
                if($sort){
                    $region = $regionModel->where($where)->field($fields)->order($sort)->select();
                }else{
                    $region = $regionModel->where($where)->field($fields)->select();
                }
            }
        }else{
            $tmp = implode(',',$fields);
            if($sort){
                $region = $regionModel->where($where)->column($tmp);
            }else{
                $region = $regionModel->where($where)->order($sort)->column($tmp);
            }
        }
        return $region;
    }


     /**
     * 获取分类数据
     * @param  array   $where  条件
     * @param  array   $fields 字段
     * @param  int     $num    条数
     * @param  boolean $bool   获取方式
     * @return array
     */
    public function getCategoryData($where=array(), $fields=array(), $num=null, $bool=false){
        $categoryModel = DB::name('category');
        if(!$bool){
            if($num == 1){
                $category = $categoryModel->where($where)->field($fields)->find();
            }else if($num != 1 && empty($num)){
                $category = $categoryModel->where($where)->field($fields)->select();
            }
        }else{
            $tmp = implode(',',$fields);
            $category = $categoryModel->where($where)->column($tmp);
        }
        return $category;
    }


     /**
     * 获取订单预约量和成交量
     */
    public function getOrderNum(){
    	$inputdata = input('post.');
        $start_time = $inputdata['start_time'];
        $end_time = $inputdata['end_time'];
        $region = $inputdata['region'];
        $arr = $this->_getNumber($start_time,$end_time,$region);
        $result['success'] = $arr['success'];
        $result['default'] = $arr['default'];
        echo json_encode($result);
    }

    /**
     * 获取订单预约量和成交量的公共方法
     */
    public function _getNumber($start_time,$end_time,$province_id){
        if(!empty($start_time) && !empty($end_time)){
            $where['created_time'] = array('BETWEEN',array(strtotime($start_time),strtotime($end_time.' 23:59:59')));
        }
        if(!empty($province_id)){
            $where['province_id'] = array('eq',$province_id);
        }
        $status = array(config('order_status.default'),config('order_status.success'));
        $where['status'] = array('in',$status);
       
        $order = Db::name('OrderGrave');
        $data = $order->where($where)->field('province_id,status')->select();
        $success = 0;
        $default = 0;
        if($data){
            foreach ($data as $key => $value) {
                if($value['status']==10){
                    $success += 1;
                }else if($value['status']==0){
                    $default += 1;
                }
            }
        }
        return array('success'=>$success,'default'=>$default);
    }

    /**
	 * 添加支出记录
	 */
	public function addDisburse(){
		$addModel = Db::name('disburse');
		$postInfo = input('post.');
		$postInfo['start_time'] = strtotime($postInfo['start_time']);
		$postInfo['end_time'] = strtotime($postInfo['end_time']);
		$postInfo['admin_id'] = session('admin_id');
        $injectId = Db::name('inject')->where('status=1 and category_id='.$postInfo['category_id'])->order('id desc')->field('id')->find();
        $postInfo['inject_id'] = $injectId['id'];
		$this->_add($addModel,$postInfo);
	}

	private function _add($table,$data){
         $result = array('flag'=>0,'msg'=>'操作失败！');
		if($table->insert($data)){
			$result['flag'] = 1;
			$result['msg'] = '操作成功';
		}
		echo json_encode($result);
	}
}