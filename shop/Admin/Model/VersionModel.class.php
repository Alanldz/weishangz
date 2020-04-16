<?php

namespace Admin\Model;

use Common\Model\ModelModel;

/**
 * 版本管理模型
 */
class VersionModel extends ModelModel
{
    /**
     * 数据库表名
     */
    protected $tableName = 'version_announcement';

    /**
     * 新增
     * @return bool
     * @time 2019-2-23 16:15:51
     */
    public function addVersion(){
        $model = D('version_announcement');
        $data = I('post.');

        if (empty($data['title'])) {
            $this->error = '标题不能为空';
            return false;
        }

        if (empty($data['create_time'])) {
            $this->error = '日期不能为空';
            return false;
        }

        $data['create_time'] = date('YmdHis',strtotime($data['create_time']));
        $data['update_time'] = date('YmdHis',time());
        $res = $model->add($data);
        if (!$res){
            $this->error = '新增失败';
            return false;
        }
        return true;
    }

    /**
     * 更新
     * @return bool
     * @time 2019-2-23 16:30:21
     */
    public function updateData(){
        $model = D('version_announcement');
        $data        = I('post.');

        if (empty($data['title'])) {
            $this->error = '标题不能为空';
            return false;
        }

        if (empty($data['create_time'])) {
            $this->error = '日期不能为空';
            return false;
        }

        $data['create_time'] = date('YmdHis',strtotime($data['create_time']));
        $data['update_time'] = date('YmdHis',time());

        $result = $model->save($data);
        if (!$result){
            $this->error = '更新失败';
            return false;
        }
        return true;
    }

    public function deleteData(){
        $id        = I('id');
        if(!$id){
            $this->error = '参数不正确';
            return false;
        }
        $model = D('version_announcement');
        $info = $model->find($id);
        if(!$info){
            $this->error = '该数据不存在';
            return false;
        }

        $data['id'] =  $id;
        $data['delete_time'] =  date('YmdHis',time());
        $result = $model->save($data);
        if (!$result){
            $this->error = '删除失败';
            return false;
        }
        return true;
    }

}
