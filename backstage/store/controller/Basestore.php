<?php
namespace back\store\controller;
use back\extra\controller\Base;
use think\Session;
use think\Request;
use think\Db;
use think\Cache;
use back\extra\model\Store;
use back\extra\model\StoreRecommend;

/**
 * 商家控制器
 * 包含：陵园、殡仪馆、殡葬礼仪、相册、联系人、名人、园区、墓地
 *
 * @author WangQiang <wangqiang@huigeyuan.com>
 */
class Basestore extends Base
{
    /**
     * 陵园列表
     */
    public function cemetery(){
        $this->_list(config('category_cemetery_id'));
        return $this->fetch();
    }

    /**
     * 殡仪馆列表
     */
    public function funeral(){
        $this->_list(config('category_funeral_id'));
        return $this->fetch();
    }

    /**
     * 礼仪公司
     */
    public function etiquette(){
        $this->_list(config('category_etiquette_id'));
        return $this->fetch();
    }

    /**
     * 公共列表方法
     */
    private function _list($cate){
        $input = input('get.');
        // $regionWhere['status'] = config('normal_status');
        // $regionWhere['level'] = config('normal_status');
        $regions = Db::name('region')->where(['status' => config('normal_status')])->field('id,name,pid')->select();
        $province = [];
        $city = [];
        if($regions){
            foreach ($regions as $val) {
                if($val['pid'] == config('china_num')){
                    // 省份
                    $province[] = $val;
                }else if(isset($input['province']) && !empty($input['province'])){
                    if($val['pid'] == $input['province']){
                        // 市区
                        $city[] = $val;
                    }
                }
            }
        }
        $where['category_id'] = $cate;
        if($input){
            if(!empty($input['store_name'])){
                $where['name'] = ['like','%'.$input['store_name'].'%'];
            }
            if(!empty($input['province'])){
                $where['province_id'] = $input['province'];
                if(!empty($input['city'])){
                    $where['city_id'] = $input['city'];
                }
            }
            if(!empty($input['member_status']) && $cate == config('category_cemetery_id')){
                $where['member_status'] = $input['member_status'];
                if($input['member_status'] == config('store_member')){
                   $where['member_status'] = ['in',[config('store_member'),config('store_member_v')]];
                }
            }
        }
        $storeList = Store::with(['province','city'])->where($where)->field('id,name,store_sn,province_id,city_id,category_id,member_status,created_time,status,have_car')->order('created_time desc')->paginate(config('page_size'),false,['query' => $input]);
        $page = $storeList->render();
        $jump = [
            'store_name'    => isset($input['store_name']) ? $input['store_name'] : '',
            'nowPage'       => isset($input['page']) ? $input['page'] : 1,
            'province'      => isset($input['province']) ? $input['province'] : '',
            'city'          => isset($input['city']) ? $input['city'] : '',
            'member_status' => isset($input['member_status']) ? $input['member_status'] : ''
        ];
        $this->assign('jump',$jump);
        $this->assign('input',$input);
        $this->assign('haveCar',config('have_car'));
        $this->assign('page',$page);
        $this->assign('store',$storeList);
        $this->assign('storeMember',getStoreMember());
        $this->assign('province',$province);
        $this->assign('city',$city);
    }

    /**
     * 添加陵园
     */
    public function addcemetery(){
        if(request()->isPost()){
            $return = $this->_returnParam();
            $result = $this->_add(config('category_cemetery_id'));
            if($result){
                $this->success('添加成功',url('/store/Basestore/cemetery','store_name='.$return['store_name'].'&page='.$return['nowPage'].'&province='.$return['province'].'&city='.$return['city'].'&member_status='.$return['member_status']));
            }else{
                $this->error('添加失败');
            }
        }else{
            $where['level'] = config('normal_status');
            $where['status'] = config('normal_status');
            $province = Db::name('region')->where($where)->column('id,name');
            $this->assign('province',$province);
            $this->_jumpParam();
            $this->_publicData();
            return $this->fetch();
        }
    }

    /**
     * 添加殡仪馆
     */
    public function addfuneral(){
        if(request()->isPost()){
            $return = $this->_returnParam();
            $result = $this->_add(config('category_funeral_id'));
            if($result){
                $this->success('添加成功',url('/store/Basestore/funeral','store_name='.$return['store_name'].'&page='.$return['nowPage'].'&province='.$return['province'].'&city='.$return['city']));
            }else{
                $this->error('添加失败');
            }
        }else{
            $where['level'] = config('normal_status');
            $where['status'] = config('normal_status');
            $province = Db::name('region')->where($where)->column('id,name');
            $this->assign('province',$province);
            $this->_jumpParam();
            return $this->fetch();
        }
    }

    /**
     * 添加礼仪公司
     */
    public function addetiquette(){
        if(request()->isPost()){
            $result = ['code' => 0,'msg' => '添加失败'];
            $ret = $this->_add(config('category_etiquette_id'));
            if($ret){
                $result = ['code' => 1,'msg' => '添加成功'];
            }
            echo json_encode($result);
        }else{
            $where['level'] = config('normal_status');
            $where['status'] = config('normal_status');
            $province = Db::name('region')->where($where)->column('id,name');
            $this->assign('province',$province);
            $this->_jumpParam();
            return $this->fetch();
        }
    }

