<?php
namespace back\store\controller;
use back\extra\controller\Base;
use back\extra\model\DataTrack;
use back\extra\model\DataTrackMsg;
use think\Controller;
use think\Db;

class DataTrace extends Base
{
    /**
     * 列表
     * @return void
     */
    public function index(){
        $input = input('get.');
        $regions = Db::name('region')->where(['status' => config('normal_status')])->field('id,name,pid')->select();
        $province = [];
        $city = [];
        $where = [];
        $this->_params();
        if($regions){
            foreach ($regions as $val) {
                if($val['pid'] == config('china_num')){
                    // 省份
                    $province[] = $val;
                }else if(isset($input['search_province']) && !empty($input['search_province'])){
                    if($val['pid'] == $input['search_province']){
                        // 市区
                        $city[] = $val;
                    }
                }
            }
        }
        if(isset($input['search_company']) && !empty($input['search_company'])){
            $params['search_company'] = $input['search_company'];
            $where['company'] = ['like','%'.$input['search_company'].'%'];
        }
        if(isset($input['search_province']) && !empty($input['search_province'])){
            $params['search_province'] = $input['search_province'];
            $where['province_id'] = $input['search_province'];
            if(isset($input['search_city']) && !empty($input['search_city'])){
                $params['search_city'] = $input['search_city'];
                $where['city_id'] = $input['search_city'];
            }
        }
        if(isset($input['search_category_id']) && !empty($input['search_category_id'])){
            $params['search_category_id'] = $input['search_category_id'];
            $where['category_id'] = $input['search_category_id'];
        }
        if(isset($input['search_intention']) && !empty($input['search_intention'])){
            $params['search_intention'] = $input['search_intention'];
            $where['intention'] = $input['search_intention'];
        }
        if(isset($input['search_is_system']) && !empty($input['search_is_system'])){
            $params['search_is_system'] = $input['search_is_system'];
            $where['is_system'] = $input['search_is_system'];
        }
        if(isset($input['search_flow_man']) && !empty($input['search_flow_man'])){
            $params['search_flow_man'] = $input['search_flow_man'];
            $where['flow_man'] = $input['search_flow_man'];
        }
        if(session('?admin_id')){
            $user = Db::name('RoleUser')->where(['user_id' => session('admin_id')])->find();
            if(session('login_name') != config('admin_name')){
                if($user['role_id'] == config('business_depart') && $user['role_job_id'] == config('bj_software_depart')){
                    $where['flow_man'] = session('admin_id');
                }
            }
        }

        $traceList = DataTrack::with(['province','city'])->where($where)->order('created_time desc')->paginate(config('page_size'),false,['query' => $input]);
        $page = $traceList->render();
        $this->assign([
            'province'    => $province,
            'city'        => $city,
            'business'    => $this->getBusinessMen(true),
            'allbusiness' => $this->getBusinessMen(false),
            'page'        => $page,
            'traceList'   => $traceList
        ]);
        return $this->fetch();
    }

    private function _params(){
        $input = input('get.');
        $params = [
            'page'               => !empty($input['page']) ? $input['page'] : 1,
            'search_company'     => !empty($input['search_company']) ? $input['search_company'] : '',
            'search_province'    => !empty($input['search_province']) ? $input['search_province'] : '',
            'search_city'        => !empty($input['search_city']) ? $input['search_city'] : '',
            'search_category_id' => !empty($input['search_category_id']) ? $input['search_category_id'] : '',
            'search_intention'   => !empty($input['search_intention']) ? $input['search_intention'] : '',
            'search_is_system'   => !empty($input['search_is_system']) ? $input['search_is_system'] : '',
            'search_flow_man'    => !empty($input['search_flow_man']) ? $input['search_flow_man'] : ''
        ];

        $this->assign('params',$params);
    }

