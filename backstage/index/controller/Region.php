<?php
namespace back\index\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;

/**
 * 地区列表
 */
class Region extends Base {

    /**
     * 地区列表
     */
    public function index() {
        $regionModel = Db::name('region');
        $regionData = $regionModel->order('id asc,sort desc')->select();
        $list = $this->RegionTree($regionData, 0);
        $this->assign('region', $list);
        return $this->fetch();
    }


    /**
     * 地区添加
     */
     public function add() {
        if (Request::instance()->isPost()) {
             $result = array('flag' => 0, 'msg' => '操作失败');
             $postData = input('post.');
             $regionModel = Db::name('region');
             if ($postData['pid'] == 0) {
                 $postData['level'] = 0;
             } else {
                 $topData = $regionModel->where('id=' . $postData['pid'])->field('level')->find();
                 if (!empty($topData)) {
                     $postData['level'] = $topData['level'] + 1;
                 } else {
                     $postData['level'] = 0;
                 }
             }
             if ($regionModel->insert($postData)) {
                 $result['flag'] = 1;
                 $result['msg'] = '添加成功';
             }
             echo json_encode($result);
         }
     }

    /**
     * 编辑地区
     */
     public function edit() {
         if (Request::instance()->isPost()) {
             $postData = input('post.');
             //获取每个类别下的所有子id
             $regionModel = Db::name('region');
             $regionData = $regionModel->order('sort desc,id asc')->select();
             $childRegion = $this->getChildRegionIs($regionData, $postData['region_id']);
             $data = Db::name('region')->where('id=' . $postData['region_id'])->find();
             if (!empty($data)) {
                 $pid = $postData['pid'];
                 if (!in_array($pid, $childRegion)) {
                     if($data['pid']!=$postData['pid']){
                         $topData = $regionModel->where('id=' . $postData['pid'])->field('level')->find();
                         if (!empty($topData)) {
                             $postData['level'] = $topData['level'] + 1;
                         } else {
                             $postData['level'] = 0;
                         }
                     }
                     $data['update_time'] = time();
                     $data['pid'] = $pid;
                     $final  = Db::name('region')->where('id='.$postData['region_id'])->update($data);
                     if ($final) {
                         $result['flag'] = 1;
                         $result['msg'] = '操作成功';
                     }else{
                         $result['flag'] = 0;
                         $result['msg'] = '操作失败';
                     }
                 } else {
                     $result['flag'] = 0;
                     $result['msg'] = '不能移动到其子类下';
                 }
             } else {
                 $result['flag'] = 0;
                 $result['msg'] = '没有找到该数据';
             }
             echo json_encode($result);
         } else {
             $region_id = input('region_id');
             $data = Db::name('region')->where('id='.$region_id)->find();
             if (!empty($data)) {
                 $result['flag'] = 1;
                 $result['data'] = $data;
             } else {
                 $result['flag'] = 0;
                 $result['data'] = '';
             }
             echo json_encode($result);
         }
     }

    /**
     * 删除启用地区
     */
     public function delete() {
         if(Request::instance()->isPost()){
             $result = array('flag'=>0,'msg'=>'操作失败');
             $postData = input('post.');
             $regionModel =  Db::name('region');
             $data['id'] = $postData['region_id'];
             $data['update_time'] = time();
             if($postData['act'] == 'del'){
                 $data['status'] = config('delete_status');
                 if(Db::name('region')->where('id='.$postData['region_id'])->update($data)){
                     $result['flag'] = 1;
                     $result['msg'] = '操作成功';
                 }
             }else if($postData['act'] == 'enable'){
                 $data['status'] = config('normal_status');
                 if(Db::name('region')->where('id='.$postData['region_id'])->update($data)){
                     $result['flag'] = 1;
                     $result['msg'] = '操作成功';
                 }
             }
             echo json_encode($result);
         }
     }


    /**
     * 将地区数组重组
     *
     * @param   array $data
     * @param   int   $pid
     * @return  array
     */
    public function RegionTree($data,$pid=0){
        static $result = array();
        foreach ($data as $key => $v){
            if($v['pid']==$pid){
                $result[] = $v;
                unset($data[$key]);
                $this->RegionTree($data, $v['id']);
            }
        }
        return $result;
    }

       /**
     * 获取每个地区下所有的子地区
     *
     * @param array $data
     * @param int $region_id
     * @return array
     */
    public function getChildRegionIs($data,$region_id){
        static $result = array();
        foreach($data  as $key => $v){
            if($v['pid']==$region_id){
                $result[] = $v['id'];
                unset($data[$key]);
                $this->getChildRegionIs($data, $v['id']);
            }
        }
        return $result;
    }

}
