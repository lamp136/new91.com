<?php 
namespace back\operate\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;

/**
 * 百度统计数据管理类
 */
class Baidudata extends Base
{
	//列表页
	public function index(){
        $getdata = input('get.');
        $pageSize = Config('page_size');
		$data =Db::name('BaiduVisitorNum')->order('created_time' ,'desc')->paginate($pageSize,false,['query' => $getdata]);
        $page = $data->render();
        $this->assign('data',$data);
        $this->assign('page',$page);
		return $this->fetch();
	}

	//添加功能
	public function add(){
		$getdata = input('post.');
		$getdata['start_time'] = strtotime($getdata['start_time']);
	 	$getdata['admin_id'] = session('admin_id');
		$getdata['end_time'] = strtotime($getdata['end_time']);
		$getdata['created_time'] = time();
		$data = Db::name('BaiduVisitorNum')->insert($getdata);
		if($data){
			$result['flag'] =1;
			$result['msg'] ='添加成功';
		}else{
			$result['flag'] =2;
			$result['msg'] ='添加失败';
		}
		echo json_encode($result);
	}



}