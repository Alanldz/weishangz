<?php

namespace Admin\Controller;

/**
 * 轮播图控制器
 *
 */
class BannerController extends AdminController
{
    /**
     * 用户列表
     */
    public function index()
    {
        // 获取所有用户
        $map['status'] = array('egt', '0'); // 禁用和正常状态
        //链接数据库
        $user_object = M('banner');
        //分页
        $p = getpage($user_object, $map, 10);
        $page = $p->show();
        //条件查询
        $data_list = $user_object->where($map)->order('id desc')->select();

        $this->assign('list', $data_list);
        $this->assign('table_data_page', $page);
        $this->display();
    }

    /**
     * 新增轮播图
     */
    public function add()
    {
        if (IS_POST) {
            $user_object = M('banner');
            $data = I('post.');
            if (empty($data['title'])) {
                $this->error('名称不能为空');
            }
            $data['uid_str'] = '0,';
            $data['create_time'] = time();
            $data['status'] = 1;

            if ($data) {
                if ($_FILES['image']['tmp_name']) {
                    $save_name = date('YmdHis', time()) . rand(100000, 999999);
                    $info = uploading('banner', $save_name);
                    if (!$info['status']) {
                        $this->error($info['msg']);
                    }
                    $data['picture'] = $info['path']['filepath'];
                } else {
                    $this->error('图片不能为空');
                }
                $id = $user_object->add($data);
                if ($id) {
                    $this->success('新增成功', U('index'));
                } else {
                    $this->error('新增失败');
                }
            } else {
                $this->error($user_object->getError());
            }
        } else {
            $this->display('edit');
        }
    }

    /**
     * 编辑轮播图
     */
    public function edit($id)
    {
        if (IS_POST) {
            // 提交数据
            $user_object = D('banner');
            $data = I('post.');
            $data['create_time'] = time();

            if (empty($data['title'])) {
                $this->error('名称不能为空');
            }
            if ($data) {
                if ($_FILES['image']['tmp_name']) {
                    $save_name = date('YmdHis', time()) . rand(100000, 999999);
                    $info = uploading('banner', $save_name);
                    if (!$info['status']) {
                        $this->error($info['msg']);
                    }
                    $data['picture'] = $info['path']['filepath'];
                }
                $result = $user_object->save($data);
                if ($result) {
                    $this->success('更新成功', U('index'));
                } else {
                    $this->error('更新失败', $user_object->getError());
                }
            } else {
                $this->error($user_object->getError());
            }
        } else {
            // 获取账号信息
            $info = D('banner')->find($id);
            $this->assign('info', $info);
            $this->display();
        }
    }

    /**
     * 设置一条或者多条数据的状态
     * @param mixed|string $model
     */
    public function setStatus($model = CONTROLLER_NAME)
    {
        $ids = I('request.ids');
        parent::setStatus($model);
    }
}