    /**
     * 预览
     * @return [type] [description]
     */
    public function preview(){
        $id = input('get.id');
        $result = ['code' => 0,'data' => ''];
        if($id){
            $fields = 'id,company,is_system,province_id,city_id,intention,category_id,input_time,flow_man,decision_maker,decision_position,decision_phone,affect_maker,affect_position,affect_phone,other_contacts,amount';
            $preview = DataTrack::with(['province','city'])->field($fields)->where('id',$id)->find();
            $business = $this->getBusinessMen(false);
            if($preview){
                $result = ['code' => 1,'data' => $preview,'business' => $business];
            }
        }
        echo json_encode($result);
    }

    /**
     * 添加
     * @return json
     */
    public function add(){
        if(request()->isPost()){
            $input = input('post.');
            $result = ['code' => 0,'msg' => '添加失败'];
            $info = $input['info'];
            $track = $input['track'];
            $info['input_time'] = strtotime($info['input_time']);
            $info['created_time'] = time();
            $info['input_man'] = $info['flow_man'] = session('admin_id');
            $insertId = Db::name('DataTrack')->insertGetId($info);
            if($insertId){
                $traceMsg = [
                    'track_id'     => $insertId,
                    'flow_man'     => session('admin_id'),
                    'message'      => $track['message'],
                    'intention'    => $info['intention'],
                    'created_time' => time(),
                    'status'       => config('normal_status')
                ];
                $addTraceMsg = Db::name('DataTrackMsg')->insert($traceMsg);
                if($addTraceMsg){
                    $result = ['code' => 1,'msg' => '添加成功'];
                }
            }
            echo json_encode($result);
        }else{
            $province = Db::name('region')->where('level',config('normal_status'))->column('id,name');
            $this->_params();
            $this->assign([
                'province' => $province
            ]);
            return $this->fetch();
        }
    }

    /**
     * 编辑
     * @return json
     */
    public function edit(){
        if(request()->isPost()){
            $input = input('post.');
            $info = $input['info'];
            $result = ['code' => 0,'msg' => '修改失败'];
            $info['input_time'] = strtotime($info['input_time']);
            $info['updated_time'] = time();
            $info['input_man'] = $info['flow_man'] = session('admin_id');
            $saveRet = Db::name('DataTrack')->data($info)->update();
            if($saveRet){
                $result = ['code' => 1,'msg' => '修改成功'];
            }
            echo json_encode($result);
        }else{
            $input = input('get.');
            $this->_params();
            $id = $input['id'];
            $province = $city = [];
            if($id){
                $regions = Db::name('region')->where(['status' => config('normal_status')])->field('id,name,pid')->select();
                $info = Db::name('DataTrack')->where('id',$id)->find();
                foreach ($regions as $key => $val) {
                    if($val['pid'] == config('china_num')){
                        $province[$val['id']] = $val['name'];
                    }else if($info['province_id'] != 0 && $val['pid'] == $info['province_id']){
                        $city[$val['id']] = $val['name'];
                    }
                }
                $this->assign([
                    'info' => $info,
                    'province' => $province,
                    'city' => $city
                ]);
            }
            return $this->fetch();
        }
    }

    /**
     * 重复检测
     * @return json
     */
    public function checkRepeat(){
        $input = input('get.');
        $result['code'] = 0;
        $where['company'] = $input['company'];
        if(!empty($input['id'])){
            $where['id'] = ['neq',$input['id']];
        }
        $info = Db::name('DataTrack')->where($where)->field('id')->count();
        if($info > 0){
            $result['code'] = 1;
        }

        echo json_encode($result);
    }

    /**
     * 更改跟踪人
     * @return json
     */
    public function changeFlowMan(){
        $input = input('post.');
        $result = ['code' => 0,'msg'=>'操作失败'];
        if($input['flow_man'] != 0){
            $where['id'] = ['in',$input['id']];
            $ret = Db::name('DataTrack')->where($where)->setField('flow_man',$input['flow_man']);
            if($ret){
                $result = ['code' => 1,'msg'=>'操作成功'];
            }
        }
        echo json_encode($result);
    }

