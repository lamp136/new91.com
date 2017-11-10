<?php
namespace web\app\controller;
use think\Controller;
use think\Db;
use think\controller\JsonRpc;
/**
 * 对外提供的数据接口
 * 新闻部分数据有四类：行业新闻、企业软文、政策法规
 *array('total'=>100, data=>array());
 *
 * @author hgy
 *
 */
class HgyApi extends JsonRpc
{

	public function index(){

	}
	/**
	 * 获取行业新闻数据
	 *
	 * @param number $page     当前页数，默认0，即为第一页
	 * @param number $pagesize 每页数据的条数，默认0， 即为：10
	 *
	 * @return json string
	 */
	public function hyxw($page=0, $pagesize=0) {
		$currentPage = (int)$page;
		$pageSize = (int)$pagesize;
		if (empty($currentPage)) {
			$currentPage = 1;
		}
		if (empty($pageSize)) {
			$pageSize = config('page_size');
		}
		$category = config('article_industry_dynamic');

		$ret = $this->_getNews($category,$currentPage, $pageSize);
		return json_encode($ret);
	}
	/**
	 * 获取企业软文数据
	 *
	 * @param number $page     当前页数，默认0，即为第一页
	 * @param number $pagesize 每页数据的条数，默认0， 即为：10
	 *
	 * @return json string
	 */
	public function qyrw($page=0, $pagesize=0) {
		$currentPage = (int)$page;
		$pageSize = (int)$pagesize;
		if (empty($currentPage)) {
			$currentPage = 1;
		}
		if (empty($pageSize)) {
			$pageSize = config('page_size');
		}
		$category = config('article_com_culture');

		$ret = $this->_getNews($category,$currentPage, $pageSize);

		return json_encode($ret);
	}
	/**
	 * 获取政策法规数据
	 *
	 * @param number $page     当前页数，默认0，即为第一页
	 * @param number $pagesize 每页数据的条数，默认0， 即为：10
	 *
	 * @return json string
	 */
	public function zcfg($page=0, $pagesize=0) {
		$currentPage = (int)$page;
		$pageSize = (int)$pageSize;
		if (empty($currentPage)) {
			$currentPage = 1;
		}
		if (empty($pageSize)) {
			$pageSize = config('page_size');
		}
		$category = config('article_laws_regulations');

		$ret = $this->_getNews($category,$currentPage, $pageSize);

		return json_encode($ret);
	}
	/**
	 * 新闻数据获取的核心方法
	 *
	 * @param int $category    分类ID
	 * @param int $currentPage 当前页数
	 * @param int $pageSize    每页的数据条数
	 *
	 * @return  array
	 */
	private function _getNews($category, $currentPage, $pageSize) {
		$where['category_id'] = $category;
		$where['status'] = config('normal_status');
		$where['published_time'] = array('ELT', time());
		//获取新闻数量
		$total = Db::name('News')->where($where)->count();
		$lists = array();
		if ($total > 0) {
			//分页数据
			$start = ($currentPage-1)*$pageSize;
			$fields = 'id, title, summary, published_time, source';
			$lists = Db::name('News')->field($fields)->where($where)->order('published_time DESC')->limit($start, $pageSize)->select();
		}

		$result = array('total'=>$total, 'data'=>$lists);
		return $result;
	}
	/**
	 * 根据新闻的ID获取具体的新闻数据
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function getById($id) {
		$id = (int)$id;
		$ret['total'] = 0;
		$ret['data'] = '';
		if ($id) {
			$where['status'] = config('normal_status');
			$where['id'] = $id;
			$fields = 'id, title, summary, published_time, content,source';
			$newsInfo = Db::name('news')->field($fields)->where($where)->find();
			if ($newsInfo) {
				$ret['total'] = 1;
				$ret['data'] = $newsInfo;
			}
		}

		return json_encode($ret);
	}
}


