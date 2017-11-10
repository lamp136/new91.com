<?php
namespace back\plugin\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;

/**
 * 友情链接控制器
 */
class Friendlink extends Base
{
	/**
	 * 友情链接列表
	 */
	public function index(){
            $getSearch = input('get.');
            $friendlinkModel = Db::name('friendlink');
            $pageSize = Config('page_size');
            $list = $friendlinkModel->field('id,name,province_id,city_id,status,url')->order('created_time desc')->paginate($pageSize,false,['query'=>$getSearch]);
            $data = $friendlinkModel->field('id,name,province_id,city_id,status,url')->order('created_time desc')->select();
            $where['pid'] = array('in',"0,".config('china_num'));
            $where['status'] = config('normal_status');
            $province = $this->getRegionData($where,array('id,name'),'',true);
            //获取省名称
            foreach ($data as $key => $value) {
                $province_name='';
                $province_id = explode(',',$value['province_id']);
                foreach ($province_id as $k => $v) {
                    if(!empty($province[$v])){
                            $province_name.=$province[$v].',';
                            $data[$key]['province_id'] =rtrim($province_name,','); 
                    }
                }
            }
            $page = $list->render();
            $this->assign('list',$list);
            $this->assign('data',$data);
            $this->assign('page',$page);
            return $this->fetch();
	}

	/**
	 * 添加友情链接
	 */
	public function add(){
        $result = array('flag'=>0,'msg'=>'操作失败');
		if(Request::instance()->isPost()){
            $postData = input('post.');
            $postData['province_id'] = rtrim(implode(',',$postData['province_id']),',');
            $postData['admin_id'] = session('admin_id');
            $postData['created_time'] = time();
            unset($postData['check_all']);
            $friendlinkModel = Db::name('Friendlink');
            if($friendlinkModel->insert($postData)){
                $result = array('flag'=>1,'msg'=>'添加成功');
            }
        }else{
            $regionWhere['pid'] = array('in',"0,".config('china_num'));
            $regionWhere['status'] = config('normal_status');
            $regionData = $this->getRegionData($regionWhere,array('id,name'));
            if(!empty($regionData)){
                $result['flag'] = 1;
                $result['province'] = $regionData;
            }
        }
        echo json_encode($result);
	}

	/**
	 * 编辑友情链接
	 */
	public function edit(){
        $id = input('id');
        $result = array('flag'=>0,'msg'=>'操作失败');
		if(Request::instance()->isPost()){
            $postData = input('post.'); 
            $postData['admin_id'] = session('admin_id');
            $postData['updated_time'] = time();
            $postData['province_id'] = rtrim(implode(',',$postData['province_id']),',');
            $where['id'] = $postData['id'];
            $final = Db::name('friendlink')->where($where)->update($postData);
            if($final){
                $result['flag'] = 1;
                $result['msg'] = '修改成功';
            }
        }else{
            $provWhere['pid'] = array('in',"0,".config('china_num'));
            $provWhere['status'] = config('normal_status');
            $province = $this->getRegionData($provWhere,array('id,name'));

            $data = Db::name('friendlink')->where('id='.$id)->field('id,name,province_id,city_id,sort,status,url')->find();
            $result['flag'] = 1;
            $result['province'] = $province;
            $result['data'] = $data;
        }
        echo json_encode($result);
	}

	/**
	 * 删除友情链接
	 */
	public function delete(){
		if(Request::instance()->isPost()){
            $result = array('flag'=>0,'msg'=>'操作失败');
            $postData = input('post.');
            $friendlinkModel = Db::name('friendlink');
            if($postData['act'] == 'del'){
                $data['id'] = $postData['id'];
                $data['status'] = config('delete_status');
                if($friendlinkModel->update($data)){
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功';
                }
            }else if($postData['act'] == 'enable'){
                $data['id'] = $postData['id'];
                $data['status'] = config('normal_status');
                if($friendlinkModel->update($data)){
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功';
                }
            }
            echo json_encode($result);
        }
	}

	/**
     * 通过省份id获取城市信息
     */
    public function getCity(){
        if(Request::instance()->isPost()){
            $result = array('flag'=>0,'data'=>array());
            $province_id = I('post.province_id');
            $regoin = Db::name('region')->field('region_id,region_name')->where('pid='.$province_id.' and state='.config('normal_status'))->select();
            if(!empty($regoin)){
                $result['flag'] = 1;
                $result['data'] = $regoin;
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
     * @return array
     */
    private function getRegionData($where=array(), $fields=array(), $num=null, $bool=false){
        $regionModel = Db::name('region');
        if(!$bool){
            if($num == 1){
                $region = $regionModel->where($where)->field($fields)->find();
            }
            if(empty($num)){
                $region = $regionModel->where($where)->field($fields)->select();
            }
        }else{
            $tmp = implode(',',$fields);
            $region = $regionModel->where($where)->column($tmp);
        }
        return $region;
    }
}