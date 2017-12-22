<?php
namespace back\tool\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use back\extra\model\CarDriver;
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
        $this->_list($status);
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
        $result = ['code' => 0];
        $input = input('post.');
        $driver = $input['driver'];
        $car = $input['car'];
        $province = explode('-',$driver['province_id']);
        $city = explode('-',$driver['city_id']);
        $driverData = [
            'name'                 => $driver['name'],
            'password'             => encryptHome($driver['password']),
            'photo'                => $this->_files('photo'),
            'id_card'              => $this->_files('id_card'),
            'driving_licence'      => $this->_files('driving_licence'),
            'mobile'               => $driver['mobile'],
            'id_number'            => $driver['id_number'],
            'province_id'          => $province[0],
            'province_name'        => $province[1],
            'city_id'              => $city[0],
            'city_name'            => $city[1],
            'address'              => $driver['address'],
            'store_ids'            => $driver['store_ids'],
            'status'               => $driver['status'],
            'created_licence_time' => strtotime($driver['created_licence_time']),
            'created_time'         => time(),
            'updated_time'         => time(),
        ];
        $addDriver = Db::name('CarDriver')->insertGetId($driverData);
        if($addDriver){
            $carData = [
                'driver_id'              => $addDriver,
                'vehicle_travel_license' => $this->_files('vehicle_travel_license'),
                'models'                 => $car['models'],
                'models_image'           => $this->_files('models_image'),
                'plate_number'           => $car['plate_number'],
                'vin'                    => $car['vin'],
                'engine_number'          => $car['engine_number'],
                'car_type'               => $car['car_type'],
                'status'                 => config('normal_status'),
                'remarks'                => $car['remarks'],
                'created_time'           => time(),
                'updated_time'           => time()
            ];
            $addCar = Db::name('CarInfo')->data($carData)->insert();
            if($addCar){
                $result = ['code' => 1,'msg' => '添加成功'];
            }
        }
        echo json_encode($result);
    }

    /**
     * 上传图片方法
     * @param  string $imgField 图片保存字段
     * @return string
     */
    private function _files($imgField,$oldImg = ''){
        $image = '';
        if($_FILES[$imgField]['error'] == 0 && !empty($_FILES[$imgField]['tmp_name'])){
            $image = '';
            $imagesRet = uploadOne($imgField,config('plugin_image'));
            if($imagesRet['ok'] == 0){
                $this->error($imagesRet['error']);
            }else{
                if(!empty($oldImg) && is_file('.'.config('public_path').$oldImg)){
                    unlink('.'.config('public_path').$oldImg);
                }
                $image = $imagesRet['images'][0];
            }
        }

        return $image;
    }

    /**
     * 修改车辆信息
     * 
     */
    public function edit(){
        $result = ['code' => 0];
        if(request()->isPost()){
            $input = input('post.');
            $province = explode('-',$input['driver_province_id']);
            $city = explode('-', $input['driver_city_id']);
            
            $update['name']          = $input['name'];
            $update['mobile']        = $input['mobile'];
            $update['id_number']     = $input['id_number'];
            $update['province_id']   = $province[0];
            $update['province_name'] = $province[1];
            $update['city_id']       = $city[0];
            $update['city_name']     = $city[1];
            $update['address']       = $input['address'];
            $update['store_ids']     = $input['store_ids'];
            $update['updated_time']  = time();
            $oldImg = $input['old'];
            $photo = $this->_files('photo',$oldImg['photo']);
            if(!empty($photo)){
                $update['photo'] = $photo;
            }
            $idCard = $this->_files('id_card',$oldImg['id_card']);
            if(!empty($idCard)){
                $update['id_card'] = $idCard;
            }
            $drivingLicence = $this->_files('driving_licence',$oldImg['driving_licence']);
            if(!empty($drivingLicence)){
                $update['driving_licence'] = $drivingLicence;
            }
            $updateRet = Db::name('CarDriver')->where('id',$input['id'])->update($update);
            if($updateRet){
                $result = ['code' => 1,'msg' => '修改成功'];
            }
        }else{
            $id = input('get.id');
            if($id){
                $driverInfo = Db::name('CarDriver')->where('id',$id)->field('id,name,password,id_card,driving_licence,id_number,photo,mobile,province_id,province_name,city_id,city_name,address,status,store_ids')->find();
                $city = $stores = [];
                if(!empty($driverInfo)){
                    if($driverInfo['province_id'] != 0){
                        if(!empty($driverInfo['store_ids'])){
                            $stores = Db::name('store')->whereIn('id',$driverInfo['store_ids'])->column('id,name');
                        }
                        $city = Db::name('region')->where('pid',$driverInfo['province_id'])->column('id,name');
                    }
                    $result = ['code' => 1,'data' => $driverInfo,'city' => $city,'stores' => $stores];
                }
            }
        }
        echo json_encode($result);
    }
    
    /**
     * 删除车辆信息
     */
    public function delete(){
        $id = input('id');
        $info['status'] = config('delete_status');
        $info['updated_time'] = time();
        $result = ['flg'=>0,'msg'=>'删除失败'];
        $res = Db::name('CarDriver')->where('id',$id)->update($info);
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
        $this->_list($status);
        return $this->fetch('index');
    }

    //列表公共方法
    private function _list($status){
        $pageSize = config('paginate.list_rows');
        $input = input('get.');
        if(!empty($input['name'])){
            $where['name'] = ['like','%'.$input['name'].'%'];
        }
        if(!empty($input['mobile'])){
            $where['mobile'] = ['like','%'.$input['mobile'].'%'];
        }
        if(!empty($input['province_id'])){
            $where['province_id'] = $input['province_id'];
        }
        if(!empty($input['city_id'])){
            $where['city_id'] = $input['city_id'];
        }
        $where['status'] = $status;
        $list = CarDriver::with('cars')->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $input]);
        $driverRegion = Db::name('CarDriver')->where(['province_name' => ['neq',''],'city_name' => ['neq','']])->field('province_id,province_name,city_id,city_name')->select();
        $province = $city = [];
        if($driverRegion){
            foreach ($driverRegion as $key => $val) {
                $province[$val['province_id']] = $val['province_name'];
                if(!empty($input['province_id'])){
                    if($val['province_id'] == $input['province_id']){
                        $city[$val['city_id']] = $val['city_name'];
                    }
                }
            }
            $this->assign('city',$city);
        }
        $where['level'] = config('normal_status');
        $where['status'] = config('normal_status');
        $province = Db::name('region')->where($where)->column('id,name');
        $nowPage = isset($input['page']) ? $input['page'] : 1;
        $page = $list->render();
        $this->assign('allProvince',$province);
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
        $result = ['flg' => 0];
        $driver = CarDriver::with('cars')->where('id',$id)->find();
        if(!empty($driver['store_ids'])){
            $stores = Db::name('store')->whereIn('id',$driver['store_ids'])->column('id,name');
            $driver['stores'] = implode(',', $stores);
        }
        if($driver){
            $result = ['flg' => 1,'data' => $driver];
        }

        echo json_encode($result);
    }
}