    /**
     * 商家添加公共方法
     */
    private function _add($category){
        $input = input('post.');
        $cityId = $input['cityId'];
        $string = '';
        foreach($cityId as $val){
            $string .= $val.',';
        }
        $data = $input['info'];
        $data['service_city'] = $string;
        $contact = $input['contact'];
        if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){
            $thumb = config('store_image_size.thumb');
            if($category == config('category_etiquette_id')){
                $thumb = config('etiquette_image_size.thumb');
            }
            $result = uploadOne('image',config('store_image'),$thumb,true);
            if($result['ok'] == 1){
                $data['image'] = $result['images'][0];
                $data['thumb_image'] = $result['images'][1];
            }
        }
        $data['category_id'] = $category;
        $data['admin_id'] = Session::get('admin_id');
        $data['created_time'] = $data['updated_time'] = time();
        $data['store_sn'] = getStoreSn($category);
        if($category == config('category_cemetery_id')){
            $data['min_price'] = (float)$data['min_price'];
            $data['max_price'] = (float)$data['max_price'];
        }
        $result = Db::name('store')->insertGetId($data);
        if($result){
            $contact['status'] = config('normal_status');
            $contact['store_id'] = $result;
            $contact['created_time'] = $contact['updated_time'] = time();
            Db::name('StoreContact')->data($contact)->insert();
        }

