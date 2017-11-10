<?php
namespace back\store\controller;
use think\Controller;
use think\Request;//请求类
use think\Db;//数据类
use back\extra\model\StoreProfiles;
use back\extra\controller\Base;
use think\Cache;

/**
 * 合同档案控制器
 * @author hqy
 * @version 1.0
 * @date 2017/5/18
 * 
 */
class Profiles extends Base 
{
    /**
     * 合同列表页
     * @param  input|void;
     * @return  output|void;
     */
    public function proList()
    {
        $where['status'] = Config('normal_status');
        $this->_pro($where);
        $this->assign('nowTime',time());
        return $this->fetch();
    }
    /**
     * 过期合同列表页
     * @param  input|void;
     * @return  output|void;
     */
    public function proOverdue()
    {   
        $where['status'] = Config('normal_status');
        $where['end_time'] = array('elt',time());
        $this->_pro($where);
        return $this->fetch();
    }
    /**
     * 垃圾合同列表页
     * @param  input|void;
     * @return  output|void;
     */
    public function proRubbish()
    {   
        $where['status'] = array('neq',Config('normal_status'));
        $this->_pro($where);
        return $this->fetch();
    }
    
    /**
     * 公共合同核心方法
     */
    private function _pro($condition=null)
    {   
        $input = array();
        if(!empty($condition)){
            $proWhere = $condition;
        }
        $showStoreName = input('show_store_name');
        if(!empty($showStoreName)){
            $proWhere['show_store_name'] = ['like','%'.$showStoreName.'%'];
            $input['show_store_name'] = $showStoreName;
        }
        $provinceId = input('province_id');
        $cityData = array();
        if(!empty($provinceId)){
            $proWhere['province_id'] = $provinceId;
            $input['province_id'] = $provinceId;
            //对应的市
            $cityWhere['status'] = Config('normal_status');
            $cityWhere['pid'] = $provinceId;
            $cityData= Db::name('Region')->where($cityWhere)->column('name','id');
        }
        $cityId = input('city_id');
        if(!empty($cityId)){
            $proWhere['city_id'] = $cityId;
            $input['city_id'] = $cityId;
        }
        $memberStatus = input('member_status');
        if(!empty($memberStatus)){
            $proWhere['member_status'] = $memberStatus;
            $input['member_status'] = $memberStatus;
        }
        $field = 'id,show_store_name,province_id,city_id,address,category_id,'
                . 'profile_name,member_status,return_amount,contact_man,'
                . 'telephone,mobile,fax,start_time,end_time,remarks,give_up';
        $pageSize = Config('page_size');
        $data = StoreProfiles::with(array('Province','City','Category','CategoryGroup'))->where($proWhere)->field($field)->order('id desc')->paginate($pageSize,false,['query' => $input]);
        //分页
        $page = $data->render();
        
        $storemember = getStoreMember();//商家会员类型
        $storemember['0'] = '';
        //地区查询
        $regionWhere['status'] = Config('normal_status');
        $regionWhere['pid'] = Config('china_num');
        $provinceData= Db::name('Region')->where($regionWhere)->column('name','id');
        $this->assign('cityData',$cityData);
        $this->assign('provinceData',$provinceData);
        $this->assign('memberStatus',$storemember);
        $this->assign('data',$data);
        $this->assign('page',$page);
    }
    
