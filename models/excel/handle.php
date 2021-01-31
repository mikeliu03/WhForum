<?php
/**
 * WeCenter Framework
 *
 * An open source application development framework for PHP 5.2.2 or newer
 *
 * @package     WeCenter Framework
 * @author      WeCenter Dev Team
 * @copyright   Copyright (c) 2011 - 2014, WeCenter, Inc.
 * @license     http://www.wecenter.com/license/
 * @link        http://www.wecenter.com/
 * @since       Version 1.0
 * @filesource
 */
if (!defined('IN_ANWSION'))
{
    die;
}

class excel_handle_class extends AWS_MODEL
{
    public function __construct(){
        require 'PHPExcel.php';
        $this->excel= new PHPExcel();
        $this->header_arr = array('A','B','C','D','E','F','G','H','I','J','K','L','M', 'N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    }

    public function export_data($title,$head_name,$data,$indexKey,$date=null)
    {
    	$date=$date?$date:date('Y-m-d');
        $this->excel->getActiveSheet()->setTitle($title.$date);
        $startRow=2;
 		foreach ($data as $row) { 
		 		foreach ($indexKey as $key => $value){
		 				$this->excel->getActiveSheet()->setCellValue($this->header_arr[$key].'1',$head_name[$key]);
		 				$this->excel->getActiveSheet()->setCellValue($this->header_arr[$key].$startRow,$row[$value]?$row[$value]:0);
		 			}
	 		$startRow++;
 		} 
         $obj_Writer = PHPExcel_IOFactory::createWriter($this->excel,'Excel5');
         $filename =$title. $date.".xls";//文件名
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