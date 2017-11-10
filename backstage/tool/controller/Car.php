<?php
namespace back\tool\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
/**
 * 主要是车辆、司机管理
 */
class Car extends Base 
{
    /**
     * 司机、车辆列表页
     * 
     * @return void
     */
    public function index() {
        $status = config('normal_status');
        $this->__list($status);
        return $this->fetch('index');
    }
    /**
     * 添加司机、车辆信息
     *
     * @return void
     */
    public function add() {
        $where['level'] = config('normal_status');
        $where['status'] = config('normal_status');
        $province = Db::name('region')->where($where)->column('id,name');
        $this->assign('province',$province);
        return $this->fetch('add');
    }

    /**
     * 保存添加车辆信息
     */
    public function save(){
        $data = input('post.');
        $info = $data['info'];
 
        $province = explode('-',$info['province_id']);
        $city = explode('-',$info['city_id']);
 
        $info['province_id'] = $province[0];
        $info['province_name'] = $province[1];
        $info['city_id'] = $city[0];
        $info['city_name'] = $city[1];
        $info['store_id'] = implode(',', $data['storeId']);
        $info['status'] = config('normal_status');
        $info['created_time'] = time();
        $info['updated_time'] = time();
    
        if($_FILES['photo']['error'] == 0 && !empty($_FILES['photo']['tmp_name'])){
            $imagesRet = uploadOne('photo',Config('plugin_image'));
            if($imagesRet['ok'] == 0){
                $this->error($imagesRet['error']);
            }else{
                $info['photo'] = $imagesRet['images'][0];
            }
        }
        if($_FILES['id_card']['error'] == 0 && !empty($_FILES['id_card']['tmp_name'])){

            $imagesRet = uploadOne('id_card',Config('plugin_image'));
            if($imagesRet['ok'] == 0){
                $this->error($imagesRet['error']);
            }else{
                $info['id_card'] = $imagesRet['images'][0];
            }
        }
        if($_FILES['models_image']['error'] == 0 && !empty($_FILES['models_image']['tmp_name'])){

            $imagesRet = uploadOne('models_image',Config('plugin_image'));
            if($imagesRet['ok'] == 0){
                $this->error($imagesRet['error']);
            }else{
                $info['models_image'] = $imagesRet['images'][0];
            }
        }
        
        $result = Db::name('car_info')->insert($info);
        if($result){
            $this->success('添加成功', url('/tool/Car/index'));
        }else{
            $this->error('添加失败');
        }

    }

    /**
     * 修改车辆信息
     * 
     */
    public function edit(){
        if(Request::instance()->isPost()){
            $data = input('post.');
            $info = $data['info'];
            $province = explode('-',$info['province_id']);
            $city = explode('-',$info['city_id']);
     
            $info['province_id'] = $province[0];
            $info['province_name'] = $province[1];
            $info['city_id'] = $city[0];
            $info['city_name'] = $city[1];
            $info['store_id'] = implode(',', $data['storeId']);
            $info['updated_time'] = time();

            $image = Db::name('car_info')->where('id',$info['id'])->field('photo,id_card,models_image')->find();
            
            if($_FILES['photo']['error'] == 0 && !empty($_FILES['photo']['tmp_name'])){
                $imagesRet = uploadOne('photo',Config('plugin_image'));
                if($imagesRet['ok'] == 0){
                    $this->error($imagesRet['error']);
                }else{
                    if(file_exists('.'.config('public_path').$image['photo'])){
                        unlink('.'.config('public_path').$image['photo']);       
                    }
                    $info['photo'] = $imagesRet['images'][0];
                }
            }
            if($_FILES['id_card']['error'] == 0 && !empty($_FILES['id_card']['tmp_name'])){

                $imagesRet = uploadOne('id_card',Config('plugin_image'));
                if($imagesRet['ok'] == 0){
                    $this->error($imagesRet['error']);
                }else{
                    if(file_exists('.'.config('public_path').$image['id_card'])){
                        unlink('.'.config('public_path').$image['id_card']);       
                    }
                    $info['id_card'] = $imagesRet['images'][0];
                }
            }
            if($_FILES['models_image']['error'] == 0 && !empty($_FILES['models_image']['tmp_name'])){

                $imagesRet = uploadOne('models_image',Config('plugin_image'));
                if($imagesRet['ok'] == 0){
                    $this->error($imagesRet['error']);
                }else{
                     if(file_exists('.'.config('public_path').$image['models_image'])){
                        unlink('.'.config('public_path').$image['models_image']);       
                    }
                    $info['models_image'] = $imagesRet['images'][0];
                }
            }
            $result = Db::name('car_info')->update($info);
            if($result){
                $this->success('修改成功', url('/tool/Car/index','nowPage='.$data['nowPage']));
            }else{
                $this->error('修改失败');
            }

        }else{
            $data = Request::instance()->param();

            $car = Db::name('car_info')->where('id',$data['id'])->find();
            //获取省
            $where['level'] = config('normal_status');
            $where['status'] = config('normal_status');
            $province = Db::name('region')->where($where)->column('id,name');
            $city = Db::name('region')->where('pid',$car['province_id'])->column('id,name');

            $cemetery = Db::name('store')->whereIn('id',$car['store_id'])->column('id,name');
            
            $this->assign('nowPage',$data['nowPage']);
            $this->assign('car',$car);
            $this->assign('province',$province);
            $this->assign('city',$city);
            $this->assign('cemetery',$cemetery);
            return $this->fetch('edit');
        }
    }
    