    /**
     * 删除/启用
     * @return json
     */
    public function delete(){
        $input = input('get.');
        $result = ['code' => 0,'msg' => '操作失败'];
        $id = $input['id'];
        if($id){
            $delRet = Db::name('DataTrack')->where('id',$id)->setField('status',$input['status']);
            if($delRet){
                $result = ['code' => 1,'msg' => '操作成功'];
            }
        }
        echo json_encode($result);
    }

    /**
     * 查看跟踪信息
     * @return json
     */
    public function trackMsg(){
        $input = input('get.');
        $result = ['code' => 0];
        $where['track_id'] = $input['track_id'];
        if(session('admin_id') != config('admin_name')){
            $where['flow_man'] = $input['flow_man'];
        }
        $trackMsg = DataTrackMsg::with('admin')->where($where)->field('id,flow_man,message,intention,created_time')->order('created_time desc')->select();
        if(!empty($trackMsg)){
            $result = ['code' => 1,'data' => $trackMsg];
        }
        echo json_encode($result);
    }

    /**
     * 删除跟踪信息
     * @return json
     */
    public function delTrackMsg(){
        $input = input('get.');
        $result = ['code' => 0,'msg' => '删除失败'];
        $id = $input['id'];
        $ret = Db::name('DataTrackMsg')->where('id',$id)->delete();
        if($ret){
            $result = ['code' => 1,'msg' => '删除成功'];
        }
        echo json_encode($result);
    }

    /**
     * 添加跟踪信息
     * @return json
     */
    public function addMsg(){
        if(request()->isPost()){
            $input = input('post.');
            $result = ['code' => 0,'msg' => '添加失败'];
            $data = $input;
            $data['created_time'] = time();
            $data['flow_man'] = session('admin_id');
            $ret = Db::name('DataTrackMsg')->data($data)->insert();
            if($ret){
                $result = ['code' => 1,'msg' => '添加成功'];
            }
            echo json_encode($result);
        }
    }

