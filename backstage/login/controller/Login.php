<?php
namespace back\login\controller;
use think\Controller;
use think\Request;
use think\Db;
use back\extra\model\Privilege;
use think\Session;//session类
class Login extends Controller{
    /**
     * 登陆方法
     * 如果直接访问其他方法没有登陆直接跳转到这里
     *
     * @return void |
     */
    public function login(){
        $data = input('post.');
        if($data){
                $pwd      = $data['password'];
                $password = encryptAdmin($pwd);
                $where['pwd'] = $password;
                $where['login_name'] = $data['username'];
                $info = db('admin')->where($where)->find();
                if($info){
                    //首先判断用户是否正常
                    if ($info['status'] == config('delete_status')) {
                        $result['flag'] = 2;
                        $result['msg'] = '该账号已被禁用';
                }else{
                        $login_name = $info['login_name'];
                        // 判断用户是否是超级用户
                        if($login_name!=config('login_name')){
                            // 获取该用户具有的权限
                            $privilegemodel = new Privilege;
                            $privilege = $privilegemodel->getUsetPrivilege($info['id']);
                            $privilegeName = json_encode($privilege['name']);
                            $privilegeIds = json_encode($privilege['ids']);
                            session('privilegeData',$privilegeName);
                            session('privilegeIdsData',$privilegeIds);
                        }
                        
                        session('admin_id',$info['id']);
                        session('login_name',$info['login_name']);
                        $lifeTime = config('life_time'); 
                        setcookie(session_name(), session_id(), time() + $lifeTime, "/");

                        $result['flag'] = 3;
                        $result['msg'] = '登录成功';
                        /**
                         * 写入登录log
                         */
                        $login_data['admin_id'] = $info['id'];
                        $login_data['client_name'] = $info['email'];
                        $login_data['user_agent'] = get_client_browser();
                        $login_data['login_ip'] = get_client_ip(1);
                        $login_data['login_time'] = date('Y-m-d H:i:s');
                        $login_log_db = db('LoginLogs')->insert($login_data);
                    }
                }else{
                 $result['flag'] = 4;
                 $result['msg'] = '用户名或密码错误';
                } 
            echo json_encode($result);
        }else{
            $adminId = session('admin_id');
            if(!empty($adminId)){
                $this->redirect('/index/Index/myWork');
            }else{
                 return $this->fetch('login');
            }
        }
    }


    /**
     * 退出登陆
     * @return void
     */
    public function logout()
    {   
        session('admin_id',null);
        session('login_name',null);
        $this->redirect('/login/login/login');
        //return $this->fetch('login');
    }

}