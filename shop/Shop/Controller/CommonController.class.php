<?php

namespace Shop\Controller;

use Think\Controller;

class CommonController extends Controller
{
    /**
     *  初始化,权限控制,菜单显示
     */
    protected function _initialize()
    {
        //判断网站是否关闭
        $close = is_close_site();
        if ($close['value'] == 0) {
            success_alert($close['tip'], U('Home/Login/logout'));
        }

        //判断商城是否关闭
        $close1 = is_close_mall();
        if ($close1['value'] == 0) {
            success_alert($close1['tip'], U('Home/index/index'));
        }
        if (IS_AJAX) {
            $uid = session('userid');
            if(!$uid){
                $this->error ('请先登录',U('Home/Login/login'));
            }
        } else {
            //验证用户登录
            $this->is_user();
        }
    }

    protected function is_user()
    {
        $user_id = user_login();
        $user = M('user');
        if (!$user_id) {
            $this->redirect('Home/Login/login');
            exit();
        }

        //判断12小时后必须重新登录
        $in_time = session('in_time');
        $time_now = time();
        $between = $time_now - $in_time;
        if ($between > 43200) {
            $this->redirect('Home/Login/logout');
        }

        $u_info = $user->where(['userid' => $user_id])->field('status,session_id')->find();
        //判断用户是否锁定
        $login_from_admin = session('login_from_admin');//是否后台登录
        if ($u_info['status'] == 0 && $login_from_admin != 'admin') {
            if (IS_AJAX) {
                $mes = array('status' => 2, 'info' => '你账号已锁定，请联系管理员');
                $this->ajaxReturn($mes);
            } else {
                success_alert('你账号已锁定，请联系管理员', U('Home/Login/logout'));
                exit();
            }
        }

        //判断用户是否在他处已登录
        $session_id = session_id();
        if ($session_id != $u_info['session_id'] && empty($login_from_admin)) {
            if (IS_AJAX) {
                $mes = array('status' => 2, 'info' => '您的账号在他处登录，您被迫下线');
                $this->ajaxReturn($mes);
            } else {
                success_alert('您的账号在他处登录，您被迫下线', U('Home/Login/logout'));
                exit();
            }
        }
    }
}

?>