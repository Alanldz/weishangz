<?php
// +----------------------------------------------------------------------
// | 零云 [ 简单 高效 卓越 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.lingyun.net All rights reserved.
// +----------------------------------------------------------------------
// | Author: jry <598821125@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

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
        $condition  = array('like', '%' . $keyword . '%');
        $map['a.id|a.username|a.nickname'] = array(
            $condition,
            $condition,
            $condition,
            '_multi' => true,
        );

        // 获取所有用户
        $map['a.status'] = array('egt', '0'); // 禁用和正常状态
        $user_object   = M('admin a')->join(C('DB_PREFIX').'group b ON a.auth_id=b.id','LEFT');
        //分页
        $p=getpage($user_object,$map,10);
        $page=$p->show();  

        $data_list = $user_object
            ->field('a.*,b.title')
            ->where($map)
            ->order('a.id asc')
            ->select();

        $this->assign('list',$data_list);
        $this->assign('table_data_page',$page);
        $this->display();
    }
}