    /**
     * 添加合同档案
     * @param  input|void;
     * @return  output|void;
     */
    public function proAdd()
    {   
        if(Request::instance()->isPost()){
            $post = Request::instance()->post();
            $info = $post['info'];
            $info['admin_id'] = Request::instance()->session('admin_id');
            $info['start_time'] = strtotime($info['start_time']);
            $info['end_time'] = strtotime($info['end_time']);
            $info['receive_time'] = strtotime($info['receive_time']);
            $adminWhere['id'] = $info['principal_id'];
            $adminData= Db::name('Admin')->where($adminWhere)->field('name')->find();
            $info['principal'] = $adminData['name'];
            $info['updated_time'] = time();
            $info['created_time'] = time();
            $info['status'] = Config("normal_status");
            $info['min_price'] = (float)$info['min_price'];
            $info['max_price'] = (float)$info['max_price'];
            if(Db::name('StoreProfiles')->insert($info)){
                $this->success('添加成功!', 'store/Profiles/proList');
            }else{
                $this->error('添加失败!');
            }
        }else{
            //分类查询
            $cateWhere['is_show'] = Config("normal_status");
            $cateWhere['pid'] = array('in',array(Config('category_group_id'),Config('category_store_id')));
            $cateData= Db::name('Category')->where($cateWhere)->field('id,pid,name')->select();
            foreach($cateData as $val){
                if($val['pid'] == Config('category_group_id')){
                    $groupCate[$val['id']] = $val['name']; 
                }else{
                    $storeCate[$val['id']] = $val['name'];
                }
            }
            //地区查询
            $regionWhere['status'] = Config('normal_status');
            $regionWhere['pid'] = Config('china_num');
            $proData= Db::name('Region')->where($regionWhere)->column('name','id');
            //商务部门人员
            $principalData =$this->getBusinessMen();
            //商家会员类型
            $memberStatus= getStoreMember();
            $this->assign('memberStatus',$memberStatus);
            $this->assign('principal',$principalData);
            $this->assign('proData',$proData);
            $this->assign('groupCate',$groupCate);
            $this->assign('storeCate',$storeCate);
            return $this->fetch();
        }
    }
    /**
     * 放弃合同档案
     * @param  input|void;
     * @return  output|json(string);
     */
    public function proDel()
    {
        $result = array('flag'=>0,'msg'=>'操作失败');
        $id = input('id');
        if(!empty($id)){
            if(Request::instance()->isGet()){
                $proWhere['id'] = $id;
                $remark= Db::name('StoreProfiles')->where($proWhere)->field('give_up')->find();
                $result['flag'] = 1;
                $result['msg'] = '操作成功!';
                $result['remarks'] =  $remark['give_up'];
            }else{
                $data['id'] = (int)$id;
                $data['give_up'] = input('remarks');
                $data['status']  = Config('delete_status');
                $data['updated_time'] = time();
                if(Db::name('StoreProfiles')->update($data)){
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功!';
                }
            }
        }
        echo json_encode($result);
    }
    /**
     * 省市联动
     * @param  input|void;
     * @return  output|json(string);
     */
    public function getRegion()
    {
        $result = array('flag'=>0,'msg'=>'操作失败'); 
        $provinceId = Request::instance()->post('provinceId');
        if(!empty($provinceId)){
            $regionWhere['pid'] = $provinceId;
            $regionWhere['status'] = Config('normal_status');
            $regionData = Db::name('Region')->where($regionWhere)->column('name','id');
            $result['flag'] = 1;
            $result['msg'] = '操作成功!';
            $result['data'] = $regionData;
        }
        echo json_encode($result);
    }
    /**
     * 编辑合同档案
     * @param  input|void;
     * @return  output|void;
     */
    public function proEdit()
    {
        $objective['object'] = 'Profiles/proList';//跳转到列表
        $objective['now'] = 'Profiles/proEdit';//当前
        $this->_edit($objective);
        
        return $this->fetch('proEdit');
    }
     /**
     * 编辑过期合同档案
     * @param  input|void;
     * @return  output|void;
     */
    public function proOverEdit()
    {
        $objective['object'] = 'Profiles/proOverdue';//跳转到列表
        $objective['now'] = 'Profiles/proOverEdit';//当前
        $this->_edit($objective);
        return $this->fetch('proEdit');
    }
    