        return $result;
    }

    /**
     * 编辑陵园
     */
    public function editcemetery(){
        if(request()->isPost()){
            $return = $this->_returnParam();
            $result = $this->_edit();
            if($result){
                $this->success('更新成功',url('/store/Basestore/cemetery','store_name='.$return['store_name'].'&page='.$return['nowPage'].'&province='.$return['province'].'&city='.$return['city'].'&member_status='.$return['member_status']));
            }else{
                $this->error('更新失败');
            }
        }else{
            $id = input('id');
            if($id){
                $this->_editData($id);
                $this->_publicData();
            }
            return $this->fetch();
        }
    }

    /**
     * 编辑殡仪馆
     */
    public function editfuneral(){
        if(request()->isPost()){
            $return = $this->_returnParam();
            $result = $this->_edit();
            if($result){
                $this->success('更新成功',url('/store/Basestore/funeral','store_name='.$return['store_name'].'&page='.$return['nowPage'].'&province='.$return['province'].'&city='.$return['city']));
            }else{
                $this->error('更新失败');
            }
        }else{
            $id = input('id');
            if($id){
                $this->_editData($id);
            }
            return $this->fetch();
        }
    }

    /**
     * 编辑礼仪公司
     */
    public function editetiquette(){
        if(request()->isPost()){
            $result = ['code' => 0,'msg' => '更新失败'];
            $ret = $this->_edit();
            if($ret){
                $result = ['code' => 1,'msg' => '更新成功'];
            }
            echo json_encode($result);
        }else{
            $id = input('id');
            if($id){
                $this->_editData($id);
            }
            return $this->fetch();
        }
    }

    /**
     * 获取编辑数据
     * @param  int $id 主键id
     * @return void
     */
    private function _editData($id){
        $data = Db::name('store')->where('id',$id)->field('id,profiles_id,name,category_id,category_pid,image,thumb_image,seo_title,seo_keywords,seo_description,content,label,advantage,disadvantage,province_id,city_id,address,status,review_price,review_traffic,review_ambient,review_service,longitude,latitude,member_status,feature,min_price,max_price,business_type,sort,distance,have_car,pick_up_address,level,remarks,other_project,urban_office,official_website,phone,service_time,service_city')->find();
        $where['status'] = config('normal_status');
        $allcity = Db::name('region')->where($where)->column('id,name');
        $cityarray = array_filter(explode(',',$data['service_city']));
        $Tmp = array();
        foreach($cityarray as $val){
            $Tmp[$val] = $allcity[$val];
        }
        $data['service_city'] = $Tmp;
        
        $where['level'] = config('normal_status');
        $province = Db::name('region')->where($where)->column('id,name');
        if($data['province_id']){
            $files['province_id'] = $data['province_id'];
            $city = Db::name('region')->where('pid',$data['province_id'])->column('id,name');
            $this->assign('city',$city);
            if($data['category_id'] == config('category_cemetery_id')){
                if($data['city_id']){
                    $files['city_id'] = $data['city_id'];
                }
                $profilesList = Db::name('StoreProfiles')->where($files)->field('id,profile_name')->select();
                $this->assign('profilesList',$profilesList);
            }
        }
        $this->assign('province',$province);
        $this->assign('data',$data);
        $this->_jumpParam();
    }

    /**
     * 公共编辑方法
     */
    private function _edit(){
        $result = true;
        $input = input('post.');
        $data = $input['info'];
        if(!empty($input['cityId'])){
            $cityId = $input['cityId'];
            $string = '';
            foreach($cityId as $val){
                $string .= $val.',';
            }
            $data['service_city'] = $string;
        }
        $data['admin_id'] = Session::get('admin_id');
        $data['updated_time'] = time();
        if($input['category'] == config('category_cemetery_id')){
            $data['min_price'] = (float)$data['min_price'];
            $data['max_price'] = (float)$data['max_price'];
        }
        Db::startTrans();
        if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){
            $result = uploadOne('image',config('store_image'),config('store_image_size.thumb'),true);
            if($result['ok'] == 1){
                $delImg = Db::name('store')->where('id',$data['id'])->field('image,thumb_image')->find();
                if(!empty($delImg['image']) && is_file('.'.config('public_path').$delImg['image'])){
                    unlink('.'.config('public_path').$delImg['image']);
                }
                if(!empty($delImg['thumb_image']) && is_file('.'.config('public_path').$delImg['thumb_image'])){
                    unlink('.'.config('public_path').$delImg['thumb_image']);
                }
                $data['image'] = $result['images'][0];
                $data['thumb_image'] = $result['images'][1];

                $ret = Db::name('store')->data($data)->update();
            }else{
                Db::rollback();
                $result = false;
            }
        }else{
            $ret = Db::name('store')->data($data)->update();
        }
        
        if($ret){
            Db::commit();
            $result = true;
            if($input['category'] == config('category_cemetery_id') && !empty($data['profiles_id'])){
                /**
                 * [$profile 修改合同数据] 
                 */
                $profile = [
                    'id'                => $data['profiles_id'],
                    'member_status'     => $data['member_status'],
                    'show_store_name'   => $data['name'],
                    'min_price'         => $data['min_price'],
                    'max_price'         => $data['max_price'],
                    'remarks'           => $data['remarks'],
                    'category_group_id' => $data['category_pid'],
                    'updated_time'      => time()
                ];
                $result = Db::name('StoreProfiles')->data($profile)->update();
                if(!$result){
                    $result = false;
                    Db::rollback();
                }else{
                    Db::commit();
                }
            }
        }

        return $result;
    }

    /**
     * 用于添加/修改带参数跳转
     */
    private function _jumpParam(){
        $input = input('get.');
        $jump = [
            'store_name'    => isset($input['store_name']) ? $input['store_name'] : '',
            'nowPage'       => isset($input['nowPage']) ? $input['nowPage'] : 1,
            'province'      => isset($input['province']) ? $input['province'] : '',
            'city'          => isset($input['city']) ? $input['city'] : '',
            'member_status' => isset($input['member_status']) ? $input['member_status'] : '' 
        ];
        $this->assign('jump',$jump);
    }

    /**
     * 提交后跳转列表页参数
     * @return array
     */
    private function _returnParam(){
        $input = input('post.');

        $data['store_name']    = $input['sname'];
        $data['nowPage']       = $input['spage'];
        $data['province']      = $input['sprovince'];
        $data['city']          = $input['scity'];
        if(isset($input['smember_status'])){
            $data['member_status'] = $input['smember_status'];
        }
        
        return $data;
    }

    /**
     * 获取市区列表
     * @return json
     */
    public function getRegion(){
        $result = array('code' => 0,'data' => '');
        $pid = input('get.id');
        if($pid){
            $cityData = Db::name('region')->where('pid',$pid)->column('id,name');
            if($cityData){
                $result = array('code' => 1,'data' => $cityData);
            }
        }

        echo json_encode($result);
    }

    /**
     * 公共数据
     * @return void
     */
    private function _publicData(){
        $group = Db::name('category')->where('pid',config('category_group_id'))->field('id,pid,name')->select();
        $storeMember = getStoreMember();
        $this->assign('group',$group);
        $this->assign('storeMember',$storeMember);
    }

    /**
     * 获取合同
     */
    public function getProfiles(){
        $input = input('get.');
        $result = array('code' => 0,'profilesData' => '');
        $where = array();
        if(!empty($input['provinceId'])){
            $where['status'] = config('normal_status');
            $where['province_id'] = $input['provinceId'];
            if(!empty($input['cityId'])){
                $where['city_id'] = $input['cityId'];
            }
            $storeProfilesList = Db::name('StoreProfiles')->where($where)->field('id,profile_name')->select();
            if($storeProfilesList){
                $result = array('code' => 1,'profilesData' => $storeProfilesList);
            }
        }

        echo json_encode($result);
    }

    /**
     * 商家 禁用/启用
     */
    public function delete(){
        $id = input('get.id');
        $result = array('code' => 0,'msg' => '操作失败');
        if($id){
            $status = input('get.status');
            $ret = Db::name('store')->where('id',$id)->setField('status',$status);
            if($ret){
                $result = array('code' => 1,'msg' => '操作成功');
            }
        }
        echo json_encode($result);
    }

    /**
     * 检测商家名字重复
     * @return bool
     */
    public function isRepeatName(){
        $result = ['code' => 0];
        $input = input('get.');
        $where['name'] = $input['name'];
        $where['category_id'] = $input['category'];
        if(!empty($input['id'])){
            $where['id'] = ['neq',$input['id']];
        }
        $length = Db::name('store')->where($where)->count();
        if($length > 0){
            $result = ['code' => 1];
        }

        echo json_encode($result);
    }

    /**
     * 陵园相册列表页
     */
    public function storealbum(){
        $id = input('id');
        if($id){
            $category_id = input('category_id');
            $store_name = input('store_name');
            $input= input('get.');
            $store = Db::name('store')->where('id',$id)->field('store_sn')->find();
            $where['store_id'] = $id;
            $pageSize = Config('page_size');
            $list = Db::name('store_images')->where($where)->order('id desc')->paginate($pageSize,false,['query'=>$input]);
            $page = $list->render();
            $tombZone = Db::name('TombZone')->where('store_id',$id)->order('created_time desc')->column('id,zone_name');
            $this->assign([
                'page'        => $page,
                'category_id' => $category_id,
                'store_name'  => $store_name,
                'list'        => $list,
                'storeId'     => $id,
                'storeSn'     => $store['store_sn'],
                'tombZone'    => $tombZone
            ]);
        }
        return $this->fetch('storealbum');
    }

    /**
     * 添加相册
     */
    public function saveAlbum(){
        $data = input('post.');
        $info = $data['info'];
        $array = array('flg'=>0,'msg'=>'添加失败');

        if(in_array('',$_FILES['image']['tmp_name'])){
            $array = array('flg'=>0,'msg'=>'请选择一张图片');
            echo json_encode($array);die;
        }

        $imagesRet = upload('image',Config('store_image'),array(array(225,220,6)));
        if($imagesRet['ok'] == 0){
            $this->error($imagesRet['error']);
        }else{
            foreach($imagesRet['images'] as $k=>$v){
                $info['image'] = $v;
                foreach($imagesRet['thumb'][$k] as $key=>$value){
                    $info['thumb_image'] = $value;
                }
                $result = Db::name('store_images')->insert($info);
                if($result){
                    $array = array('flg'=>1,'msg'=>'添加成功');
                }
            }
        }
        
        echo json_encode($array);
    }

    /**
     * 修改相册
     */
    public function editImage(){
        $imageId = input('id');
        if($imageId){
            $data = Db::name('store_images')->where('id',$imageId)->find();
            $array = array('flg'=>1,'msg'=>$data);
        }else{
            $array = array('flg'=>0);
        }
        echo json_encode($array);
    }

    /**
     * 保存修改
     */
    public function updateImage(){
        $data = input('post.');
        $id = $data['image_id'];
        $info = $data['info'];
        $array = array('flg'=>0,'msg'=>'更新失败');
       
        if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){
            $imagesRet = uploadOne('image',Config('store_image'),array(array(225,220,6)));
            if($imagesRet['ok'] == 0){
                $this->error($imagesRet['error']);
            }else{
                $delImg = Db::name('store_images')->where('id',$id)->field('image,thumb_image')->find();
                $info['image'] = $imagesRet['images'][0];
                $info['thumb_image'] = $imagesRet['images'][1];
                $result = db('store_images')->where('id',$id)->update($info);
                if($result && file_exists('.'.config('public_path').$delImg['image'])){
                    unlink('.'.config('public_path').$delImg['image']);
                    unlink('.'.config('public_path').$delImg['thumb_image']);
                }
            }
        }else{
            $result = db('store_images')->where('id',$id)->update($info);
        }
        if($result){
            $array = array('flg'=>1,'msg'=>'更新成功');
        }

        echo json_encode($array);
    }

   
    /**
     * 删除商家相册
     * 
     */
    public function delstart(){
        $id = input('id');
        $array = array('flg'=>0,'msg'=>'操作失败');

        $imgurl = db('store_images')->whereIn('id',$id)->field('thumb_image,image')->select();
        $result = db('store_images')->whereIn('id',$id)->delete();
        if($result){
            foreach ($imgurl as $key => $value) {
                if(file_exists('.'.config('public_path').$value['image'])){
                    unlink('.'.config('public_path').$value['image']);
                    unlink('.'.config('public_path').$value['thumb_image']);
                }
            }
            $array = array('flg'=>1,'msg'=>'操作成功');
        }
        echo json_encode($array);
    }

    /**
     * 商家联系人列表
     */
    public function storeContact(){
        $id = input('id');
        if($id){
            $category_id = input('category_id');
            $store_name = input('store_name');
            $list = Db::name('store_contact')->where('store_id',$id)->order('id desc')->select();
            $store_sn = Db::name('store')->where('id',$id)->field('store_sn')->find();
            
            $this->assign('store_sn',$store_sn['store_sn']);
            $this->assign('category_id',$category_id);
            $this->assign('store_name',$store_name);
            $this->assign('list',$list);
            $this->assign('storeId',$id);
            return $this->fetch('storecontact');
        }
    }

    /**
     * 添加联系人
     */
    public function saveContact(){

        $data = input("post.");
        $info = $data['info'];

        if (!empty($info['mobile'])) {
            $where['mobile'] = $info['mobile'];
        }
        if (!empty($info['tel'])) {
            $where['tel'] = $info['tel'];
        }
        if (!empty($info['store_sn'])) {
            $where['store_sn'] = $info['store_sn'];
        }
        $contactInfo = Db::name('store_contact')->where($where)->find();
        $info['admin_id'] = Session::get('admin_id');
        $info['status'] = Config('normal_status');
        $info['created_time'] = time();
        $info['updated_time'] = time();
        if(!empty($contactInfo)){
            $result['flag'] = 0;
            $result['msg'] = '同一个陵园已经有相同的手机号';
        }else{
            $res = Db::name('store_contact')->insert($info);
            if($res){
                $result['flag'] = 1;
                $result['msg'] = '添加成功';
            }else{
                $result['flag'] = 0;
                $result['msg'] = '添加失败';
            }
        }

        echo json_encode($result);
    }

    /**
     * 编辑商家联系人
     */
    public function editContact(){
        $id = input('id');
        if($id){
            $data = Db::name('store_contact')->where('id',$id)->find();
            $array = array('flg'=>1,'msg'=>$data);
        }else{
            $array = array('flg'=>0);
        }
        echo json_encode($array);
    }

    /**
     * 保存编辑商家联系人
     */
    public function updateContact(){
        $data = input('post.');
        $id = $data['contact_id'];

        if($id){ 
            $info = $data['info'];
            $array = array('flg'=>0,'msg'=>'更新失败');

            $result = db('store_contact')->where('id',$id)->update($info);
            if($result){
                $array = array('flg'=>1,'msg'=>'更新成功');
            }
        }
        echo json_encode($array);
    }

    /**
     * 删除商家联系人
     */
    public function delContact(){
        $id = input('id');
        $token = input('token');
        $array = array('flg'=>0,'msg'=>'操作失败');

        if($token == 'del'){
            $result = db('store_contact')->where('id',$id)->update(['status'=>Config('delete_status')]);
        }else if($token == 'start'){
            $result = db('store_contact')->where('id',$id)->update(['status'=>Config('normal_status')]);
        }
        if($result){
            $array = array('flg'=>1,'msg'=>'操作成功');
        }
        echo json_encode($array);
    }

    /**
     * 名人墓地列表
     */
    public function celebrityList(){
        $id = input('id');
        if($id){
            $category_id = input('category_id');
            $store_name = input('store_name');
            $pageSize = Config('page_size');
            $input = input('get.');
            $list = Db::name('celebrity_cemetery')->where('store_id',$id)->order('id desc')->paginate($pageSize,false,['query'=>$input]);
            // $snames = urldecode($store_name);
             //分页
            $page = $list->render();

            $this->assign('page',$page);
            $this->assign('category_id',$category_id);
            $this->assign('store_name',$store_name);
            $this->assign('list',$list);
            $this->assign('storeId',$id);
            return $this->fetch('celebritylist');
        }
    }

    /**
     * 添加名人墓地
     */
    public function addCelebrity(){
        if(Request::instance()->isPost()){
            $data = input('post.');
            $info = $data['info'];

            $info['updated_time'] = time();
            $info['created_time'] = time();
            if ($_FILES['image_url']['error'] == 0 && !empty($_FILES['image_url']['tmp_name'])) {
                $thumb = array(array(130,130,6));
                $ret = uploadOne('image_url', Config('celebrity_image'),$thumb);
                if ($ret['ok'] == 0) {
                    $this->error = $ret['error'];
                    return FALSE;
                } else {
                    $info['image_url'] = $ret['images'][0];
                    $info['thumb_image_url'] = $ret['images'][1];
                }
            }
            $res = Db::name('celebrity_cemetery')->insert($info);
            //面包屑信息
            $store = Db::name('store')->where('id',$info['store_id'])->field('category_id,name')->find();
            if($res){

                $this->success('修改成功', url('/store/Basestore/celebrityList', 'category_id='.$store['category_id'].'&store_name='.$store['name'].'&id='.$info['store_id']));
            }else{
                $this->error('添加失败');
            }
        }else{
            $storeId = input('id');
            $store = Db::name('store')->where('id',$storeId)->field('name,category_id,store_sn')->find();

            $this->assign('store',$store);
            $this->assign('storeId',$storeId);
            return $this->fetch('addcelebrity');
        }
    }

    /**
     * 编辑名人墓地
     */
    public function editCelebrity(){
        if (Request::instance()->isPost()){
            $data = input('post.');
            $info = $data['info'];
            $id = $data['id'];

            if(empty($id)){
                $this->error('请重新操作！');
            }

            $info['updated_time'] = time();
            if ($_FILES['image_url']['error'] == 0 && !empty($_FILES['image_url']['tmp_name'])) {
                $thumb = array(array(130, 130, 6));
                $ret = uploadOne('image_url', Config('celebrity_image'), $thumb);
                if ($ret['ok'] == 0) {
                    $this->error = $ret['error'];
                    return FALSE;
                } else {
                    $info['image_url'] = $ret['images'][0];
                    $info['thumb_image_url'] = $ret['images'][1];
                }
                $img =  Db::name('celebrity_cemetery')->field('image_url,thumb_image_url')->where('id',$id)->find();
                if(file_exists('.'.config('public_path').$img['image_url'])){
                    unlink('.'.config('public_path').$img['image_url']);
                    unlink('.'.config('public_path').$img['thumb_image_url']);
                }
            }
            $result = Db::name('celebrity_cemetery')->where('id',$id)->update($info);
            //面包屑导航
            $store = Db::name('store')->where('id',$info['store_id'])->field('category_id,name')->find();

            $store_name = $store['name'];
            if ($result) {
                $this->success('修改成功', url('/store/Basestore/celebrityList', 'category_id='.$store['category_id'].'&store_name='.$store_name.'&id='.$info['store_id']));
            } else {
                $this->error('请重新操作！');
            }
        }else{
            $id = input('id');
            if(empty($id)){
                $this->error('请重新操作！');
            }
            $result = Db::name('celebrity_cemetery')->where('id',$id)->find();

            //面包屑导航
            $store = Db::name('store')->where('id',$result['store_id'])->field('category_id,name')->find();

            $this->assign('store',$store);
            $this->assign('result',$result);
            $this->assign('store_id',$result['store_id']);
            return $this->fetch('editcelebrity');
        }
    }

    /**
     * 删除开启名人墓地
     */
    public function delcelebrity(){
        $id = input('id');
        $token = input('token');
        $array = array('flg'=>0,'msg'=>'操作失败');

        if($token == 'del'){
            $result = db('celebrity_cemetery')->whereIn('id',$id)->update(['status'=>Config('delete_status')]);
        }else if($token == 'start'){
            $result = db('celebrity_cemetery')->where('id',$id)->update(['status'=>Config('normal_status')]);
        }
        if($result){
            $array = array('flg'=>1,'msg'=>'操作成功');
        }
        echo json_encode($array);
    }

    /**
     * 园区列表
     */
    public function tombzone(){
        $store_id = input('id');
        if($store_id){
            $category_id = input('category_id');
            $store_name = input('store_name');
            $pageSize = Config('page_size');
            $input = input('get.');
            $list = Db::name('tomb_zone')->where('store_id',$store_id)->order('id desc')->paginate($pageSize,false,['query'=>$input]);

            //分页
           $page = $list->render();

            $store_sn = Db::name('store')->where('id',$store_id)->field('store_sn')->find();
            $this->assign('store_sn',$store_sn['store_sn']);
            $this->assign('page',$page);
            $this->assign('category_id',$category_id);
            $this->assign('store_name',$store_name);
            $this->assign('list',$list);
            $this->assign('storeId',$store_id);
            return $this->fetch('tombzone');
        }
    }

    /**
     * 添加园区
     */
    public function savetombzone(){
        $data = input("post.");
        $info = $data['info'];

        $info['created_time'] = time();
        $info['updated_time'] = time();
        $res = Db::name('tomb_zone')->insert($info);
        if($res){
            $result['flg'] = 1;
            $result['msg'] = '添加成功';
        }else{
            $result['flg'] = 0;
            $result['msg'] = '添加失败';
        }
        echo json_encode($result);
    }

    /**
     * 编辑园区
     */
    public function edittombzone(){
        if (Request::instance()->isPost()){
            $data = input('post.');
            $id = $data['id'];

            if($id){ 
                $info = $data['info'];
                $info['updated_time'] = time();
                $array = array('flg'=>0,'msg'=>'更新失败');

                $result = db('tomb_zone')->where('id',$id)->update($info);
                if($result){
                    $array = array('flg'=>1,'msg'=>'更新成功');
                }
            }
                echo json_encode($array);
        }else{
            $id = input('id');
            if($id){
                $data = Db::name('tomb_zone')->where('id',$id)->find();
                if($data){
                    $array = array('flg'=>1,'msg'=>$data);
                }
                echo json_encode($array);
            }
        }
    }

    /**
     * 删除开启园区
     */
    public function deltombzone(){
        $id = input('id');
        $token = input('token');
        $array = array('flg'=>0,'msg'=>'操作失败');

        if($token == 'del'){
            $result = db('tomb_zone')->whereIn('id',$id)->update(['status'=>Config('delete_status')]);
        }else if($token == 'start'){
            $result = db('tomb_zone')->where('id',$id)->update(['status'=>Config('normal_status')]);
        }
        if($result){
            $array = array('flg'=>1,'msg'=>'操作成功');
        }
        echo json_encode($array);
    }

    /**
     * 福位列表
     */
    public function tombs(){
        $store_id = input('id');
        if($store_id){
            $category_id = input('category_id');
            $store_name = urldecode(input('store_name'));

            $pageSize = Config('page_size');
            $input = input('get.');
            $list = Db::name('tombs')->where('store_id',$store_id)->order('id desc')->paginate($pageSize,false,['query'=>$input]);

            //获取墓型分类
            $categorytomb = Db::name('category')->column('id,name');
            //分页
            $page = $list->render();

            $this->assign('page',$page);
            $this->assign('category_id',$category_id);
            $this->assign('store_name',$store_name);
            $this->assign('categorytomb',$categorytomb);
            $this->assign('list',$list);
            $this->assign('storeId',$store_id);
            return $this->fetch('tombs');
        }
    }

    /**
     * 礼仪套餐
     */
    public function combo(){
        $input = input('get.');
        $storeId = $input['store_id'];
        $comboList = [];
        if($storeId){
            $comboList = Db::name('EtiquetteCombo')->where('store_id',$storeId)->field('id,combo_name,combo_type,image,thumb_image,combo_price,platform_price,created_time,status,sort')->order('created_time desc')->paginate(config('page_size'),false,['query' => $input]);
        }
        $page = $comboList->render();
        $input['page'] = isset($input['page']) && !empty($input['page']) ? $input['page'] : 1;
        $type = config('combo_type');
        $this->assign([
            'comboList' => $comboList,
            'page'      => $page,
            'input'     => $input,
            'type'     => $type,

        ]);
        return $this->fetch();
    }

    /**
     * 添加礼仪套餐
     */
    public function addcombo(){
        if(request()->isPost()){
            $inputPost = input('post.');
            $result = ['code' => 0,'msg' => '添加失败'];
            $data = $inputPost;
            $images = $this->_comboImg();
            if(!empty($images)){
                $data['image'] = $images['image'];
                $data['thumb_image'] = $images['thumb_image'];
            }
            $data['created_time'] = $data['updated_time'] = time();
            $ret = Db::name('EtiquetteCombo')->data($data)->insert();
            if($ret){
                $result = ['code' => 1,'msg' => '添加成功'];
            }
            echo json_encode($result);
        }else{
            $input = input('get.');
            $type = config('combo_type');
            $this->assign([
                'input' => $input,
                'type'     => $type,
            ]);
            return $this->fetch();
        }
    }

    /**
     * 编辑礼仪套餐
     */
    public function editcombo(){
        if(request()->isPost()){
            $input = input('post.');
            $result = ['code' => 0,'msg' => '更新失败'];
            $data = $input['info'];
            $images = $this->_comboImg();
            if(!empty($images)){
                $data['image'] = $images['image'];
                $data['thumb_image'] = $images['thumb_image'];
                if(!empty($input['img'])){
                    $oldImg = $input['img'];
                    if(!empty($oldImg['image']) && is_file('.'.$oldImg['image'])){
                        unlink('.'.$oldImg['image']);
                    }
                    if(!empty($oldImg['thumb_image']) && is_file('.'.$oldImg['thumb_image'])){
                        unlink('.'.$oldImg['thumb_image']);
                    }
                }
            }
            $data['updated_time'] = time();
            $ret = Db::name('EtiquetteCombo')->data($data)->update();
            if($ret){
                $result = ['code' => 1,'msg' => '更新成功'];
            }
            echo json_encode($result);
        }else{
            $input = input('get.');
            $id = $input['id'];
            $info = [];
            if($id){
                $info = Db::name('EtiquetteCombo')->where('id',$id)->field('id,combo_name,combo_type,image,thumb_image,combo_price,platform_price,status,seo_title,seo_keywords,seo_description,content,service_course,sort,description')->find();
            }
            $type = config('combo_type');
            $this->assign([
                'info'  => $info,
                'input' => $input,
                'type' => $type,
            ]);
            return $this->fetch();
        }
    }

    /**
     * 套餐图片上传方法
     * @return array
     */
    private function _comboImg(){
        $files = $_FILES;
        $data = [];
        if($files['image']['error'] == 0 && !empty($files['image']['tmp_name'])){
            $ret = uploadOne('image',config('combo_image'),config('combo_image_size.thumb'));
            if($ret['ok'] == 0){
                $this->error = $ret['error'];
            }else{
                $data['image'] = $ret['images'][0];
                $data['thumb_image'] = $ret['images'][1];
            }
        }

        return $data;
    }

    /**
     * 删除礼仪套餐
     * @return void
     */
    public function delcombo(){
        $input = input('get.');
        $result = ['code' => 0,'msg' => '操作失败'];
        if($input['id']){
            $data = [
                'id' => $input['id'],
                'status' => $input['status']
            ];
            $update = Db::name('EtiquetteCombo')->data($data)->update();
            if($update){
                $result = ['code' => 1,'msg' => '操作成功'];
            }
        }
        echo json_encode($result);
    }

    /**
     * 获取墓型分类
     */
    public function gettombs(){
        $category_pid = input('category_pid');
        $list = Db::name('category')->where('pid',$category_pid)->column('name','id');
        if($list){
            $array = array('flag'=>1,'data'=>$list);
        }else{
             $array = array('flag'=>0);
        }
        echo json_encode($array);
    }

    /**
     * 添加福位
     */
    public function addtombs(){
        if(Request::instance()->isPost()){
            $data = input('post.');
            //面包屑
            $category_id = input('category_id');
            $store_name = input('store_name');

            $info = $data['info'];
            $info['admin_id'] = 1;
            $store = Db::name('store')->where('id',$info['store_id'])->field('store_sn,name')->find();
            $info['store_sn'] = $store['store_sn'];
            $info['store_name'] = $store['name'];
            $info['updated_time'] = time();
            $info['created_time'] = time();

            if ($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])) {
                $thumb = config('tombs_image_size.thumb');
                $ret = uploadOne('image', config('tombs_image'),$thumb);
                if ($ret['ok'] == 0) {
                    $this->error = $ret['error'];
                    return FALSE;
                } else {
                    $info['image'] = $ret['images'][0];
                    $info['thumb_image'] = $ret['images'][1];
                }
            }
            $res = Db::name('tombs')->insert($info);
            if($res){
                $this->success('添加成功', url('/store/Basestore/tombs', 'category_id='.$category_id.'&store_name='.$store_name.'&id='.$info['store_id']));
            }else{
                $this->error('添加失败');
            }
        }else{
            $storeId = input('id');
            //获取该陵园下所有园区
            $where['store_id'] = $storeId;
            $where['status'] = Config('normal_status');
            $tomb_zone = Db::name('tomb_zone')->field('id,zone_name')->where($where)->column('id,zone_name');
            $sn = Db::name('store')->where('id',$storeId)->field('store_sn')->find();
            //获取墓型分类
            $categorytomb = Db::name('category')->where('pid',Config('category_tombs'))->column('id,name');
            //面包屑
            $category_id = input('category_id');
            $store_name = input('store_name');

            $this->assign('category_id',$category_id);
            $this->assign('store_name',$store_name);
            $this->assign('tomb_zone',$tomb_zone);
            $this->assign('categorytomb',$categorytomb);
            $this->assign('storeId',$storeId);
            $this->assign('store_sn',$sn['store_sn']);
            return $this->fetch('addtombs');
        }
    }

    /**
     * 编辑墓位
     */
    public function edittombs(){
        if(Request::instance()->isPost()){
            $data = input('post.');
            $info = $data['info'];
            $info['updated_time'] = time();
            //面包屑
            $category_id = input('category_id');
            $store_name = input('store_name');

            if ($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])) {
                $thumb = config('tombs_image_size.thumb');
                $ret = uploadOne('image', Config('tombs_image'),$thumb);
                if ($ret['ok'] == 0) {
                    $this->error = $ret['error'];
                    return FALSE;
                } else {
                    //删除原图
                    $delImg = Db::name('tombs')->where('id',$info['id'])->field('image,thumb_image')->find();
                    if(file_exists($delImg['image'])){
                        unlink('.'.$delImg['image']);
                        unlink('.'.$delImg['thumb_image']);
                    }
                    $info['image'] = $ret['images'][0];
                    $info['thumb_image'] = $ret['images'][1];
                }
            }
            $res = Db::name('tombs')->update($info);
            if($res){
                $this->success('修改成功', url('/store/Basestore/tombs', 'category_id='.$category_id.'&store_name='.$store_name.'&id='.$info['store_id']));
            }else{
                $this->error('修改失败');
            }
        }else{
            $storeId = input('store_id');
            $id = input('id');

            $tombs = Db::name('tombs')->where('id',$id)->find();

            //获取该陵园下所有园区
            $where['store_id'] = $storeId;
            $where['status'] = Config('normal_status');
            $tomb_zone = Db::name('tomb_zone')->field('id,zone_name')->where($where)->column('id,zone_name');

            //获取墓型父分类
            $categorytomb = Db::name('category')->where('pid',Config('category_tombs'))->column('id,name');

            //获取墓型子分类
            $categoryson = Db::name('category')->where('pid',$tombs['category_pid'])->column('id,name');


             //面包屑
            $category_id = input('category_id');
            $store_name = input('store_name');

            $this->assign('category_id',$category_id);
            $this->assign('store_name',$store_name);
            $this->assign('tombs',$tombs);
            $this->assign('tomb_zone',$tomb_zone);
            $this->assign('categorytomb',$categorytomb);
            $this->assign('categoryson',$categoryson);
            $this->assign('storeId',$storeId);
            $this->assign('id',$id);
            return $this->fetch('edittombs');
        }
    }

    /**
     * 删除开启福位
     */
    public function deltombs(){
        $id = input('id');
        $token = input('token');
        $array = array('flg'=>0,'msg'=>'操作失败');

        if($token == 'del'){
            $result = db('tombs')->where('id',$id)->update(['status'=>Config('delete_status')]);
        }else if($token == 'start'){
            $result = db('tombs')->where('id',$id)->update(['status'=>Config('normal_status')]);
        }
        if($result){
            $array = array('flg'=>1,'msg'=>'操作成功');
        }
        echo json_encode($array);
    }
    /**
     * 商家推荐列表
     * @param void;
     * @return void;
     */
    public function recommendlist(){
        //查找地区
        $regionWhere['status'] = config('normal_status');
        $regiondata = Db::name('region')->where($regionWhere)->column('id,name,pid');
        foreach($regiondata as $key=>$val){
            //查找省份
            if($val['pid'] == config('china_num')){
                $province[$key] = $val['name'];
            }
        }
        //处理筛选
        $input = $bool = input('get.');
        $city = array();
        $where = array();
        unset($bool['page']);
        if($bool){
            $seachprovince = $input['province'];
            if(!empty($seachprovince)){
                $where['province_id'] = $seachprovince;
                foreach($regiondata as $key=>$val){
                    if($val['pid'] == $seachprovince){
                        $city[$key] = $val['name'];
                    }
                }
            }
            if(!empty($input['city'])){
                $seachcity = $input['city'];
            }
            if(!empty($seachcity)){
                $where['city_id'] = $seachcity;
            }
            $seachcategory = $input['category'];
            if(!empty($seachcategory)){
                $where['category_id'] = $seachcategory;
            }
            $seachfeature = $input['feature'];
            if(!empty($seachfeature)){
                $where['feature'] = $seachfeature;
            }
        }
        
        //dump($input);die;
        //商家类型
        $catWhere['is_show'] = config('normal_status');//是否显示
        $catWhere['pid'] = config('category_store_id');//店铺分类
        $category =  Db::name('category')->where($catWhere)->column('id,name');
        //位置
        $recommend = config('recommend_location');
        
        $pageSize = Config('page_size');
        //查找数据
        $data = StoreRecommend::with('store')->where($where)->order('province_id ASC,sort DESC')->paginate($pageSize,false,['query'=>$input]);
        //dump($data);die;
        //分页
        $page = $data->render();
        //处理数据
        $count = count($data);
        for($i=0;$i<$count;$i++){
            $data[$i]['province_name'] = $regiondata[$data[$i]['province_id']]['name'];
            
            $data[$i]['feature_name'] = $recommend[$data[$i]['feature']];
            $data[$i]['category_name'] = $category[$data[$i]['category_id']];
        }
        //dump($data);die;
        $this->assign('city',$city);
        $this->assign('page',$page);
        $this->assign('data',$data);
        $this->assign('recommend',$recommend);
        $this->assign('category',$category);
        $this->assign('province',$province);
        return $this->fetch();
    }
    /**
     * 商家推荐添加
     * @param void;
     * @return string|json;
     */
    public function recommendadd(){
        if(Request::instance()->isPost()){
            $input = input('post.');
            if($input){
                $count = count($input['storeId']);
                //dump($input);die;
                for($i=0;$i<$count;$i++){
                    $input['city_id'] = 0;
                    $store = explode(":",$input['storeId'][$i]);
                    $data[$i]['province_id'] = $input['province_id'];
                    $data[$i]['city_id'] = $input['city_id'];
                    $data[$i]['feature'] = $input['feature'];
                    $data[$i]['category_id'] = $input['category_id'];
                    $data[$i]['store_id'] = $store['0'];
                    $data[$i]['store_sn'] = $store['1'];
                    $data[$i]['created_time'] = date('Y-m-d H:i:s',time());
                    $data[$i]['sort'] = $input['sort'];
                }
                if(Db::name('StoreRecommend')->insertAll($data)){
                    $this->success('添加成功!',url('Basestore/recommendlist'));
                }
            }else{
                $this->error('添加失败!');
            }
        }else{
            //商家类型
            $catWhere['is_show'] = config('normal_status');//是否显示
            $catWhere['pid'] = config('category_store_id');//店铺分类
            $category =  Db::name('category')->where($catWhere)->column('id,name');
            //位置
            $recommend = config('recommend_location');
            //查找省份
            $regionWhere['status'] = config('normal_status');
            $regionWhere['level'] = config('normal_status');
            $province = Db::name('region')->where($regionWhere)->column('id,name');

            $this->assign('recommend',$recommend);
            $this->assign('category',$category);
            $this->assign('province',$province);
            return $this->fetch();
        }
    }
    /**
     * 商家推荐删除
      * @param void;
      * @return string|json;
      */
    public function recommenddel(){
        $result = array('flag'=>0,'msg'=>'删除失败!');
        $input = input('post.');
        if($input){
            if(Db::name('StoreRecommend')->where($input)->delete()){
                $result['flag'] = 1;
                $result['msg'] = '操作成功!';
            }
        }
        echo json_encode($result);
    }
    /**
      * 获取商家列表
      * @param void;
      * @return string|json;
      */
    public function getcemetery(){
        $result = array('flg'=>0,'msg'=>'操作失败!');
        $city_id = input('cityId');
        if(!empty($city_id)){
            $where['city_id'] = $city_id;
            $where['status'] = config('normal_status');
            $where['member_status'] = array('neq',0);
            $list = Db::name('store')->where($where)->column('id,name,store_sn');
            if($list){
                $result['flg'] = 1;
                $result['msg'] = 'ok';
                $result['data'] = $list;
            }
        }
        echo json_encode($result);
    }
}