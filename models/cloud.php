<?php

if (!defined('IN_ANWSION'))
{
    die;
}

class cloud_class extends AWS_MODEL
{
    /*检查最新版本*/
    public function check_last_version()
    {
        $data = array(
            'url'=>base_url(),
            'version'=>G_VERSION,
            'version_build' =>G_VERSION_BUILD
        );
        $result = HTTP::wc_request(HTTP::$api_url.HTTP::$version_check_url,'post',$data);
        if(!$result)
        {
            return false;
        }
        return $result;
    }

    /*检查升级内容*/
    public function check_upgrade_info()
    {
        $data = array(
            'url'=>base_url(),
            'version'=>G_VERSION,
            'version_build' =>G_VERSION_BUILD
        );
        $result=  HTTP::wc_request(HTTP::$api_url.HTTP::$get_upgrade_info_url,'post',$data);
        if(!$result)
        {
            return false;
        }
        return $result;
    }

    /*下载更新文件*/
    public function download_upgrade_file($id)
    {
        $result=  HTTP::wc_request(HTTP::$api_url.HTTP::$upgrade_url,'post',array('id'=>intval($id),'url'=>base_url(),'version'=>G_VERSION,));
        if(!$result)
        {
            return false;
        }
        return $result;
    }

    //执行sql
    public function run_query($sql_query)
    {
        $this->begin_transaction();
        
        if (!$db_engine = get_setting('db_engine'))
        {
            $db_engine = 'MyISAM';
        }

        $sql_query = str_replace("\n", "\r", $sql_query);

        if ($db_table_querys = explode(";\r", str_replace(array('[#DB_PREFIX#]','[#DB_ENGINE#]'),array(AWS_APP::config()->get('database')->prefix, $db_engine), $sql_query)))
        {
            foreach ($db_table_querys as $_sql)
            {
                if ($query_string = trim(str_replace(array(
                    "\r",
                    "\n",
                    "\t"
                ), '', $_sql)))
                {
                    $query_string = strtolower($query_string);
                    try {
                        if(strpos($query_string,'alter') !== false){ 
                            $pattern = '#`(.*?)`#i'; 
                            preg_match_all($pattern, $query_string, $matches); 
                            $table_name = $matches[1][0];
                            $column_name = $matches[1][1];
                            $sql = 'DESCRIBE '.$table_name.' '.$column_name;
                            $resulte = $this->db()->fetchRow($sql);
                            if($resulte){
                                if(strpos($query_string,'add') == false){
                                    $this->db()->query($query_string);
                                }
                            }

                            if(!$resulte && strpos($query_string,'add') !== false){
                                $this->db()->query($query_string);
                            }
                            
                        }else{
                            $this->db()->query($query_string);
                        }
                        
                    } catch (Exception $e) {
                        return "<b>SQL:</b> <i>{$query_string}</i><br /><b>错误描述:</b> " . $e->getMessage();
                        $this->roll_back();  
                    }
                }
            }
        }

        $this->commit();
    }
}