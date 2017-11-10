<?php
namespace back\logs\controller;
use think\Controller;
use think\Request;
use think\Db;
use back\extra\controller\Base;
use back\extra\model\Privilege;
use think\Session;//session类
class Logshow extends Base{
	/**
	 * 操作记录
	 */
	public function index(){
		$logsModel = Db::name('logs');
		$this->_list($logsModel);
		return $this->fetch();
	}

	/**
	 * 登录记录
	 */
	public function loginlog(){
		$loginLogsModel =Db::name('loginLogs') ;
		$this->_list($loginLogsModel);
		return $this->fetch();
		
	}

	/**
	 * 公共列表方法
	 * @param  array $where 日志类型
	 * @return void
	 */
	private function _list($table){
		$getdata = input('get.');
		$pageSize = config('page_size');
        $logslist = $table->order('id desc')->paginate($pageSize,false,['query' => $getdata]);
        $pageshow  = $logslist->render();
        $alladmin = $this->getBusinessMen(false,false);
		$this->assign('order_flow',$alladmin);
		$this->assign('page',$pageshow);
		$this->assign('logs',$logslist);
	}

	//操作日志删除
	public function delLogs(){
		$logs = Db::name('logs');
		$this->_del($logs);
	}
	//登陆日志清空
	public function delLoginlogs(){
		$loginLogs =Db::name('loginLogs');
		$this->_del($loginLogs);
	}


	//操作日志清空
	public function deloperateLogs(){
		$loginLogs =Db::name('logs');
		$this->_del($loginLogs);
	}

	/**
	 * 删除\清空方法
	 * @param  int    $res 删除条件
	 * @param  string $act 方式是清空还是删除 delAll\del
	 */
	private function _del($table){
		$postInfo = input('post.');
		if($postInfo['act'] == 'delAll'){
			$where['id'] = array('neq',0);
			$success = '清空成功';
			$error = '清空失败';
		}else if($postInfo['act'] == 'del'){
			$where['id'] = $postInfo['id'];
			$success = '删除成功';
			$error = '删除失败';
		}
		$info = $table->where($where)->delete();

		if($info){
            $result['flag'] = 1;
            $result['msg'] = $success; 
        }else{
            $result['flag'] = 0;
            $result['msg'] = $error;
        }
        echo json_encode($result);
	}

}