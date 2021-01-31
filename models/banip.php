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

/**
 * WeCenter APP 封禁IP
 *
 * @package     WeCenter
 * @subpackage  App
 * @category    Model
 * @author      Laushow
 */


if (!defined('IN_ANWSION')) {
    die;
}

class banip_class extends AWS_MODEL
{
    /**
     * @description [封禁IP]
     * @param $ip
     * @author Laushow
     * @datatime 2018/10/9 16:09
     */
    function ban_ip($uid,$ip)
    {
        if(!empty($uid) && !empty($ip)){
            return $this->insert('ban_ip',['ip'=>$ip,'uid'=>$uid,'time'=>time()]);
        } else {
            return false;
        }
    }

    /**
     * 检查IP是否被封禁
     * @param $ip
     * @return bool
     */
    public function check_ip($ip)
    {
        static $forbiddenIp;
        if(!isset($forbiddenIp[$ip])){
            $forbiddenIp[$ip] = $this->fetch_one('ban_ip', 'id', "ip='".$ip."'");
        }
        if($forbiddenIp[$ip])
        {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 解封IP
     * @param $uid
     * @param $ip
     * @return bool|int
     * @throws \Exception
     */
    public function unban_ip($uid, $ip)
    {
        if (!empty($uid) && !empty($ip)) {
            return $this->delete('ban_ip', "ip='$ip' and uid=$uid");
        } else {
            return false;
        }
    }

}
