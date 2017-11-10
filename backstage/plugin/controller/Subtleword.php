<?php
namespace back\plugin\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use back\extra\model\SubtleWords;

/**
 * 敏感词过滤词库
 * 
 */
class Subtleword extends Base
{
    /**
     * 词库列表
     */
    public function index(){
        $pageSize = Config('page_size');
        $inputMan = $this->getBusinessMen(false,false);
        $list = Db::name('SubtleWords')->order('created_time desc')->paginate($pageSize,false);
        $page = $list->render();
        $this->assign('inputMan',$inputMan);
        $this->assign('list',$list);
        $this->assign('page',$page);
         return $this->fetch();
        
    }

    /**
     * 添加过滤词
     */
    public function add(){
        $postData = input('post.');
        $str = rtrim($postData['keywords'],'；');
        $data =explode('；',$str);
        foreach($data as $v){
            $list[]= [
                'admin_id'=>session('admin_id'),
                'created_time'=>time(),
                'keywords'=>$v,
                'status'=>config('normal_status')

            ];
        }
        $sub = new SubtleWords;
        $final = $sub->saveAll($list);
        if($final){
            $result['flag'] = 1;
            $result['msg'] = '操作成功';
        }
        echo json_encode($result);
    }

    /**
     * 编辑
     */
    public function edit(){
        if(Request::instance()->isPost()){
            $postData = input('post.');
            $postData['updated_time'] = time();
            $postData['admin_id'] = session('admin_id');
            if(Db::name('SubtleWords')->update($postData)){
                $result['flag'] = 1;
                $result['msg'] = '操作成功';
            }
        }else{
            $getInfo = input('get.');
            $id = $getInfo['id'];
            $result = array('flag'=>0,'data'=>array());
            if(!empty($id)){
                $data = Db::name('SubtleWords')->find($id);
                if(!empty($data)){
                    $result['flag'] = 1;
                    $result['data'] = $data;
                }
            }
        }
        echo json_encode($result);
    }



    /**
     * 删除
     */
    public function del(){
    
        if(Request::instance()->isPost()){
            $postInfo = input('post.');
            $result = array('flag'=>0,'msg'=>'操作失败');
            $data['id'] = $postInfo['id'];
            if($postInfo['act']=='del'){
                // 状态改为-1
                $data['status'] = config('delete_status');
            }else if($postInfo['act']=='enable'){
                // 状态改为1
                $data['status'] = config('normal_status');
            }
            if(Db::name('SubtleWords')->update($data)){
                $result['flag'] = 1;
                $result['msg'] = '操作成功';
            }
            echo json_encode($result);
        }
    }

    /**
     * 导入词库文件
     */

    public function uploadFile(){  
      if(Request::instance()->isPost()){  
        $files = request()->file('txt_file'); 
        $path = './public/uploadfile/subtleword/'; // 获取表单提交过来的文件   
        $error = $_FILES['txt_file']['error'];
        if(!$error){  
          $dir = $path;  
          // 验证文件并移动到框架应用根目录/public/uploads/ 目录下  
          $info = $files->validate(['size'=>3145728,'ext'=>'xls,xlsx,csv'])->rule('uniqid')->move($dir); 
          /*判断是否符合验证*/  
          if($info){    //  符合类型  
            $ext = $info->getExtension();// 获取后缀
            $filename = $path.$info->getSaveName();// 文件保存名字

            // vendor('PHPExcel.PHPExcel');
            vendor('PHPExcel.PHPExcel.IOFactory');
            vendor('PHPExcel.PHPExcel.Reader.Excel5');// Excel5:xls;Excel2007:xlsx;
            vendor('PHPExcel.PHPExcel.Reader.Excel2007');// Excel5:xls;Excel2007:xlsx;
            $reader = \PHPExcel_IOFactory::createReader('Excel5');//设置以Excel5格式
            if($ext == 'xlsx'){
                $reader = \PHPExcel_IOFactory::createReader('Excel2007');//设置以Excel2007格式
            }
            $PHPExcel = $reader->load($filename); // 载入excel文件  
            $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表  
            $highestRow = $sheet->getHighestRow(); // 取得总行数  
            $highestColumm = $sheet->getHighestColumn(); // 取得总列数  
                /** 循环读取每个单元格的数据 */  
                for ($row = 2; $row <= $highestRow; $row++){//行数是以第1行开始，这里示例中excel有3列字段  
                  $keywords = $sheet->getCell('A'.$row)->getValue(); 
                  // $admin_id = $sheet->getCell('B'.$row)->getValue(); 
                  // $status = $sheet->getCell('C'.$row)->getValue();  
                 
                  $data = array();  
                  $data = array(  
                      'keywords'  =>  $keywords,  
                      'admin_id'  =>  session('admin_id'),  
                      'created_time'    =>  time(),  
                      'status'    =>  config('normal_status'),  
                    );  
                   $ret =  Db::name('SubtleWords')->insert($data);  
                }  
                if($ret){
                    // unlink($filename);
                    $result['flag'] = 1;
                    $result['msg'] = '数据上传成功';
                }else{
                	$result['flag'] = 0;
                    $result['msg'] = '数据上传失败';
                } 
              } else{ //  不符合类型业务  
                $result['flag'] = 2;
                $result['msg'] = '请选择上传3MB内的excel表格文件...';
              }  
            }else{  
              $result['flag'] = 3;
              $result['msg'] = '请选择需要上传的文件...';  
            }  
            echo json_encode($result);
        }  
    }  
}