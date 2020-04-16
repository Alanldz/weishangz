<?php

namespace SAdmin\Model;

use Think\Model;

class CommentModel extends Model
{

    public function show_comment_page()
    {
        $sql = 'SELECT * FROM ' . C('DB_PREFIX') . 'comment ';
        $count = count(M()->query($sql));
        $Page = new \Think\Page($count, C('BACK_PAGE_NUM'));
        $show = $Page->show();// 分页显示输出
        $sql .= ' order by comment_id desc LIMIT ' . $Page->firstRow . ',' . $Page->listRows;
        $list = M()->query($sql);
        return array(
            'empty' => '<tr><td colspan="20">~~暂无数据</td></tr>',
            'list' => $list,
            'page' => $show
        );
    }
}