    /**
     * 核心编辑合同档案方法
     */
    private function _edit($objective)
    {
        $id = input('id');
        if(!empty($id)){
            if(Request::instance()->isPost()){
                $post = Request::instance()->post();
                $info = $post['info'];
                $getinfo = $post['getInfo'];
                $info['id'] = $id;
                $info['updated_time'] = time();
                $info['start_time'] = strtotime($info['start_time']);
                $info['end_time'] = strtotime($info['end_time']);
                $info['receive_time'] = strtotime($info['receive_time']);
                $adminWhere['id'] = $info['principal_id'];
                $adminData= Db::name('Admin')->where($adminWhere)->field('name')->find();
                $info['principal'] = $adminData['name'];
                if(Db::name('StoreProfiles')->update($info)){
                    $this->success('编辑成功!', url($objective['object'],'show_store_name='.$getinfo['show_store_name'].'&province_id='.$getinfo['province_id'].'&member_status='.$getinfo['member_status'].'&city_id='.$getinfo['city_id'].'&page='.$getinfo['page']));
                }else{
                    $this->error('编辑失败!');
                }
            }else{
                $proWhere['id'] = $id; 
                $data = Db::name('StoreProfiles')->where($proWhere)->find();
                $data['start_time'] = date('Y-m-d',$data['start_time']);
                $data['end_time'] = date('Y-m-d',$data['end_time']);
                $data['receive_time'] = date('Y-m-d',$data['receive_time']);
                
                //分类查询
                $cateWhere['is_show'] = Config("normal_status");
                $cateWhere['pid'] = array('in',array(Config('category_group_id'),Config('category_store_id')));
                $cateData= Db::name('Category')->where($cateWhere)->field('id,pid,name')->select();
                foreach($cateData as $val){
                    if($val['pid'] == Config('category_group_id')){
                        $groupCate[$val['id']] = $val['name']; 
                    }else{
                        $storeCate[$val['id']] = $val['name'];
                    }
                }
                //地区查询
                $regionWhere['status'] = Config('normal_status');
                $regionWhere['pid'] = Config('china_num');
                $proData= Db::name('Region')->where($regionWhere)->column('name','id');
                $cityWhere['status'] = Config('normal_status');
                $cityWhere['pid'] = $data['province_id'];
                $cityData = Db::name('Region')->where($cityWhere)->column('name','id');
                //商务部门人员
                $principalData =$this->getBusinessMen();
                //商家会员类型
                $memberStatus= getStoreMember();
                //接收get参数
                $get = Request::instance()->param();
                $getData = [
                    'show_store_name'    => isset($get['show_store_name']) ? $get['show_store_name'] : '',
                    'province_id'        => isset($get['province_id']) ? $get['province_id'] : '',
                    'member_status'      => isset($get['member_status']) ? $get['member_status'] : '',
                    'city_id'            => isset($get['city_id']) ? $get['city_id'] : '',
                    'page'               => isset($get['page']) ? $get['page'] : ''
                ];
                $this->assign('getData',$getData);
                $this->assign('memberStatus',$memberStatus);
                $this->assign('principal',$principalData);
                $this->assign('proData',$proData);
                $this->assign('cityData',$cityData);
                $this->assign('groupCate',$groupCate);
                $this->assign('storeCate',$storeCate);
                $this->assign('objective',$objective);
                $this->assign('data',$data);
            } 
        }else{
            $this->error('操作失败!');
        }
    }
   
