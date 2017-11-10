<?php 
namespace back\plugin\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use back\extra\model\AdvertisingBanner;

/**
 * 广告管理类
 */
class Adbanner extends Base
{
    /**
     * 广告列表
     */
    public function index(){

        $getSearch = input('get.');
        //封装搜索条件
        $map =array();
        if(!empty($getSearch['advertising_position_id'])){
            $map['ad_position_id'] = array('eq',$getSearch['advertising_position_id']);
        }
        if(!empty($getSearch['province_id'])){
            $map['province_id'] = array('eq',$getSearch['province_id']);
        }
        
        $list = Db::name('advertising_banner')->where($map)->order('banner_id desc')->paginate(config('page_size'),false,['query'=>$getSearch]);
        //分页
        $page = $list->render();
        $nowPage = isset($getSearch['page']) ? $getSearch['page'] : 1;

        //查询省份(搜索中的)
        $this->getprovince();

        //查询广告位(搜索中的)
        $this->getAdpos();
     
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('nowPage',$nowPage);
        return $this->fetch('index');
    }

    /**
     * 添加广告
     */
    public function add(){
       
        if(request()->isPost()){
            $data = input('post.');
            $info = $data['info'];
            if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){
                $imagesRet = uploadOne('image',Config('banner_image'));
                if($imagesRet['ok'] == 0){
                    $this->error($imagesRet['error']);
                }else{
                    $info['banner_url'] = $imagesRet['images'][0];
                }
            }
            $ad_position_id = explode('-',$info['ad_position_id']);
            $info['ad_position_id'] = $ad_position_id[0];
            $info['start_time'] = strtotime($info['start_time']);
            $info['end_time'] = strtotime($info['end_time']);
            $info['admin_id'] = session('admin_id');
            $info['created_time'] = time();
            $info['updated_time'] = time();
            $res = Db::name('advertising_banner')->insert($info);
            if($res){
                $this->success('添加成功',url('/plugin/Adbanner/index'));
            }else{
                $this->error('添加失败');
            }
            
        }else{
            $this->getAdpos();
            $this->getprovince();
            $this->assign('bannerType',config('banner_type'));
            return $this->fetch('add');
        }
    }

    /**
     * 编辑广告
     */
    public function edit(){
        if(request()->isPost()){
            $data = input('post.');
            $info = $data['info'];
            // dump($info);die;
            if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){

                $imagesRet = uploadOne('image',config('banner_image'));
                if($imagesRet['ok'] == 0){
                    $this->error($imagesRet['error']);
                }else{
                    $delImg = Db::name('advertising_banner')->where('banner_id='.$info['banner_id'])->field('banner_url')->find();
                    if($delImg['banner_url']){   
                        if(file_exists('.'.config('public_path').$delImg['banner_url'])){
                            unlink('.'.config('public_path').$delImg['banner_url']);
                        }
                    }
                    $info['banner_url'] = $imagesRet['images'][0];
                }
            }
            $ad_position_id = explode('-',$info['ad_position_id']);
            $info['ad_position_id'] = $ad_position_id[0];
            $info['start_time'] = strtotime($info['start_time']);
            $info['end_time'] = strtotime($info['end_time']);
            $info['admin_id'] = session('admin_id');
            $info['updated_time'] = time();
            $res = Db::name('advertising_banner')->update($info);
            if($res){
                $this->success('编辑成功',url('/plugin/Adbanner/index','page='.$data['nowPage']));
            }else{
                $this->error('编辑失败');
            }
        }else{
            $id = input('id');
            $nowPage = input('nowPage');
            $banner = Db::name('advertising_banner')->where('banner_id',$id)->find();
            $this->getAdpos();
            $this->getprovince();
            $this->assign('bannerType',config('banner_type'));
            $this->assign('nowPage',$nowPage);
            $this->assign('banner',$banner);
            return $this->fetch('edit');
        }
    }

      /**
     * 删除/开启广告
     */
    public function delete(){
        $id = input('id');
        $token = input('token');
        $array = array('flg'=>0,'msg'=>'操作失败');

        if($token == 'del'){
            $result = Db::name('advertising_banner')->where('banner_id',$id)->update(['status'=>config('delete_status')]);
        }else if($token == 'start'){
            $result = Db::name('advertising_banner')->where('banner_id',$id)->update(['status'=>config('normal_status')]);
        }
        if($result){
            $array = array('flg'=>1,'msg'=>'操作成功');
        }
        echo json_encode($array);
    }

    //获取广告位
    public function getAdpos(){
        $adpos = Db::name('advertising_position')->field('id,position_name,advertising_width,advertising_height')->select();
        $this->assign('adpos',$adpos);
    }

    //获取省信息
    public function getprovince(){
        $where['pid'] =  array('in', array(config('china_num'), 0));
        $where['status'] = config('normal_status');
        $province = Db::name('region')->where($where)->column('id,name');
         $this->assign('province',$province);
    }
}
