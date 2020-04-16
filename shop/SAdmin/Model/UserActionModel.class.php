<?php

namespace SAdmin\Model;

class UserActionModel
{

    public function show_user_action_page()
    {

        $count = M('user_action')->count();
        $Page = new \Think\Page($count, C('BACK_PAGE_NUM'));
        $show = $Page->show();// 分页显示输出

        $list = M('user_action')->order('ua_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        return array(
            'empty' => '<tr><td colspan="20">~~暂无数据</td></tr>',
            'list' => $list,
            'page' => $show
        );

    }


}
