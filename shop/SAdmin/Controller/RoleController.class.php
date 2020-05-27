<?php

namespace SAdmin\Controller;

use Util\Tree;

class RoleController extends CommonController
{


    protected function _initialize()
    {
        parent::_initialize();
        $this->breadcrumb1 = '用户';
        $this->breadcrumb2 = '角色管理';
    }

    function index()
    {
        $model = M('role','nc_')
            ->where(array('del_time'=>0))
            ->order('sort asc, id asc')
            ->select();

        $empty = "<tr><td colspan=\"20\">~~暂无数据</td></tr>";

        $this->assign('empty',$empty);
        $this->assign('list', $model);
        $this->display();
    }


    /**
     * 新增角色
     */
    function add()
    {
        if (IS_POST) {
            $model       = M('role','nc_');
            $_POST['menu_auth'] = implode(',', I('post.menu_auth'));
            $data               = $model->create();
            if ($data) {
                $id = $model->add($data);
                if ($id) {
                    $this->success('新增成功', U('index'));
                } else {
                    $this->error('新增失败');
                }
            } else {
                $this->error($model->getError());
            }
        } else {
            $all_module_menu_list=$this->getMenuTree();
            $this->assign('all_module_menu_list', $all_module_menu_list);
            $this->assign('crumbs','新增');
            $this->display('edit');
        }
    }

    /**
     * 编辑角色
     */
    function edit($id)
    {
        if (IS_POST) {
            $model       = M('role','nc_');
            $_POST['menu_auth'] = implode(',', I('post.menu_auth'));
            $_POST['hylb'] = implode(',', I('post.hylb'));
            $data               = $model->create();
            if ($data) {
                if ($model->save($data) !== false) {
                    $this->success('更新成功', U('index'));
                } else {
                    $this->error('更新失败');
                }

            } else {
                $this->error($model->getError());
            }
        } else {
            //获取角色信息
            $where['id']=$id;
            $info=M('role','nc_')->find($id);

            // 获取功能模块的后台菜单列表
            $all_module_menu_list=$this->getMenuTree();
            $this->assign('all_module_menu_list', $all_module_menu_list);
            $info['menu_auth']=explode(',', trim($info['menu_auth'],','));
            $this->assign('info', $info);

            $this->display('edit');
        }
    }

    // 获取功能模块的后台菜单列表
    private function getMenuTree(){
        $tree                 = new Tree();
        $all_module_menu_list = array();

        $menu=M('menu','nc_')->order('sort_order asc,id asc')->select();

        $temp                               = $menu;
        $menu_list_item                     = $tree->list2tree($temp);
        return $menu_list_item;
    }

    function del()
    {
        if (IS_POST) {
            $id = I('id');
            if (M('Role', 'nc_')->where(array('id'=>$id))->delete()) {
                $this->ajaxReturn('删除成功');
                die();
            }
        }
    }
}