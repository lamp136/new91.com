<?php
namespace back\plugin\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;

	
class History extends Base
{
	/**
	 * 列表页
	 */
	public function index(){
		$input = input('get.');
		$where = [];
		if(!empty($input['year'])){
			$where['year'] = $input['year'];
		}
		if(!empty($input['month'])){
			$where['month'] = $input['month'];
		}
		$monthData = ["01","02","03","04","05","06","07","08","09","10","11","12"];
		$pageSize = config('page_size');
		$list = Db::name('TodayHistory')->where($where)->order('year desc,month desc,day desc')->paginate($pageSize,false,['query' => $input]);
		$page = $list->render();
		$this->_listParams();
		$this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('monthData',$monthData);
		return $this->fetch();
	}

	/**
     * 添加/编辑跳转参数
     * @return void
     */
    private function _listParams(){
        $input = input('get.');
        $params = [
			'page'  => !empty($input['page']) ? $input['page'] : 1,
			'year'  => !empty($input['year']) ? $input['year'] : '',
			'month' => !empty($input['month']) ? $input['month'] : '',
        ];

        $this->assign('params',$params);
    }

	/**
	 * 增加
	 */
	public function add(){
		$result = ['flag' => 0,'msg' => '添加失败'];
		$input = input('post.');
		$time = strtotime($input['time']);
		$data = [
			'year'         => date('Y',$time),
			'month'        => $this->_rmZero(date('m',$time)),
			'day'          => $this->_rmZero(date('d',$time)),
			'title'        => $input['title'],
			'keywords'     => $input['keywords'],
			'content'      => $input['content'],
			'created_time' => date('Y-m-d H:i:s')
		];
		$info = Db::name('TodayHistory')->insert($data);
		if($info){
			$result['flag'] = 1;
			$result['msg'] = '添加成功';
		}
		echo json_encode($result);
	}

	/**
	 * 去除月或日的0
	 * @param  number|string $num 月/日
	 * @return number
	 */
	private function _rmZero($num){
		return $num < 10 ? str_replace(0, '', $num) : $num;
	}

	
	/**
	 * 删除
	 */
	public function delete(){
		$result = array('flag'=>0,'msg'=>'删除失败');
		$id = input('id');
		$info = Db::name('TodayHistory')->where('id='.$id)->delete();
		if($info){
			$result['flag'] = 1;
			$result['msg'] = '删除成功';
		}
		echo json_encode($result);
	}

	/**
	 * 查看内容
	 */
	public function lookcontent(){
		$input = input('post.');
		$where['id'] = $input['id'];
		if($input['type']== 'content'){
			$field = 'content';
		}else{
			$field = 'keywords';
		}
		$data = Db::name('TodayHistory')->where($where)->field($field)->find();
		if($data){
			$result['flag'] = 1;
			$result['data'] = $data;
		}
		echo json_encode($result);
	}


	/**
	 * 编辑
	 */
	public function edit(){
		$id = input('id');
		if(request()->isPost()){
			$result = ['code' => 0,'msg' => '修改失败'];
			$input = input('post.');
			$updateData = $input['info'];
			$updateData['updated_time'] = date('Y-m-d H:i:s');
			$final = Db::name('TodayHistory')->data($updateData)->update();
			if($final){
				$result = ['code' => 1,'msg' => '修改成功'];
			}
			echo json_encode($result);
		}else{
			$where['id'] = $id;
			$info = Db::name('TodayHistory')->where($where)->find();
			$this->_listParams();
			$this->assign('news',$info);
			return $this->fetch('edit');
		}
	}
}