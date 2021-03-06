<?php

namespace SAdmin\Controller;

class HeadController extends CommonController
{

    protected function _initialize()
    {
        parent::_initialize();
        $this->breadcrumb1 = '系统';
        $this->breadcrumb2 = '头部导航';
    }

    function index()
    {
        $cate = M()->query('SELECT id,pid,url,title AS name FROM ' . C('DB_PREFIX') . 'head ORDER BY sort_order ASC');
        $list = list_to_tree($cate);
        $this->list = json_encode($list);

        $this->display();
    }

    function add()
    {
        if (IS_POST) {
            $d['title'] = I('title');
            $d['url'] = I('url');
            $d['sort_order'] = I('sort_order');
            $d['pid'] = I('id');

            if (M('head')->where(array('title' => $d['title'], 'pid' => $d['pid']))->find()) {
                $data['err'] = '该分类名称已经存在';
                $this->ajaxReturn($data);
                die();
            }

            $id = M('head')->add($d);
            if ($id) {
                $data['name'] = $d['title'];
                $data['id'] = $id;
                $this->ajaxReturn($data);
                die();
            } else {
                die();
            }
        }
    }

    function edit()
    {
        if (IS_POST) {
            $d['id'] = I('id');
            $d['title'] = I('title');
            $d['url'] = I('url');
            $d['sort_order'] = I('sort_order');

            $category = M('head')->find($d['id']);
            /*
            if(M('head')->where(array('title'=>$d['title'],'pid'=>$category['pid']))->find()){
                $data['err']='该分类名称已经存在';
                $this->ajaxReturn($data);
                die();
            }
             */
            $r = M('head')->save($d);

            if ($r) {

                $data['success'] = '修改成功';
                $data['name'] = $d['title'];
                $this->ajaxReturn($data);

                die();
            } else {

                $data['err'] = '修改失败';

                $this->ajaxReturn($data);

                die();
            }
        }

    }

    function del()
    {
        if (IS_POST) {
            $id = I('id');

            if (M('head')->where('pid=' . $id)->find()) {
                $data['err'] = '请先删除子节点！！';
                $this->ajaxReturn($data);
                die;
            }
            if (M('blog')->where(array('category_id' => $id))->find()) {
                $data['err'] = '请先删除该分类下博客！！';
                $this->ajaxReturn($data);
                die;
            }

            if (M('head')->where('id=' . $id)->delete()) {
                $this->ajaxReturn('删除成功');
                die();
            }
        }
    }

    function get_info()
    {
        if (IS_POST) {
            $id = I('id');
            $d = M('head')->find($id);
            $data['title'] = $d['title'];
            $data['url'] = $d['url'];
            $data['sort_order'] = $d['sort_order'];
            $this->ajaxReturn($data);
        }
    }

    function autocomplete()
    {
        $json = array();

        $filter_name = I('filter_name');

        if (isset($filter_name)) {
            $sql = 'SELECT id,title FROM ' . c('DB_PREFIX') . "head where title LIKE'%" . $filter_name . "%' LIMIT 0,20";
        } else {
            $sql = 'SELECT id,title FROM ' . c('DB_PREFIX') . "head LIMIT 0,20";

        }
        $results = M('head')->query($sql);

        foreach ($results as $result) {
            $json[] = array(
                'category_id' => $result['id'],
                'name' => strip_tags(html_entity_decode($result['title'], ENT_QUOTES, 'UTF-8'))
            );
        }

        $this->ajaxReturn($json);
    }

}