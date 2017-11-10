<?php
namespace back\plugin\controller;
use think\Controller;
use back\extra\controller\Base;
use back\extra\model\TelRecord;
use think\Db;

class Tel extends Base
{
    /**
     * 列表
     * @return void
     */
    public function index(){
        $input = input('get.');
        $where = [];
        if(!empty($input['admin_id'])){
            $where['admin_id'] = $input['admin_id'];
        }
        $record = TelRecord::with(['province','city'])->where($where)->field('id,customer_phone,customer,province_id,city_id,duration_time,deduct_time,inbound_navigation,called_phone,start_time,end_time,service_name')->order('start_time desc')->paginate(config('page_size'),false,['query' => $input]);
        $page = $record->render();
        $business = $this->getBusinessMen(true,false);
        $this->assign([
            'business' => $business,
            'page'     => $page,
            'record'   => $record
        ]);
        return $this->fetch();
    }

    /**
     * 导入excel记录表格
     * @return void
     */
    public function loadfile(){
        if(request()->isPost()){
            $business = array_flip($this->getBusinessMen(true,false));
            $result = ['code' => 0,'msg' => '导入失败'];
            $csvFile = request()->file('csv_file');
            if(!empty($csvFile)){
                vendor('PHPExcel.PHPExcel.IOFactory');
                vendor('PHPExcel.PHPExcel.Reader.CSV');// csv
                vendor('PHPExcel.PHPExcel.Reader.Excel5');// xls
                vendor('PHPExcel.PHPExcel.Reader.Excel2007');// xlsx
                $path = './public/uploadfile/telrecord/';
                $info = $csvFile->validate(['size' => 3145728,'ext' => 'xls,xlsx,csv'])->rule('uniqid')->move($path);
                $error = $info->getError();
                if(empty($error)){
                    $ext = $info->getExtension();
                    $fileName = $path.$info->getSaveName();
                    if($ext == 'csv'){
                        $reader = \PHPExcel_IOFactory::createReader('CSV')
                                    ->setDelimiter(',')  
                                    ->setInputEncoding('GBK')  
                                    ->setEnclosure('"')  
                                    ->setLineEnding("\r\n")  
                                    ->setSheetIndex(0);
                    }else if($ext == 'xls'){
                        $reader = \PHPExcel_IOFactory::createReader('Excel5');
                    }else if($ext == 'xlsx'){
                        $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                    }

                    $excelObj = $reader->load($fileName);
                    $sheet = $excelObj->getSheet(0);
                    $highestRow = $sheet->getHighestRow(); // 取得总行数  
                    $highestColumm = $sheet->getHighestColumn(); // 取得总列数  
                    for ($i = 2; $i <= $highestRow; $i++) { 
                        $duration_time = $sheet->getCell('F'.$i)->getValue();
                        $service_name = (string)$excelObj->getSheet(0)->getCell('I'.$i)->getValue();
                        if(empty($service_name)){
                            $service_name = '';
                        }
                        $adminId = array_key_exists($service_name, $business) ? $business[$service_name] : 0;
                        $startTime = $sheet->getCell('D'.$i)->getValue();
                        $endTime = $sheet->getCell('E'.$i)->getValue();
                        if($ext != 'csv'){
                            $startTime = gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($startTime));
                            $endTime = gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($endTime));
                        }
                        $area = $sheet->getCell('C'.$i)->getValue();
                        $region = explode('省', $area);

                        if(isset($region[1])){
                            $cityName = str_replace(['市','区',' '], '', $region[1]);
                            if($cityName == '恩施土家族苗族自治州'){
                                $cityName = '恩施';
                            }
                            $city = Db::name('region')->where(['name' => ['like','%'.$cityName.'%'],'level' => 2])->field('id,pid,name')->find();
                            if(!empty($city)){
                                $provinceId = $city['pid'];
                                $cityId = $city['id'];
                            }
                        }else{
                            $pName = str_replace(['市','省',' '], '', $region[0]);
                            $data = Db::name('region')->where(['name' => ['like','%'.$pName.'%'],'level' => config('normal_status')])->field('id,name')->find();
                            $provinceId = $data['id'];
                            $cityId = 0;
                        }
                        $addData[] = [
                            'customer_phone'     => $sheet->getCell('A'.$i)->getValue(),
                            'customer'           => $sheet->getCell('B'.$i)->getValue(),
                            'area'               => $area,
                            'province_id'        => $provinceId,
                            'city_id'            => $cityId,
                            'start_time'         => strtotime($startTime),
                            'end_time'           => strtotime($endTime),
                            'duration_time'      => $duration_time,
                            'deduct_time'        => $sheet->getCell('G'.$i)->getValue(),
                            'inbound_navigation' => $sheet->getCell('H'.$i)->getValue(),
                            'admin_id'           => $adminId,
                            'service_name'       => $service_name,
                            'called_phone'       => $sheet->getCell('J'.$i)->getValue()
                        ];
                    }
                    $addRecord = Db::name('TelRecord')->insertAll($addData);
                    if($addRecord){
                        $result = ['code' => 1,'msg' => '导入成功'];
                    }
                }
            }
            echo json_encode($result);
        }
    }

    /**
     * 删除记录
     * @return json
     */
    public function delrecord(){
        $ids = input('get.id');
        $result = ['code' => 0,'msg' => '删除失败'];
        if($ids){
            $where['id'] = ['in',$ids];
            $delResult = Db::name('TelRecord')->where($where)->delete();
            if($delResult){
                $result = ['code' => 1,'msg' => '删除成功'];
            }
        }
        echo json_encode($result);
    }
}