<?php
namespace web\article\controller;
use think\Controller;
use web\extra\controller\Base;
use think\Db;
use think\Request;
use think\Cookie;//cookie类

/**
 * 殡葬百科控制器
 * author heqingyu;
 * date   17/7/17 17:04:00;
 */
class Article extends Base{  
    
    public function _initialize(){
        //用户登录信息name
        $member_name = decode(session('name'));
        $this->assign('member_name',$member_name);
        //百科导航信息
        $where['is_show'] = config('normal_status');
        $data = Db::name('Category')->where($where)->column('id,name,pid');
        $articletree = $this->articletree($data,config('article'));
        $this->assign('articletree',$articletree);
        
        //用于导航选中
        $top_cid = input('cid');
        if(!empty($top_cid)){
            if($data[$top_cid]['pid'] != 0){
                if($data[$top_cid]['pid'] != config('article')){
                    $pid = $data[$top_cid]['pid'];
                    $top_cid = $data[$pid]['id'];
                }
            }
        }
        $this->assign('top_cid',$top_cid);
        
        //推荐关键词
        $introarray = array();
        $intro_province_id = decode(cookie::get('ip_region_id'));
        $introdata = Db::name("Introduce")->field('name,province_id,sort,url')->order('sort desc')->select();
        foreach($introdata as $val){
            $tmpintrodata = explode(',',$val['province_id']);
            if(in_array($intro_province_id,$tmpintrodata)){
                $introarray[] = $val;
            }
        }
        $introarraydata = array_slice($introarray,0,5);//获取数组长度
        $this->assign('introdata',$introarraydata);
        
    }
    private function articletree($data,$id){
        $result = array();
        foreach($data as $val){
            if($val['pid'] == $id ){
                $val['child'] = array();
                $val['child'] = $this->articletree($data,$val['id']);
                $result[] = $val;
            }
            continue;
        }
        return $result;
    }
    /*
     * 殡葬百科首页
     * @param   void;
     * @return  void;
     */
    public function index(){
        //热点聚焦
        $hotWhere['status'] = config('normal_status');
        $hotWhere['hot_focus'] = config('normal_status');
        $hotdata = Db::name('News')->where($hotWhere)->order('published_time DESC,sort DESC')->limit(4)->field('id,title,summary,category_id')->select();
        $this->assign('hotdata',$hotdata);
        //轮播图
        $picWhere['status'] = config('normal_status');
        $picWhere['recommend'] = config('normal_status');
        $picdata = Db::name('News')->where($picWhere)->order('published_time DESC,sort DESC')->limit(4)->field('id,title,image_url,category_id')->select();
        $this->assign('picdata',$picdata);
        //91头条
        $companyWhere['status'] = config('normal_status');  
        $companyWhere['category_id'] = config('article_91_headline');
        $companydata = Db::name('News')->where($companyWhere)->order('published_time DESC,sort DESC')->limit(6)->field('id,title,image_url,category_id,summary')->select();
        $this->assign('companydata',$companydata);
        //企业软文
        $culWhere['status'] = config('normal_status');  
        $culWhere['category_id'] = config('article_com_culture');
        $culdata = Db::name('News')->where($culWhere)->order('published_time DESC,sort DESC')->limit(8)->field('id,title,thumb_url,category_id,published_time,hits,summary')->select();
        $this->assign('culdata',$culdata);
        //行业新闻
        $professionWhere['status'] = config('normal_status');  
        $professionWhere['category_id'] = config('article_industry_dynamic');
        $professiondata = Db::name('News')->where($professionWhere)->order('published_time DESC,sort DESC')->limit(8)->field('id,title,category_id')->select();
        $this->assign('professiondata',$professiondata);
        //dump($culdata);die;
        //政策法规
        $lawsWhere['status'] = config('normal_status');  
        $lawsWhere['category_id'] = config('article_laws_regulations');
        $lawsdata = Db::name('News')->where($lawsWhere)->order('published_time DESC,sort DESC')->limit(8)->field('id,title,category_id')->select();
        $this->assign('lawsdata',$lawsdata);  
        
        //行业新闻和政策法规
        $prolawWhere['status'] = config('normal_status');  
        $prolawWhere['category_id'] = array('in',array(config('article_industry_dynamic'),config('article_laws_regulations'),config('article_industry_information')));
        $prolawdata = Db::name('News')->where($prolawWhere)->order('published_time DESC,sort DESC')->limit(3)->field('id,title,category_id,image_url')->select();
        $this->assign('prolawdata',$prolawdata);
        //dump($prolawdata);die;
         //风水文化
        $fengshuiWhere['status'] = config('normal_status');  
        $fengshuiWhere['category_id'] = config('article_fengshui_culture');
        $fengshuidata = Db::name('News')->where($fengshuiWhere)->order('published_time DESC,sort DESC')->limit(8)->field('id,title,image_url,category_id')->select();
        $this->assign('fengshuidata',$fengshuidata);
        //dump($fengshuidata);die;
        //白事常识
        $senseWhere['status'] = config('normal_status');  
        $senseWhere['category_id'] = config('article_sense');
        $sensedata = Db::name('News')->where($senseWhere)->order('published_time DESC,sort DESC')->limit(5)->field('id,title,thumb_url,category_id,summary')->select();
        $this->assign('sensedata',$sensedata);
        //丧葬习俗
        $cusWhere['status'] = config('normal_status');  
        $cusWhere['category_id'] = config('article_burial_custom');
        $cusdata = Db::name('News')->where($cusWhere)->order('published_time DESC,sort DESC')->limit(8)->field('id,title,category_id')->select();
        $this->assign('cusdata',$cusdata);
        //祭祀习俗
        $jsWhere['status'] = config('normal_status');  
        $jsWhere['category_id'] = config('article_sacrifice_custom');
        $jsdata = Db::name('News')->where($jsWhere)->order('published_time DESC,sort DESC')->limit(8)->field('id,title,category_id')->select();
        $this->assign('jsdata',$jsdata);
        //传统节日
        $festivalWhere['status'] = config('normal_status');  
        $festivalWhere['category_id'] = config('article_traditional_festival');
        $festivaldata = Db::name('News')->where($festivalWhere)->order('published_time DESC,sort DESC')->limit(22)->field('id,title,image_url,category_id')->select();
        $festivaldataone = array_slice($festivaldata,0,11);
        $festivaldatatwo = array_slice($festivaldata,11,11);
        //dump($festivaldatatwo);die;
        $this->assign('festivaldataone',$festivaldataone);
        $this->assign('festivaldatatwo',$festivaldatatwo);
        //生命礼赞
        $lifeWhere['status'] = config('normal_status');  
        $lifeWhere['category_id'] = array('in',array(config('article_life_story'),config('article_life_sentiment'),config('article_cemetry_story'),config('article_lucky_celebrity')));
        $lifedata = Db::name('News')->where($lifeWhere)->order('published_time DESC,sort DESC')->limit(8)->field('id,title,image_url,category_id')->select();
        $this->assign('lifedata',$lifedata);
        //考古
        $kgWhere['status'] = config('normal_status');  
        $kgWhere['category_id'] = config('article_kg_culture');
        $kgdata = Db::name('News')->where($kgWhere)->order('published_time DESC,sort DESC')->limit(5)->field('id,title,thumb_url,category_id,summary')->select();
        $this->assign('kgdata',$kgdata);
        //SEO部分
        $seo = array(
            'seo_title'=>'殡葬百科_殡葬新闻_墓地风水_购墓技巧-91搜墓网',
            'seo_keywords'=>'殡葬新闻,殡葬政策,祭祀习俗,墓地风水,丧葬民俗文化，办白事丧事常识',
            'seo_description'=>'91搜墓网殡葬百科网罗各地殡葬新闻报道，提供各地区祭祀风俗、墓地风水选择，办白事流程常识等内容！91搜墓网提供最前沿的殡葬法规政及相关的管理条约资讯！91搜墓网服务热线：400-618-9191。'
        );
        $this->assign('seo',$seo);
        
        return $this->fetch();
    }
    /*
     * 殡葬百科列表页
     * @param   void;
     * @return  void;
     */
    public function listbox(){
        $cid = input('cid');
        
        $catWhere['is_show'] = config('normal_status');
        $catdata = Db::name('Category')->where($catWhere)->column('id,name,pid');
        $cidtree = $this->getcategoryId($catdata,$cid);
        $cidtree[] = $cid;
        //面包屑部分
        $title = $catdata[$cid];
        //列表内容
        $newWhere['category_id'] = array('in',$cidtree);
        $newWhere['status'] = config('normal_status');
        $newWhere['published_time'] = array('elt',time());
        $data = Db::name('News')->where($newWhere)->order('top desc,published_time desc')->paginate(config('page_size'),false);
        $page = $data->render();
        //热门资讯
        $informationWhere['status'] = config('normal_status');
        $informationWhere['published_time'] = array('elt',time());
        $informationdata = Db::name('News')->where($informationWhere)->order('hits DESC')->limit(5)->field('id,title,thumb_url')->select();
        //dump($informationdata);die;
        //热门文章
        $hotWhere['status'] = config('normal_status');
        $hotWhere['published_time'] = array('elt',time());
        $hotdata = Db::name('News')->where($hotWhere)->order('hits DESC')->limit(6)->field('id,title')->select();
        //dump($hotdata);die;
        //SEO部分
        $seoWhere['category_id'] = $cid;
        $seoWhere['type'] = config('seo_type.news_class');
        $seoWhere['status'] = config('normal_status');
        $seo = Db::name('Seo')->field('seo_title,seo_keywords,seo_description')->where($seoWhere)->find();

        $this->assign([
            'cid'  =>  $cid,
            'seo'  =>  $seo,
            'title'=>  $title,
            'page' =>  $page,
            'data' =>  $data,
            'hotdata' => $hotdata,
            'informationdata' => $informationdata
            ]);
        return $this->fetch();
       
    }
    