    /**
     * 删除车辆信息
     */
    public function delete(){
        $id = input('id');
        $info['status'] = config('delete_status');
        $info['updated_time'] = time();
        $result = array('flg'=>0,'msg'=>'删除失败');
        $res = Db::name('car_info')->where('id',$id)->update($info);
        if($res){
            $result = array('flg'=>1,'msg'=>'删除成功');
        }

        echo json_encode($result);

    }

    /**
     * 和继续合作的司机以及车辆
     * 
     * @return void
     */
    public function recycle() {
        $status = config('delete_status');
        $this->__list($status);
        return $this->fetch('recycle');
    }

    //列表公共方法
    public function __list($status){
        $pageSize = config('paginate.list_rows');
        $input = input('get.');
        if($input){
            if(!empty($input['driver'])){
                $where['driver'] = array('like','%'.$input['driver'].'%');
            }
            if(!empty($input['driver_phone'])){
                $where['driver_phone'] = array('like','%'.$input['driver_phone'].'%');
            }
            if(!empty($input['province_id'])){
                $where['province_id'] = array('eq',$input['province_id']);
                 $city = Db::name('car_info')->where('province_id',$input['province_id'])->distinct(true)->field('city_id')->column('city_id,city_name');
                $this->assign('city',$city);
            }
            if(!empty($input['city_id'])){
                $where['city_id'] = array('eq',$input['city_id']);
            }
        }
        $where['status'] = $status;

        $list = Db::name('car_info')->where($where)->order('id desc')->paginate($pageSize,false,['query'=>$input]);

        $nowPage = isset($input['page']) ? $input['page'] : 1;
        $page = $list->render();

        //搜索的省市
        $province = Db::name('car_info')->distinct(true)->field('province_id')->column('province_id,province_name');
        
        $this->assign('province',$province);
        $this->assign('nowPage',$nowPage);
        $this->assign('page',$page);
        $this->assign('list',$list);
    }

    /**
     * 获取车辆表市区的列表
     */
    public function getcarcity(){
        $result = array('code' => 0,'data' => '');
        $pid = input('get.provinceId');
        if($pid){
            $cityData = Db::name('car_info')->where('province_id',$pid)->column('city_id,city_name');

            if($cityData){
                $result = array('code' => 1,'data' => $cityData);
            }
        }

        echo json_encode($result);
    }

    /**
     * 获取市区列表
     * @return [type] [description]
     */
    public function getRegion(){
        $result = array('code' => 0,'data' => '');
        $pid = input('get.provinceId');
        if($pid){
            $cityData = Db::name('region')->where('pid',$pid)->column('id,name');
            if($cityData){
                $result = array('code' => 1,'data' => $cityData);
            }
        }

        echo json_encode($result);
    }

    /**
     * 获取陵园列表
     */
    public function getcemetery(){
        $result = array('flg'=>0,'data'=>'');
        $info = input('post.');
        $where['province_id'] = $info['provinceId'];
        if(!empty($info['cityId'])){
            $where['city_id'] = $info['cityId'];
        }
        $where['category_id'] = config('category_cemetery_id');
        $where['status'] = config('normal_status');
        $list = Db::name('store')->where($where)->column('id,name');
        if($list){
            $result = array('flg'=>1,'data'=>$list);
        }

        echo json_encode($result);
    }

    /**
     * 车辆详情
     */
    public function details(){
        $id = input('id');
        $arr = array('flg'=>0,'data'=>'');
        $res = Db::name('car_info')->where('id',$id)->find();
        $cemetery = Db::name('store')->whereIn('id',$res['store_id'])->column('id,name');
        $cemeteryName = implode(',', $cemetery);
        $res['cemeteryName'] = $cemeteryName;
        if($res){
            $arr = array('flg'=>1,'data'=>$res);
        }

        echo json_encode($arr);
    }
}