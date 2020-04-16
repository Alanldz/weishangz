<?php

namespace Admin\Controller;

use Admin\Model\VersionModel;

/**
 * 版本控制器
 */
class VersionController extends AdminController
{
    /**
     * 版本列表
     */
    public function index(){
        $map['delete_time'] = '';
        $model   = M('version_announcement');
        //分页
        $p=getpage($model,$map,10);
        $page=$p->show();  

        $data_list     = $model->where($map)->order('id desc')->select();
        $this->assign('list',$data_list);
        $this->assign('table_data_page',$page);
        $this->display();
    }

    /**
     * 新增用户
     */
    public function add(){
        if (IS_POST) {
            $versionModel = new VersionModel();
            $res = $versionModel->addVersion();
            if(!$res){
                $this->error($versionModel->getError());
            }
            $this->success('新增成功', U('index'));
        }

        $this->display('edit');
    }

    /**
     * 编辑用户
     */
    public function edit($id){
        if (IS_POST) {
            $versionModel = new VersionModel();
            $res = $versionModel->updateData();
            if(!$res){
                $this->error($versionModel->getError());
            }
            $this->success('新增成功', U('index'));
        }

        // 获取账号信息
        $info = D('version_announcement')->find($id);
        $info['create_time'] = date('Y-m-d',strtotime($info['create_time']));
        $this->assign('info',$info);
        $this->display();
    }

    /**
     * 删除
     */
    public function delete(){
        $versionModel = new VersionModel();
        $res = $versionModel->deleteData();
        if(!$res){
            $this->error($versionModel->getError());
        }
        $this->success('删除成功', U('index'));
    }

}
