<?php
class hook_class extends AWS_MODEL
{
    protected static $plugins_hook;
    /*获取插件钩子关联信息*/
    public function get_plugins_hook_list($update=false)
    {
        if (self::$plugins_hook && !$update)
        {
            return self::$plugins_hook;
        }else{
            $plugins_hook_list = AWS_APP::model()->fetch_column('hook_plugins','status = 1','sort DESC',['hook','plugins']);
            self::$plugins_hook = $plugins_hook_list;
            self::$plugins_hook['fetched'] = true;
            return self::$plugins_hook;
        }
    }

    /*获取钩子关联信息*/
    public function get_hook_list($update=false)
    {
        static $hookList;
        if ($hookList && !$update)
        {
            return  $hookList;
        }else{
            $hookList = AWS_APP::model()->fetch_column('hook','status = 1','add_time DESC',['name','status']);
            $hookList['fetched'] = true;
            return  $hookList;
        }
    }

    /*获取钩子信息*/
    public function get_hook_info_by_hook_name($tag,$update = false)
    {
        $hook_lists = $this->get_hook_list($update);
        if (!empty($hook_lists))
        {
            $hook_lists = array_column($hook_lists, null, "name");
        }
        return $hook_lists[$tag];
    }

    /**
     * @param string $where 查询条件
     * @param string $order 排序规则
     * @param int $page 分页
     * @return array|bool
     */
    public function get_hook_list_by_page($where,$order,$page = 0)
    {
        $where = $where ? $where : '';
        $order = $order ? $order : 'id asc';
        $list = $this->fetch_page('hook', $where, $order, $page, 15);
        if(!$list) return false;
        $plugins = array();
        foreach ($list as $k=>$v)
        {
            $hook_plugins = AWS_APP::model()->fetch_all('hook_plugins',"hook = '".$v['name']."'");
            if($hook_plugins)
            {
                foreach ($hook_plugins as $key=>$val)
                {
                    $plugins[$k][]=$val['plugins'];
                }
            }
            $list[$k]['hook_plugins'] = implode(',',$plugins[$k]);
        }
        return $list;
    }
}