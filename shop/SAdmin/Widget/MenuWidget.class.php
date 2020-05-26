<?php

namespace SAdmin\Widget;

use Think\Controller;

/**
 * 后台菜单
 */
class MenuWidget extends Controller
{

    function menu_show()
    {
        //查找该后台用户所有的权限
        $uid = session('user_auth.uid');
        $menu = M('Menu', 'nc_');

        //超级管理员拥有所有权限
        if($uid == 1) {
            $menu = $menu->order('sort_order')->select();
        }else{
            $admin = M('admin','nc_')->find($uid);
            $role = M('role','nc_')->find($admin['role_id']);

            if($role){
                $info = explode(',', trim($role['menu_auth'],','));
                $where['id'] = array('in',$info);

                $menu = $menu->where($where)->order('sort_order')->select();
            }else{
                $menu = [];
            }
        }

        $tree = list_to_tree($menu, 'id', 'pid', 'children', 0);
        $this->admin_menu = $tree;
        $this->display('Widget:menu');
    }


}

