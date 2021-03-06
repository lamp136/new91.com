<?php
namespace back\member\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use think\Session;//session类


/**
 * 陵园合作申请
 */
class Handtohand extends Base
{

	 /**商务跟踪判断**/
	   public function power(){
	        $where = array();
	        if(session('?admin_id')){
	          $ret=  Db::name('RoleUser')->where('user_id='.session('admin_id'))->field('role_job_id')->find();
	          $level = Db::name('Role')->where('id='.$ret['role_job_id'])->field('level')->find(); 
	          if($level['level'] == 3){
	            $where = session('admin_id');
	          }
	        }
	        return $where;
	    }

  

	/**
	 * 陵园合作列表
	 */
	public function index(){
		$getCondition = input('get.');
		if(!empty($getCondition['mobile'])){
			$where['mobile'] = $getCondition['mobile'];
		}
		if(!empty($getCondition['flow_man'])){
			$where['flow_man'] = $getCondition['flow_man'];
		}
      
        $ordermen =  $this->power();
        if($ordermen){
            $where['flow_man'] =  $ordermen;
        }
		$collaborate = Db::name('Collaborate');
		$arr = '0,1,2,3,4';
		$where['status'] = array('in',$arr);

		$pageSize = config('page_size');
        $list = $collaborate->where($where)->order('id desc')->paginate($pageSize,false,['query' => $getCondition]);
        $show = $list->render();
        $flow_man = $this->getBusinessMen(false,false);
        $this->assign('page',$show);
        $this->assign('flow_man',$flow_man);
        $this->assign('list',$list);
        return $this->fetch();
	}

	
	/**
	 * 跟踪人
	 */
	public function editflowMan(){
		if(Request::instance()->isPost()){
			$collaborate = Db::name('Collaborate');
			$info = input('post.');
	        $id = $info['id'];
	        $data['flow_man'] = $info['flow_man'];
	        $final  = $collaborate->where('id='.$id)->update($data);
	        if($final){
	           $result['flag'] = 1; 
	           $result['msg'] = '修改成功'; 
	        } 
			echo json_encode($result);
		}
	}

	 //查看删除原因
    public function lookreason(){
        $id = input('id');
        $reason = Db::name('collaborate')->where('id='.$id)->field('remark')->find();
        if($reason){
            $result['flag'] = 1;
            $result['data'] = $reason['remark'];
        }
        echo json_encode($result);
    }


    /**
     * 修改陵园合作申请中的status和添加备注
     */
    public function changstatus(){
        $collaborate = Db::name('Collaborate');
        $postVal = input('post.');
        $where['id']= $postVal['id'];
        $info['status'] = $postVal['sign'];
        if(!empty($postVal['remark'])){
        	$info['remark'] = $postVal['remark'];
        }
        $final = $collaborate->where($where)->update($info);
        if($final){
        	$result['flag'] = 1;
            $result['msg'] ='操作成功';
        }
        echo json_encode($result);
    }
}