    /**
     * 导入excel数据
     * @return json
     */
    public function import(){
        $result = ['code' => 0,'msg' => '执行失败'];
        $files = request()->file('trace_excel');
        if(!empty($files)){
            vendor('PHPExcel.PHPExcel.IOFactory');
            vendor('PHPExcel.PHPExcel.Reader.CSV');// csv
            vendor('PHPExcel.PHPExcel.Reader.Excel5');// xls
            vendor('PHPExcel.PHPExcel.Reader.Excel2007');// xlsx
            $tmpFile = $files->getInfo('tmp_name');
            $ext = strrchr($files->getInfo('name'),'.');
            $path = ROOT_PATH . 'public' . DS . 'uploadfile' . DS . config('track_excel') . DS . date('Y_m_d_H_i_s') . $ext;
            $files->validate(['size' => 3145728,'ext' => 'xls,xlsx,csv']);
            $check = $files->check();
            $result = ['code' => 0,'msg' => '文件不符合规范'];
            if($check){
                $info = move_uploaded_file($tmpFile,$path);
                if($info){
                    if($ext == '.csv'){
                        $reader = \PHPExcel_IOFactory::createReader('CSV')
                                    ->setDelimiter(',')  
                                    ->setInputEncoding('GBK')  
                                    ->setEnclosure('"')  
                                    ->setLineEnding("\r\n")  
                                    ->setSheetIndex(0);
                    }else if($ext == '.xls'){
                        $reader = \PHPExcel_IOFactory::createReader('Excel5');
                    }else if($ext == '.xlsx'){
                        $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                    }
                    $objPHPExcel   = $reader->load($path,$encode='utf-8');
                    $sheet         = $objPHPExcel->getSheet(0);
                    $highestRow    = $sheet->getHighestRow();
                    $highestColumn = $sheet->getHighestColumn();

                    for ($i=2; $i < $highestRow+1; $i++) {
                        // 日期
                        $datetime         = $objPHPExcel->getActiveSheet()->getCell('A'.$i)->getValue();
                        // 公司
                        $company          = $objPHPExcel->getActiveSheet()->getCell('B'.$i)->getValue();
                        // 省市
                        $region           = $objPHPExcel->getActiveSheet()->getCell('C'.$i)->getValue();
                        // 意向类型
                        $intention        = $objPHPExcel->getActiveSheet()->getCell('D'.$i)->getValue();
                        // 决策人
                        $decisionMaker    = $objPHPExcel->getActiveSheet()->getCell('F'.$i)->getValue();
                        // 决策人职务
                        $decisionPosition = $objPHPExcel->getActiveSheet()->getCell('G'.$i)->getValue();
                        // 决策人电话
                        $decisionPhone    = $objPHPExcel->getActiveSheet()->getCell('H'.$i)->getValue();
                        // 决策影响人
                        $affectMaker      = $objPHPExcel->getActiveSheet()->getCell('I'.$i)->getValue();
                        // 决策影响人职务
                        $affectPosition   = $objPHPExcel->getActiveSheet()->getCell('J'.$i)->getValue();
                        // 决策影响人电话
                        $affectPhone      = $objPHPExcel->getActiveSheet()->getCell('K'.$i)->getValue();
                        // 是否有系统
                        $isSystem         = $objPHPExcel->getActiveSheet()->getCell('L'.$i)->getValue();
                        // 陵园/殡仪馆规模
                        $scale            = $objPHPExcel->getActiveSheet()->getCell('M'.$i)->getValue();

                        // 处理时间格式
                        $time      = explode('.', $datetime);
                        $stime     = $time[0].'-'.$time[1].'-'.$time[2];
                        $inputTime = strtotime($stime);

                        $type = 0;
                        // 判断商家类型
                        if(strpos($company,'陵园')){
                            $type = config('category_cemetery_id');
                        } else if(strpos($company,'殡仪馆')){
                            $type = config('category_funeral_id');
                        } else if(strpos($company,'殡仪用品和供应商')){
                            $type = config('category_etiquette_id');
                        }  
                        
                        // 获取意向类型
                        $test = substr($intention,0,1);
                        if ($test == 'A') {
                            $intention = 1;
                        }elseif ($test == 'B') {
                            $intention = 2;
                        }elseif ($test == 'C') {
                            $intention = 3;
                        }elseif ($test == 'D') {
                            $intention = 4;
                        }else{
                            $intention = 0;
                        }

                        // 是否有系统
                        $system = substr($isSystem,0,3);
                        if($system == "有"){
                            $isSystem = 2;
                        }elseif ($system == "无") {
                            $isSystem = 1;
                        }elseif ($system == "不") {
                            $isSystem = 3;
                        }elseif ($system == null) {
                            $isSystem = "";
                        }else{
                            $isSystem = 0;
                        }

                        $provinceId  = $ciytId = '';
                        // 获取省\市id
                        $area = explode('省',$region);
                        if(isset($area[1])){
                            $cityName = str_replace(['市','区',' '], '', $area[1]);
                            if($cityName == '恩施土家族苗族自治州'){
                                $cityName = '恩施';
                            }
                            $city = Db::name('region')->where(['name' => ['like','%'.$cityName.'%'],'level' => 2])->field('id,pid,name')->find();
                            if(!empty($city)){
                                $provinceId = $city['pid'];
                                $cityId = $city['id'];
                            }
                        }else{
                            $pName = str_replace(['市','省',' '], '', $area[0]);
                            $data = Db::name('region')->where(['name' => ['like','%'.$pName.'%'],'level' => config('normal_status')])->field('id,name')->find();
                            $provinceId = $data['id'];
                            $cityId = 0;
                        }
                        $insertData[] = [
                            'input_time'        => $inputTime,
                            'company'           => $this->_obj2str($company),
                            'province_id'       => $provinceId,
                            'city_id'           => $cityId,
                            'input_man'         => session('admin_id'),
                            'flow_man'          => session('admin_id'),
                            'intention'         => $intention,
                            'category_id'       => $type,
                            'decision_maker'    => $this->_obj2str($decisionMaker),
                            'decision_position' => $this->_obj2str($decisionPosition),
                            'decision_phone'    => $this->_obj2str($decisionPhone),
                            'affect_maker'      => $this->_obj2str($affectMaker),
                            'affect_position'   => $this->_obj2str($affectPosition),
                            'affect_phone'      => $this->_obj2str($affectPhone),
                            'is_system'         => $isSystem,
                            'scale'             => $this->_obj2str($scale),
                            'created_time'      => time(),
                            'status'            => config('normal_status')
                        ];     
                    }
                    $importRet = Db::name('DataTrack')->insertAll($insertData);
                    if($importRet){
                        unlink($path);
                        $result = ['code' => 1,'msg' => '执行成功'];
                    }
                }
            }
        }
        echo json_encode($result);
    }

