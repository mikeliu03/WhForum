<?php
if (!defined('IN_ANWSION'))
{
    die;
}
/***
 * @Excel 导入导出类。
 */

class excel_class extends AWS_MODEL
{
    protected $excel;
    public function __construct()
    {
        require 'excel/PHPExcel.php';
        $this->excel= new PHPExcel();
        $this->excel->getActiveSheet()->setTitle('提现申请批量导出');
    }

    public function export($list)
    {
        $this->excel->getActiveSheet()
                ->setCellValue('A1','提现人')
                ->setCellValue('B1','提现金额')
                ->setCellValue('C1','开户行')
                ->setCellValue('D1','卡号')
                ->setCellValue('E1','申请时间');
        $i=2;
        foreach($list as $val){
            $this->excel
                ->getActiveSheet()
                ->setCellValue('A'.$i,$val['username'])
                ->setCellValue('B'.$i, $val['money'])
                ->setCellValue('C'.$i, $val['open_bank'].$val['bank'])
                ->setCellValue('D'.$i, $val['card'])
                ->setCellValue('E'.$i, date('Y-m-d H:i:s',$val['addtime']));
            $i++;
        }
        $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(80);       
        $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(50);       
        $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(40);       
        $obj_Writer = PHPExcel_IOFactory::createWriter($this->excel,'Excel5');
        $filename ='withdraw'. date('Y-m-d').".xls";//文件名
        //设置header
        header("Content-Type: application/force-download"); 
        header("Content-Type: application/octet-stream"); 
        header("Content-Type: application/download"); 
        header('Content-Disposition:inline;filename="'.$filename.'"'); 
        header("Content-Transfer-Encoding: binary"); 
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
        header("Pragma: no-cache"); 
        $obj_Writer->save('php://output');//输出
        die();//种植执行
    }

    public function data_export($list)
    {
        $this->excel ->getActiveSheet()
                ->setCellValue('A1','客服')
                ->setCellValue('B1','未处理')
                ->setCellValue('C1','已解决')
                ->setCellValue('D1','已关闭');
        $i=2;
        foreach($list as $val){
            $this->excel
                ->getActiveSheet()
                ->setCellValue('A'.$i,$val['user_name'])
                ->setCellValue('B'.$i, $val['no_deal_num'])
                ->setCellValue('C'.$i, $val['deal_num'])
                ->setCellValue('D'.$i, $val['closed_num']);
            $i++;
        }
        $obj_Writer = PHPExcel_IOFactory::createWriter($this->excel,'Excel5');
        $filename ='业绩报表'. date('Y-m-d').".xls";//文件名
        //设置header
        header("Content-Type: application/force-download"); 
        header("Content-Type: application/octet-stream"); 
        header("Content-Type: application/download"); 
        header('Content-Disposition:inline;filename="'.$filename.'"'); 
        header("Content-Transfer-Encoding: binary"); 
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
        header("Pragma: no-cache"); 
        $obj_Writer->save('php://output');//输出
        die();//种植执行
    }

    public function detail_exports($list)
    {
        $this->excel
                ->getActiveSheet()
                ->setCellValue('A1','优先级')
                ->setCellValue('B1','状态')
                ->setCellValue('C1','ID')
                ->setCellValue('D1','标题')
                ->setCellValue('E1','请求者')
                ->setCellValue('F1','来源')
                ->setCellValue('G1','发起时间')
                ->setCellValue('H1','处理人');
        $i=2;
        foreach($list as $val){
            switch ($val['priority']) {
                case 'low':
                    $priority = '低';
                    break;

                case 'normal':
                    $priority = '中';
                    break;

                case 'high':
                    $priority = '高';
                    break;

                case 'urgent':
                    $priority = '紧急';
                    break;
            }

            if ($val['status'] == 'closed'){
                $status = '已关闭';
            }else if ($val['reply_time']) {
                $status = '已解决';
            } else if ($val['service_info']) {
                $status = '已受理';
            } else {
                $status = '未受理';
            };

            switch ($val['source']) {
                    case 'local':
                        $source = '本站';
                        break;

                    case 'weibo':
                        $source = '微博';
                        break;

                    case 'weixin':
                        $source = '微信';
                        break;

                    case 'email':
                        $source = '邮件';
                        break;
                }

            if ($val['service_info']) {
                $service_info = $val['service_info']['user_name'];
             } else {
                $service_info = '未分配';
            }

            $this->excel
                ->getActiveSheet()
                ->setCellValue('A'.$i,$priority)
                ->setCellValue('B'.$i, $status)
                ->setCellValue('C'.$i, $val['id'])
                ->setCellValue('D'.$i, $val['title'])
                ->setCellValue('E'.$i, $val['user_info']['user_name'])
                ->setCellValue('F'.$i, $source)
                ->setCellValue('G'.$i, date_friendly($val['time']))
                ->setCellValue('H'.$i, $service_info);
            $i++;
        }
             
        $obj_Writer = PHPExcel_IOFactory::createWriter($this->excel,'Excel5');
        $filename ='业绩'. date('Y-m-d').".xls";//文件名
        //设置header
        header("Content-Type: application/force-download"); 
        header("Content-Type: application/octet-stream"); 
        header("Content-Type: application/download"); 
        header('Content-Disposition:inline;filename="'.$filename.'"'); 
        header("Content-Transfer-Encoding: binary"); 
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
        header("Pragma: no-cache"); 
        $obj_Writer->save('php://output');//输出
        die();//种植执行
    }
}