<?php
// +----------------------------------------------------------------------
// | 零云 [ 简单 高效 卓越 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.lingyun.net All rights reserved.
// +----------------------------------------------------------------------
// | Author: jry <598821125@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Util\Constants;
use Think\Page;

/**
 * 登录日志控制器
 */
class LoginLogController extends AdminController
{

    /**
     * 登录日志首页
     */
    public function index()
    {
        // 搜索
        $keyword    = I('keyword', '', 'string');
        $map['a.username'] = array('like', '%' . $keyword . '%');

        $user_object   = M('login_log l')
            ->join(C('DB_PREFIX').'admin a ON l.uid = a.id','LEFT')
            ->join('nc_admin b ON l.uid = b.a_id','LEFT');

        //分页
        $p=getpage($user_object,$map,10);
        $page=$p->show();  

        $data_list = $user_object
            ->field('l.*,a.username,b.a_uname')
            ->where($map)
            ->order('l.id desc')
            ->select();

        $this->assign('list',$data_list);
        $this->assign('typeItems',Constants::getLoginTypeItems());
        $this->assign('loginBackend',Constants::BACKEDND);
        $this->assign('table_data_page',$page);
        $this->display();
    }
}
