<?php
namespace web\extra\model;
use think\Model;

class News extends Model
{
    /**
     * 获取某个类别下的指定条数的文章
     *
     * @param  int   $categoryid  新闻类别
     * @param  int   $num         条数
     *
     * @return array              结果
     */
    public function getNewsByCategory($categoryid,$num){
        $where['category_id'] = $categoryid;
        $where['status'] = config('normal_status');
        $where['published_time'] = ['elt', time()];
        $defaultFields = 'title,id';
        if (empty($num)) {
            $data = $this->where($where)->field($defaultFields)->order('created_time desc')->select();
        } else {
            $data = $this->where($where)->limit($num)->field($defaultFields)->order('created_time desc')->select();
        }
        return $data;
    }
}