    /**
     * 将对象转为字符串
     * @param  object|string $data 
     * @return string
     */
    private function _obj2str($data){
        return is_object($data) ? $data->__toString() : $data;
    }

    /**
     * 导出excel文件
     * @return file
     */
    public function export(){
        $input = input('get.');
        $ext = $input['ext'];
        $trackInfo = DataTrack::with(['province','city','admin'])->field('company,is_system,province_id,city_id,category_id,intention,decision_maker,decision_position,decision_phone,affect_maker,affect_position,affect_phone,input_time,flow_man')->order('input_time asc')->select();
        // 引入PHPExcel
        vendor('PHPExcel.PHPExcel');

        $phpExcel = new \PHPExcel();

        $phpExcel->getProperties()->setCreator('hgy')
            ->setLastModifiedBy('hgy')
            ->setTitle('Office 2007 XLSX Test Document')
            ->setSubject('Office 2007 XLSX Test Document')
            ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Test result file');

        // 设置行宽度
        $phpExcel->getActiveSheet()->getColumnDimension('A')->setWidth(33.29);
        $phpExcel->getActiveSheet()->getColumnDimension('B')->setWidth(8.86);
        $phpExcel->getActiveSheet()->getColumnDimension('C')->setWidth(13.14);
        $phpExcel->getActiveSheet()->getColumnDimension('D')->setWidth(8.86);
        $phpExcel->getActiveSheet()->getColumnDimension('E')->setWidth(8.86);
        $phpExcel->getActiveSheet()->getColumnDimension('F')->setWidth(14.71);
        $phpExcel->getActiveSheet()->getColumnDimension('G')->setWidth(27.43);
        $phpExcel->getActiveSheet()->getColumnDimension('H')->setWidth(62.14);
        $phpExcel->getActiveSheet()->getColumnDimension('I')->setWidth(18.43);
        $phpExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15.57);
        $phpExcel->getActiveSheet()->getColumnDimension('K')->setWidth(27.86);
        $phpExcel->getActiveSheet()->getColumnDimension('L')->setWidth(11);
        $phpExcel->getActiveSheet()->getColumnDimension('M')->setWidth(6.86);