    /**
     * 续签合同档案
     * @param  input|void;
     * @return  output|void;
     */
    public function proRenew()
    {
        $id = input('id');
        if(!empty($id)){
            if(Request::instance()->isPost()){
                //开户事务
                Db::startTrans();
                $post = Request::instance()->post();
                $info = $post['info'];
                $info['admin_id'] = Request::instance()->session('admin_id');
                $info['start_time'] = strtotime($info['start_time']);
                $info['end_time'] = strtotime($info['end_time']);
                $info['receive_time'] = strtotime($info['receive_time']);
                $adminWhere['id'] = $info['principal_id'];
                $adminData= Db::name('Admin')->where($adminWhere)->field('name')->find();
                $info['principal'] = $adminData['name'];
                $info['updated_time'] = time();
                $info['created_time'] = time();
                $info['status'] = Config("normal_status");
                $info['min_price'] = (float)$info['min_price'];
                $info['max_price'] = (float)$info['max_price'];
                if(Db::name('StoreProfiles')->insert($info)){
                    $updateData['id'] = $id;
                    $updateData['status'] = 2;//已续签
                    if(Db::name('StoreProfiles')->update($updateData)){
                        // 提交事务
                        Db::commit();
                        $this->success('续签成功!', 'store/Profiles/proList');
                    }
                }else{
                    // 回滚事务
                    Db::rollback();
                    $this->error('续签失败!');
                }
            }else{
                $proWhere['id'] = $id; 
                $data = Db::name('StoreProfiles')->where($proWhere)->find();
                //分类查询
                $cateWhere['is_show'] = Config("normal_status");
                $cateWhere['pid'] = array('in',array(Config('category_group_id'),Config('category_store_id')));
                $cateData= Db::name('Category')->where($cateWhere)->field('id,pid,name')->select();
                foreach($cateData as $val){
                    if($val['pid'] == Config('category_group_id')){
                        $groupCate[$val['id']] = $val['name']; 
                    }else{
                        $storeCate[$val['id']] = $val['name'];
                    }
                }
                //地区查询
                $regionWhere['status'] = Config('normal_status');
                $regionWhere['pid'] = Config('china_num');
                $proData= Db::name('Region')->where($regionWhere)->column('name','id');
                $cityWhere['status'] = Config('normal_status');
                $cityWhere['pid'] = $data['province_id'];
                $cityData = Db::name('Region')->where($cityWhere)->column('name','id');
                //商务部门人员
                $principalData =$this->getBusinessMen();
                //商家会员类型
                $memberStatus= getStoreMember();
                $this->assign('memberStatus',$memberStatus);
                $this->assign('principal',$principalData);
                $this->assign('proData',$proData);
                $this->assign('cityData',$cityData);
                $this->assign('groupCate',$groupCate);
                $this->assign('storeCate',$storeCate);
                $this->assign('data',$data);
                return $this->fetch();
            } 
        }else{
            $this->error('操作失败!');
        }
    }
    /**
     * 预览合同档案
     * @param  input|void;
     * @return  output|void;
     */
    public function proPreview()
    {
        $id = input('id');
        if(!empty($id)){
            $data = StoreProfiles::with(array('Province','City','Category','CategoryGroup'))->where('id',$id)->find();
            //商家会员类型
            $memberStatus= getStoreMember();
            $data['member_status'] =$memberStatus[$data['member_status']]; 
            $imageData = Db::name('StoreProfilesImage')->where('profiles_id',$data['id'])->field('image_name,image,thumb_image,type')->order('sort desc')->select();
            $elecImage = array();
            $priceImage = array();
            foreach($imageData as $val){
                if($val['type'] == Config('profiles_electron_image')){
                    $elecImage[] = $val;
                }else{
                    $priceImage[] = $val;
                }
            }
            $this->assign('elecImage',$elecImage);
            $this->assign('priceImage',$priceImage);
            $this->assign('data',$data);
            return $this->fetch();
        }else{
            $this->error('操作失败!');
        }
    }
    /**
     * 合同档案图片列表
     * @param  input|void;
     * @return  output|void;
     */
    public function imageList()
    {
        $proId = input('id');
        if(!empty($proId)){
            $this->_imageList($proId);
            return $this->fetch();
        }else{
            $this->error('查看失败!');
        }
    }
    /**
     * 回收档案图片列表
     * @param  input|void;
     * @return  output|void;
     */
    public function imageRuList()
    {
        $proId = input('id');
        if(!empty($proId)){
            $this->_imageList($proId);
            return $this->fetch();
        }else{
            $this->error('查看失败!');
        }
    }
    //图片列表核心
    private function _imageList($id){
        $input['id'] = $id;
        $imageWhere['profiles_id'] = $id;
        $pageSize = Config('page_size');
        $data = Db::name('StoreProfilesImage')->where($imageWhere)->order('updated_time desc')->paginate($pageSize,false,['query' =>$input]);
        //分页
        $page = $data->render();
        $this->assign('page',$page);
        $this->assign('data',$data);
        $this->assign('proId',$id);
    }
    /**
     * 添加合同(电子)档案图片
     * @param  input|void;
     * @return  output|json(string);
     */
    public function imageAdd()
    {
        $result = array('flag'=>0,'msg'=>'添加失败!');
        $post = Request::instance()->post();
        $info = $post['info'];
        $info['admin_id'] = Request::instance()->session('admin_id');
        $info['updated_time'] = time();
        $info['created_time'] = time();
        $info['status'] = Config('normal_status');
        $imagesRet = upload('image',Config('profiles_image'),array(array(225,220,6)));
        if($imagesRet['ok'] != 0){
            foreach($imagesRet['images'] as $k=>$v){
                $info['image'] = $v;
                foreach($imagesRet['thumb'][$k] as $key=>$value){
                    $info['thumb_image'] = $value;
                }
                if(Db::name('StoreProfilesImage')->insert($info)){
                    $result = array('flag'=>1,'msg'=>'添加成功');
                }
            }
        }
        echo json_encode($result);
    }
    /**
     * 删除合同(电子)档案图片
     * @param  input|void;
     * @return  output|json(string);
     */
    public function imageDel()
    {
        $result = array('flag'=>0,'msg'=>'删除失败!');
        $id = input('id');
        if(!empty($id)){
            $info['updated_time'] = time();
            $info['status'] = Config('delete_status');
            if(Db::name('StoreProfilesImage')->whereIn('id',$id)->update($info)){
                $result = array('flg'=>1,'msg'=>'删除成功!');
            }
        }
        echo json_encode($result);
    }
     /**
     * 开启合同(电子)档案图片
     * @param  input|void;
     * @return  output|json(string);
     */
    public function imageStar()
    {
        $result = array('flag'=>0,'msg'=>'开启失败!');
        $id = input('id');
        if(!empty($id)){
            $info['updated_time'] = time();
            $info['status'] = Config('normal_status');
            if(Db::name('StoreProfilesImage')->whereIn('id',$id)->update($info)){
                $result = array('flg'=>1,'msg'=>'开启成功!');
            }
        }
        echo json_encode($result);
    }
    
