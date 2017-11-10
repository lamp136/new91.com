<?php
namespace back\extra\model;
use think\Model;

class Role extends Model{

	 /**
     * 将查出来的结果重新排序.
     * 
     * @param array $data
     * @param int $pid 
     * @return array
     */
    public function getPrivilegeTree($data, $pid = 0){
        static $result = array();
        foreach($data as $v){
            if($v['pid']==$pid){
                $result[] = $v;
                $this->getPrivilegeTree($data,$v['id']);//递归调用
            }
        }
        return $result;
    }

       /**
     * 获取某个菜单下所有的权限,只取出菜单的id数组集合
     * @staticvar array $result
     * @param type $pid
     * @return array
     */
    public function getChildPrivilege($pid = array()){
        static $result = array();
        $where['pid'] = array('in',$pid);
        $where['status'] = config('normal_status');
        $data = $this->where($where)->select();
        foreach($data as $v){
            $pids[] = $v['id'];
            $result[] = $v['id'];
        }
        if(!empty($pids)){
            $this->getChildPrivilege($pids);    //递归调用
        }
        return $result;
    }


}