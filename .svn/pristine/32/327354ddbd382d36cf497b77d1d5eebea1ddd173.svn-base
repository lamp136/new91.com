<?php 
namespace back\plugin\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
/**
 * 广告位类
 */
class Adposition extends Base
{
	/**
	 * 广告位列表
	 */
	public function index(){
		$get = input('get.');
		$list = Db::name('advertising_position')->order('id desc')->paginate(config('page_size'),false,['query'=>$get]);
		$page = $list->render();
		$this->assign('page',$page);
		$this->assign('list',$list);
		return $this->fetch('index');
	}

	/**
	 * 添加广告
	 */
	public function add(){
		$array = array('flag'=>0,'msg'=>'操作失败');
		$data = input('post.');
		$info = $data['info'];
		$info['admin_id'] = session('admin_id');
		$info['created_time'] = date('Y-m-d H:i:s',time());
		$res = Db::name('advertising_position')->insert($info);
		if($res){
			$array = array('flag'=>1,'msg'=>'添加成功');
		}

		echo json_encode($array);
	}

	/**
	 * 编辑广告位
	 */
	public function edit(){
		if(request()->isPost()){
			$result = array('flag'=>0,'msg'=>'修改失败');
			$data = input('post.');
			$info = $data['info'];
			$res = Db::name('advertising_position')->update($info);
			if($res){
				$result = array('flag'=>1,'msg'=>'修改成功');
			}else if($res==0){
				$result = array('flag'=>1,'msg'=>'数据无改动');
			}
			echo json_encode($result);
		}else{
			$result = array('flag'=>0,'data'=>array());
			$id = input('id');
            if(!empty($id)){
                $data = Db::name('advertising_position')->where('id',$id)->field('id,position_name,advertising_width,advertising_height,position_description')->find();
                if(!empty($data)){
                    $result['flag'] = 1;
                    $result['data'] = $data;
                }
            }
            echo json_encode($result);
		}
	}
}