    /**
     * 编辑合同(电子)档案图片
     * @param  input|void;
     * @return  output|json(string);
     */
    public function imageUpdate()
    {
        $result = array('flag'=>0,'msg'=>'操作失败!');
        $imageId = input('id');
        if(!empty($imageId)){
            if(Request::instance()->isGet()){
                $findData = Db::name('StoreProfilesImage')->where('id',$imageId)->find();
                $result = array('flag'=>1,'msg'=>'成功!','data'=>$findData);
            }else if(Request::instance()->isPost()){
                $post = Request::instance()->post();
                $info = $post['info'];
                $info['id'] = $imageId;
                $info['updated_time'] = time();
                if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){
                    $imagesRet = uploadOne('image',Config('profiles_image'),array(array(225,220,6)));
                    if($imagesRet['ok'] == 0){
                        $this->error($imagesRet['error']);
                    }else{
                        $delImg = Db::name('StoreProfilesImage')->where('id',$imageId)->field('image,thumb_image')->find();
                        $info['image'] = $imagesRet['images'][0];
                        $info['thumb_image'] = $imagesRet['images'][1];
                        $res = Db::name('StoreProfilesImage')->update($info);
                        if($res && file_exists('.'.$delImg['image']) && file_exists('.'.$delImg['thumb_image'])){
                            unlink('.'.config('public_path').$delImg['image']);
                            unlink('.'.config('public_path').$delImg['thumb_image']);
                        }
                    }
                }else{
                    $res = Db::name('StoreProfilesImage')->update($info);
                }
                if($res){
                    $result = array('flag'=>1,'msg'=>'更新成功!');
                }
            }
        }
        echo json_encode($result);
    } 
}