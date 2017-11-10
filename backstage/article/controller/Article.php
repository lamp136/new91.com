<?php
namespace back\article\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use back\extra\model\News;
use back\extra\model\Category;

/**
 * 新闻管理类
 */
class Article extends Base
{
    /**
     * 行业新闻
     */
    public function inNews(){
        $categoryId = config('article_industry_dynamic');
        $this->assign('name','inNews');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }
    /**
     * 政策法规
     */
    public function policyLaws(){
        $categoryId = config('article_laws_regulations');
        $this->assign('name','policyLaws');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }
    /**
     * 企业软文
     */
    public function comCulture(){
        $categoryId = config('article_com_culture');
        $this->assign('name','comCulture');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 人生感悟
     */
    public function lifeFeel(){
        $categoryId = config('article_life_sentiment');
        $this->assign('name','lifeFeel');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 陵园故事
     */
    public function cemeteryStory(){
        $categoryId = config('article_cemetry_story');
        $this->assign('name','cemeteryStory');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 福地名人
     */
    public function luckyCelebrity(){
        $categoryId = config('article_lucky_celebrity');
        $this->assign('name','luckyCelebrity');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }
    /**
     * 丧葬习俗
     */
    public function funeralCustom(){
        $categoryId = config('article_burial_custom');
        $this->assign('name','funeralCustom');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 祭祀习俗
     */
    public function feteActive(){
        $categoryId = config('article_sacrifice_custom');
        $this->assign('name','feteActive');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 91头条
     */
    public function copKnowledge(){
        $categoryId = config('article_91_headline');
        $this->assign('name','copKnowledge');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 考古文化
     */
    public function kgCulture(){
        $categoryId = config('article_kg_culture');
        $this->assign('name','kgCulture');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 风水文化
     */
    public function ceverts(){
        $categoryId = config('article_fengshui_culture');
        $this->assign('name','ceverts');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 白事常识
     */
    public function funeral(){
        $categoryId = config('article_sense');
        $this->assign('name','funeral');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }
    /**
     * 传统节日
     */
    public function traditionalFestival(){
        $categoryId = config('article_traditional_festival');
        $this->assign('name','traditionalFestival');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }



     /**
     * 购墓须知
     */
    public function noticeofTomb(){
        $categoryId = config('article_noticeoftomb');
        $this->assign('name','noticeofTomb');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 鲜花祭品
     */
    public function flower(){
        $categoryId = config('article_flower');
        $this->assign('name','flower');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

     /**
     * 三大鬼节
     */
    public function zomb(){
        $categoryId = config('article_zomb');
        $this->assign('name','zomb');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

     /**
     * 迁坟讲究
     */
    public function qianfen(){
        $categoryId = config('article_qianfen');
        $this->assign('name','qianfen');
        $this->assign('categoryId',$categoryId);
        $this->_publicCore($categoryId);
        return $this->fetch('index');
    }

    /**
     * 列表的核心方法
     * @param Int $cid
     * @return void
     */
    private function _publicCore($cid){
        $getdata = input('get.');
        $pageSize = config('page_size');
        $where['category_id'] = $cid;
        if(!empty($getdata['title'])){
            $where['title'] = array('like','%'.$getdata['title'].'%');     
        }
        $nowPage = isset($getdata['page']) ? $getdata['page'] : 1;
        $list = News::with('catname,nickname')->where($where)->order("created_time desc")->paginate($pageSize,false,['query' => $getdata]);
        // 通过分类ID查询分类名以及父类名
        $category = Db::name('category')->where('id',$cid)->field('pid,name')->find();
        $pidName = Db::name('category')->where('id',$category['pid'])->field('name')->find();
      
        if($category['pid']!=1){
            $this->assign('firstCategory',$pidName['name']);
            $this->assign('secondCategory',$category['name']);
        }else{
            $this->assign('firstCategory',$category['name']);
            $this->assign('secondCategory','');
        }
            
        //截取标题为15个字节
        foreach($list as $k=>$v){
            $list[$k]['titles'] = msubstr($v['title'],0,15,'utf-8',true);
        }
        $login_name = session('login_name');
        $page = $list->render();
        $this->assign('nowPage',$nowPage);
        $this->assign('page',$page);
        $this->assign('login_name',$login_name);
        $this->assign('list',$list);
    }

    /**
     * 添加新闻页
     */
    public function add(){
        $info = input('get.');
        $arr = array(config('article_noticeoftomb'),config('article_flower'),config('article_zomb'),config('article_qianfen'));
        $province = Db::name('region')->where('pid='.config('china_num').' and status='.config('normal_status'))->column('id,name');

        $list = Db::name('category')->field('id,pid,name,is_show,sort')->select();
        $categoryModel = new Category();
        if(in_array($info['categoryId'],$arr)){
            $categoryList = $categoryModel->categoryTree($list,$pid=config('helpcenter'));
        }else{
            $categoryList = $categoryModel->categoryTree($list,$pid=config('article'));
        }
        
        //判断分类下是否有子类,如果有子类不能再该类上添加新闻
        foreach($list as $k=>$v){
            $cid[]=$v['pid'];
        }
        foreach($categoryList as $k=>$v){
            if(in_array($v['id'],$cid)){
                $categoryList[$k]['last'] = 1;
            }else{
                $categoryList[$k]['last'] = 0;
            }
        }
        
        $this->crumbs();//左侧菜单选中
        $this->assign('province',$province);
        $this->assign('cate',$categoryList);
        $this->assign('firstCategory',$info['firstCategory']);
        $this->assign('secondCategory',$info['secondCategory']);
        $this->assign('cateId',$info['categoryId']);
        $this->assign('name',$info['name']);
        return $this->fetch('add');
    }

    /**
     * 通过ajax传过来的省份查找商家名称
     */
    public function getcemetery(){
        $provinceId = input('provinceId');
        $array = array('flag'=>0,'data'=>'');
        if($provinceId){
            $data = Db::name('store')->where("province_id=".$provinceId)->column('id,name');
            if($data){
                $array = array('flag'=>1,'data'=>$data);
            }
        }
        echo json_encode($array);
    }

    /**
     * 添加新闻的公共方法
     */
    public function save(){
        $data = input('post.');
        $info = $data['info'];
        if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){
            $imagesRet = uploadOne('image',Config('news_image'),array(array(622,388,6)));
            if($imagesRet['ok'] == 0){
                $this->error($imagesRet['error']);
            }else{
                $info['image_url'] = $imagesRet['images'][0];
                $info['thumb_url'] = $imagesRet['images'][1];   
            }
        }
   
        $name = $data['name'];
        $cateObj = Db::name('category')->where('id='.$info['category_id'])->find();
        $info['category_pid'] = $cateObj['pid'];

        if(isset($info['published_time']) && !empty($info['published_time'])){
            $info['published_time'] = strtotime($info['published_time']);
        }

        $info['created_time'] = time();
        $info['updated_time'] = time();
        $info['admin_id'] = session('admin_id');
        
        $res = Db::name('news')->insert($info);
        if($res){
            // 清除缓存
            // F(C('F_CACHE_KEY'),null,C('DATA_CACHE_PATH'));
            // S(C('S_CACHE_KEY'),null);
            $this->success('添加文章成功',url("/article/Article/".$name));
        }else{
            $this->error('添加失败');
        }
        
    }

    /**
     * 新闻修改页
     */
    public function edit(){
        $data = input('get.');
        $news = Db::name('news')->where('id='.$data['newsId'])->find();
    
        //获取地区信息
        $province = Db::name('region')->where('pid='.config('china_num'))->column('id,name');
        //获取分类信息
        $list = Db::name('category')->field('id,pid,name,is_show,sort')->select();
        $categoryModel = new Category();
        //获取帮助中心配置
        $arr = array(config('article_noticeoftomb'),config('article_flower'),config('article_qianfen'),config('article_zomb'));
         if(in_array($data['categoryId'],$arr)){
            $categoryList = $categoryModel->categoryTree($list,$pid=config('helpcenter'));
        }else{
            $categoryList = $categoryModel->categoryTree($list,$pid=config('article'));
        }
        //判断分类下是否有子类,如果有子类不能再该类上添加新闻
        foreach($list as $k=>$v){
            $cid[]=$v['pid'];
        }
        foreach($categoryList as $k=>$v){
            if(in_array($v['id'],$cid)){
                $categoryList[$k]['last'] = 1;
            }else{
                $categoryList[$k]['last'] = 0;
            }
        }
        //根据文章的province_id查找商家名称
        if(!empty($news['province_id'])){
            $storeName = Db::name('store')->where('status!=-1 and province_id='.$news['province_id'])->column('id,name');
            $this->assign('storeName',$storeName);

        }
        $this->crumbs();//左侧菜单选中
        $this->assign('province',$province);
        $this->assign('cateId',$data['categoryId']);
        $this->assign('cate',$categoryList);
        $this->assign('news',$news);
        $this->assign('nowPage',$data['nowPage']);
        $this->assign('firstCategory',$data['firstCategory']);
        $this->assign('secondCategory',$data['secondCategory']);
        $this->assign('name',$data['name']);
        return $this->fetch('edit');
    }

    /**
     * 保存新闻修改
     */
    public function update(){
        $data = input('post.');
       
        if($data){
            $info = $data['info'];
            $newsId = $data['news_id'];
            $name = $data['name'];
            $nowPage = $data['nowPage'];
    
            if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){

                $imagesRet = uploadOne('image',config('news_image'),array(array(622,388,6)));
                if($imagesRet['ok'] == 0){
                    $this->error($imagesRet['error']);
                }else{
                    $delImg = Db::name('news')->where('id='.$newsId)->field('image_url,thumb_url')->find();
                    if($delImg['image_url']){   
                        if(file_exists('.'.$delImg['image_url'])){
                            unlink('.'.config('public_path').$delImg['image_url']);
                            unlink('.'.config('public_path').$delImg['thumb_url']);
                        }
                    }
                    $info['image_url'] = $imagesRet['images'][0];
                    $info['thumb_url'] = $imagesRet['images'][1];   
                }
            }
            $findOne = Db::name('category')->where('id='.$info['category_id'])->find();
            $info['category_pid'] = $findOne['pid'];
            //更新发布时间
            if(isset($info['published_time']) && !empty($info['published_time'])){
                $info['published_time'] = strtotime($info['published_time']);
            }
            $info['updated_time'] = time();

            $res = Db::name('news')->where('id='.$newsId)->update($info);
            if($res){
                // 清除缓存
                // F(C('F_CACHE_KEY'),null,C('DATA_CACHE_PATH'));
                // S(C('S_CACHE_KEY'),null);
                $this->success('修改成功', url('/article/Article/'.$name, 'page='.$nowPage));

            }else{
                $this->error('修改失败');
            }
        }
    }

    /**
     * 删除/开启新闻
     */
    public function delnews(){
        $id = input('id');
        $token = input('token');
        $array = array('flg'=>0,'msg'=>'操作失败');

        if($token == 'del'){
            $result = Db::name('news')->where('id',$id)->update(['status'=>config('delete_status')]);
        }else if($token == 'start'){
            $result = Db::name('news')->where('id',$id)->update(['status'=>config('default_status')]);
        }
        if($result){
            $array = array('flg'=>1,'msg'=>'操作成功');
        }
        echo json_encode($array);
    }

    //左侧导航选中
    private function crumbs(){
        $server = input('server.');
        $httpHost = 'http://'.$server['HTTP_HOST'].'/';
        $preUrl = $server['HTTP_REFERER'];
        if(input('returnurl')){
            $preUrl = input('returnurl');
        }
        $path = str_replace('.html','',str_replace($httpHost,'',$preUrl));
        if(strpos($path,'page')){
            $cutUrl = explode('?',$path);
            $path = $cutUrl[0];
        }

        $this->assign('path',$path);
    }

}