        // 设置行高度
        $phpExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);
        $phpExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);

        // 设置字体
        $styleArray = array(
            'font' => array(
                'bold' => true,
                // 'color' => array('rgb' => 'FF0000'),
                'size' => 15,
                'name' => 'Microsoft YaHei'
            )
        );

        $phpExcel->getActiveSheet()->getCell('A1')->setValue('Some text');
        $phpExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray);

        $phpExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);
        $phpExcel->getActiveSheet()->getStyle('A2:M2')->getFont()->setBold(true);
        $phpExcel->getActiveSheet()->getStyle('A2:M2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('A1:M2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        // 设置水平居中
        $phpExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('J')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('J')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('K')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('L')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('M')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // 合并cell
        $phpExcel->getActiveSheet()->mergeCells('A1:M1');

        // 设置表格标题
        $phpExcel->setActiveSheetIndex(0)
            ->setCellValue('A1','跟踪信息')
            ->setCellValue('A2','商家名称')
            ->setCellValue('B2','系统情况')
            ->setCellValue('C2','所在地区')
            ->setCellValue('D2','商家类型')
            ->setCellValue('E2','意向类型')
            ->setCellValue('F2','决策人')
            ->setCellValue('G2','决策人职务')
            ->setCellValue('H2','决策人电话')
            ->setCellValue('I2','决策影响人')
            ->setCellValue('J2','决策影响人职务')
            ->setCellValue('K2','决策影响人电话')
            ->setCellValue('L2','录入时间')
            ->setCellValue('M2','跟踪人');

        $sys = config('is_system');
        $int = config('intention_type');
        $cate = config('business_type');
        for($i = 0;$i <= count($trackInfo) - 1;$i++){
            $isSystem = array_key_exists($trackInfo[$i]['is_system'], $sys) ? $sys[$trackInfo[$i]['is_system']] : 0;
            $category = array_key_exists($trackInfo[$i]['category_id'], $cate) ? $cate[$trackInfo[$i]['category_id']] : 0;
            $intention = array_key_exists($trackInfo[$i]['intention'], $int) ? $int[$trackInfo[$i]['intention']] : 0;
            $phpExcel->getActiveSheet(0)->setCellValue('A'.($i+3),$trackInfo[$i]['company']);
            $phpExcel->getActiveSheet(0)->setCellValue('B'.($i+3),$isSystem);
            $phpExcel->getActiveSheet(0)->setCellValue('C'.($i+3),$trackInfo[$i]['province']['name'].'/'.$trackInfo[$i]['city']['name']);
            $phpExcel->getActiveSheet(0)->setCellValue('D'.($i+3),$category);
            $phpExcel->getActiveSheet(0)->setCellValue('E'.($i+3),$intention);
            $phpExcel->getActiveSheet(0)->setCellValue('F'.($i+3),$trackInfo[$i]['decision_maker']);
            $phpExcel->getActiveSheet(0)->setCellValue('G'.($i+3),$trackInfo[$i]['decision_position']);
            $phpExcel->getActiveSheet(0)->setCellValue('H'.($i+3),$trackInfo[$i]['decision_phone']);
            $phpExcel->getActiveSheet(0)->setCellValue('I'.($i+3),$trackInfo[$i]['affect_maker']);
            $phpExcel->getActiveSheet(0)->setCellValue('J'.($i+3),$trackInfo[$i]['affect_position']);
            $phpExcel->getActiveSheet(0)->setCellValue('K'.($i+3),$trackInfo[$i]['affect_phone']);
            $phpExcel->getActiveSheet(0)->setCellValue('L'.($i+3),date('Y-m-d',$trackInfo[$i]['input_time']));
            $phpExcel->getActiveSheet(0)->setCellValue('M'.($i+3),$trackInfo[$i]['admin']['name']);
            $phpExcel->getActiveSheet()->getStyle('A'.($i+3).':M'.($i+3))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('A'.($i+3).':M'.($i+3))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $phpExcel->getActiveSheet()->getRowDimension($i+3)->setRowHeight(16);
        }
        // sheet命名
        $phpExcel->getActiveSheet()->setTitle('跟踪信息表');
        $phpExcel->setActiveSheetIndex(0);
        $saveFileName = '跟踪信息表('.date('Y-m-d H_i_s').').'.$ext;
        // excel头参数
        header('Content-Type: application/vnd.ms-excel'); // 生成xls/xlsx文件
        // header('Content-Type: text/csv'); // 生成csv文件
        header('Content-Disposition: attachment;filename="'.$saveFileName.'"'); // 后缀名:xls/xlsx/csv

        header('Cache-Control: max-age=0');
        if($ext == 'xls'){
            $attr = 'Excel5';
        }else{
            $attr = 'Excel2007';
        }

        $phpWriter = \PHPExcel_IOFactory::createWriter($phpExcel, $attr); // Excel5:xls; Excel2007:xlsx; CSV:csv;
        $output = $phpWriter->save('php://output');
    }
}