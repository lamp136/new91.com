<?php
namespace back\extra\controller;
use think\Controller;
use think\Request;//请求类
use think\Session;//session类
use think\Db;
use back\extra\model\RoleUser;

class Base extends Controller 
{
    public function _initialize(){
        //判断是否登陆
        $adminId = Session::get('admin_id');
        if(empty($adminId)){
            session('admin_id',null);
            session('login_name',null);
            $this->redirect('login/Login/login');
            return false;
        }
        self::operateLog();
        //获取左侧的菜单
        $path  =  Request::instance()->module().'/'.Request::instance()->controller().'/'.Request::instance()->action();
        $allData = Db::name('privilege')->field('id,pid,level,name,title')->where('status',Config('normal_status'))->order('sort desc,id asc')->select();
        $currentpri = array();
        $data = array();
        foreach($allData as $val){
            //判断当前访问方法是否在权限表中
            if($val['name'] == $path){
                $currentpri = $val;
            }
            //找出level小于或等于2的权限(用于菜单)
            if($val['level'] <= 2){
                $data[] = $val;
            }
        }
        if(!empty($currentpri)){
            //获取top顶部导航节点,即level=0；
            if($currentpri['level']==0){
                $currentpriId = $currentpri['id'];
            }else{
                $currentpriId = $this->gettopmenu($allData,$currentpri['pid']);
            }
            //获取其菜单节点,即level为1；
            if($currentpri['level']<=1){
                $memuId = $currentpri['id'];
            }else{
                $memuId = $this->gettopmenu($allData,$currentpri['pid'],1);
            }
        }else{
            $currentpriId = 0;
            $memuId = 0;
        }
        //递归将权限重组,将左侧菜单列举出来.
        $loginName = Session::get('login_name');
        if($loginName!=Config('admin_name')){
            $menuData = $this->getMenuTree($data,0);
        }else{
            $menuData = $this->getTree($data,0);
        }
        $this->assign('menuData',$menuData);
        $this->assign('path',$path);
        $this->assign('currentpriId',$currentpriId);
        $this->assign('memuId',$memuId);
        //权限的判断
        $noAuthPath = Config('no_auth_path');
        if(in_array($path,$noAuthPath)){
            return true;
        }
        if($loginName==Config('admin_name')){
            return true;
        }
        //当前访问的控制器和方法，是否在session中
        $sessionPrivil = json_decode(Session::get('privilegeData'));
        if(in_array($path,$sessionPrivil)){
            return true;
        }else{
            session('admin_id',null);
            session('login_name',null);
            $this->error('您没有权限访问','login/Login/login');
            die;
        }
    }


    /**
     * 记录操作日志
     */
    public function operateLog(){
        //判断是否记录
        if(config('save_log_open')){
            $contro = Request::instance()->controller();
            $action = Request::instance()->action();

            if($action == '' || $contro =='Index') {
                return false;
            }else {
                $getdata = input('get.');
                $postdata = input('post.');
                $username = session('login_name');
                $userid   = session('admin_id');
                if(empty($getdata) && empty($postdata)){
                    return false;
                }else{
                    $after  = var_export(array('GET' => $getdata),true);
                    $before = var_export(array('POST' => $postdata),true);
                }

                $log_db = Db::name('logs');
                $log_db->insert(array(
                    'controller' => $contro,
                    'action'     => $action,
                    'time'       => time(),
                    'admin_id'   => $userid,
                    'admin_name' => $username,
                    'get_data'   => $after,
                    'post_data'  => $before,
                    'ip'         => get_client_ip(1)
                ));
            }
        }
    }



     /**
     * 获取顶级菜单
     */
    private function gettopmenu($data,$id,$level=0){
        static $topmemnid = '';
        foreach($data as $val){
            if($val['id'] == $id){
                if($val['level'] == $level){
                    $topmemnid = $val['id'];
                }else{
                    $this->gettopmenu($data,$val['pid'],$level);
                }
            }
        }
        return $topmemnid;
    }
    //处理权限关系
    private function getTree($data,$pid=0){
        $result = array();
        foreach ($data as $v){
            if ($v['pid'] == $pid){
                $v['child'] = $this->getTree($data,$v['id']);
                $result[] = $v;
            }
        }
        return $result;
    } 
    private function getMenuTree($data,$pid=0){
        $result = array();
        foreach ($data as $v){
            //找当前分类的子分类，默认从顶级开始找
            $sessionPrivil = json_decode(Session::get('privilegeIdsData'),true);
            if ($v['pid'] == $pid && in_array($v['id'],$sessionPrivil)){ 
                //找到了，继续以找到的分类为当前分类，找它的后代节点,并将结果放到当前元素的下标为child的元素中
                $v['child'] = $this->getMenuTree($data,$v['id']);
                //然后将$v 保存到$list中
                $result[] = $v;
            }
            continue;
        }
        return $result;
    }
    /**
     * 获取市区方法
     * @param  int $pid 省份id
     * @return array
     */
    public function cityList($pid){
        $data = [];
        if($pid){
            $data = Db::name('region')->where('pid',$pid)->column('id,name');
        }

        return $data;
    } 

  
    
    /**
     * 获取商务人员和全部管理人员
     * @param $where 为商务人员下的条件;
     * @param $bool  true商务人员信息(默认)  false为全部管理员信息;
     * @param $work  true时为在职人员(默认)  false时取全部人员
     * @return array;
     */
    public function getBusinessMen($bool=true,$work=true,$where = array()) {
        $menData = array();
        $normal = config('normal_status');
        if ($bool) {
            $allData = Db::name('Role')->field('id,pid')->select();

            $tmpRoleId = $this->getRoleId($allData,Config('business_id'));
            $roleId = Config('business_id').$tmpRoleId;
            $businesses = RoleUser::with(array('admin'))->whereIn('role_job_id',$roleId)->select();
            if(!empty($businesses)){
                foreach ($businesses as $key => $val) {
                    if(empty($val['admin']['name'])){
                        continue;
                    }
                    if($work) {
                        if($val['admin']['status']  == $normal){
                            $menData[$val['admin']['id']] = $val['admin']['name'];
                        }
                    }else{
                        $menData[$val['admin']['id']] = $val['admin']['name'];
                    }
                }
            }

        }else{
            $allMen = Db::name('Admin')->field('id,name,status')->select();

            if(!empty($allMen)){
                // foreach($allMen as $val){
                //     if(empty($val['name'])){
                //         continue;
                //     }
                //     // if($work && $val['status']  == 1) {
                //     //     $menData[$val['id']] = $val['name'];
                //     // }else{
                //     //     $menData[$val['id']] = $val['name'];
                //     // }
                // }
                foreach ($allMen as $id => $value) {
                        if(empty($value['name'])){
                            continue;
                        }
                        $status = $value['status'];
                        $data[$id]['name'] = $value['name'];
                        $data[$id]['status'] = $status;
                        if($work && $status  == config('normal_status')) {
                            $menData[$value['id']] = $value['name'];
                        } else if (!$work) {
                            $menData[$value['id']] = $value['name'];
                        }
                    }
            }
        }
        return $menData;
    } 
    /**
     * 获取部门下级所有Id
     * @param  array   $data 部门所有数据
     * @param  integer $id   部门Id
     * @return string  eg:,8,9,5,12
     */
    private function getRoleId($data = [],$id = 0){
        if(!empty($data)){
            static $result = '';
            foreach($data as $val){
                if($val['pid'] == $id ){
                    $result = $result.','.$val['id'];
                    $this->getRoleId($data,$val['id']);
                }
            }
        }
        return $result;
    }



}