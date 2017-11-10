<?php
namespace back\extra\model;
use think\Model;

class Category extends Model
{
    /**
     * 将数组组合成一个有规律的数组，将其下面的分类放在父类的child元素中
     * @param arrray $data
     * @param int $pid
     * @return array
     */
    public function categoryChlidTree($data,$pid=0){
        $result = [];
        foreach ($data as $v){
            if($v['pid']==$pid){
                $v['children'] = $this->categoryChlidTree($data,$v['id']);
                $result[] = $v;
            }
            
        }
        return $result;
    }
    
    
    /**
     * 格式化分类
     * 
     * @param array $data
     * @param int $pid
     * @param int $level
     * @return array
     */
    public function categoryTree($data,$pid=0,$level=0){
        static $result = array();
        foreach($data as $v){
            if($v['pid'] == $pid){
                $v['level'] = $level;
                $result[] = $v;
                $this->categoryTree($data,$v['id'], $level+1);//递归调用
            }
        }
        return $result;
    }
    
    public function getChildCat($pid){
        static $result = [];
        $where['pid'] = ['in',$pid];
        $data = $this->where($where)->select();
        foreach($data as $v){
            $pids[] = $v['id'];
            $result[] = $v['id'];
        }
        if(!empty($pids)){
            $this->getChildCat($pids);    //递归调用
        }
        return $result;
    }
}