    /*
     * 殡葬百科详情页
     * @param   void;
     * @return  void;
     */
    public function detail(){
        $id = input('id');
        
        //详情
        Db::name('News')->where('id', $id)->setInc('hits');
        $data = Db::name('News')->where('id',$id)->find();
        //上一篇
        $cateWhere['is_show'] = config('normal_status');
        $categorydata = Db::name('Category')->where($cateWhere)->column('id,name,pid');
        //$category = $this->getcategoryId($categorydata,$data['category_id']);
        //$category[] = $data['category_id'];
        //dump($category);die;
        $where['category_id'] = $data['category_id'];
        $where['status'] = config('normal_status');
        $where['published_time'] = array('gt',$data['published_time']);
        $up = Db::name('News')->where($where)->order('top asc,published_time asc')->field('title,id')->find();
        //dump($after);die;
        //下一篇
        $where['published_time'] = array('lt',$data['published_time']);
        $after = Db::name('News')->where($where)->order('top desc,published_time desc')->field('title,id')->find();
        //dump($after);dump($up);die;
        //最新资讯
        $informationWhere['status'] = config('normal_status');
        $informationWhere['published_time'] = array('elt',time());
        $informationdata = Db::name('News')->where($informationWhere)->order('hits DESC')->limit(5)->field('id,title,thumb_url')->select();

        //热门文章
        $hotWhere['status'] = config('normal_status');
        $hotWhere['published_time'] = array('elt',time());
        $hotdata = Db::name('News')->where($hotWhere)->order('hits DESC')->limit(6)->field('id,title')->select();
        
        preg_match_all('/src="(.*)"/U',$data['content'],$res);
        $count = count($res[1]);
        if($count > 0){
            for ($i=0; $i < $count; $i++) { 
                // 拼接图片路径前缀
                $imgUrl[] = str_replace('/public','',$res[1][$i]);
            }
            $data['content'] = str_replace($res[1], $imgUrl, $data['content']);
        }
        //seo
        $seo  = array('seo_title'=>$data['seo_title'],'seo_keywords'=>$data['seo_keywords'],'seo_description'=>$data['seo_description']);
        //用于导航选中
        if($categorydata[$data['category_id']]['pid'] == config('article')){
            $top_cid = $data['category_id'];
        }else{
            $pid = $categorydata[$data['category_id']]['pid'];
            $top_cid = $categorydata[$pid]['id'];
        }
        $this->assign('top_cid',$top_cid);

        $this->assign([
            'informationdata' => $informationdata,
            'hotdata'         => $hotdata,
            'title'           => $categorydata[$data['category_id']],
            'data'            => $data,
            'after'           => $after,
            'up'              => $up,
            'seo'             => $seo,
        ]);
        return $this->fetch();
       
    }
    /**
     *获取该分类下子所有分类ID
     *@input $data;//分类数据
     *@input $pid;//分类ID
     *@return array;
     */
    private function getcategoryId($data,$pid){
        static $result = array();
        foreach($data as $val){
            if($val['pid'] == $pid){
                $result[] = $val['id'];
                $this->getcategoryId($data,$val['id']);
            }
        }
        return $result;
    }
}