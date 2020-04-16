<?php
/**
 * Created by PhpStorm.
 * Date: 2018/10/2
 * Time: 10:39
 */
namespace  Admin\Controller;

class SystemMsgController extends AdminController
{
    /**
     * 系统消息-列表
     * @time 2018-10-2 10:42:43
     */
    public function index(){
        $map = [];
        $SystemMsgModel = M('system_msg');
        //分页
        $p=getpage($SystemMsgModel,$map,15);
        $page=$p->show();
        $list = $SystemMsgModel->order('id desc')->select();

        $this->assign('list',$list);
        $this->assign('table_data_page',$page);
        $this->display();
    }

    /**
     * 新增系统消息
     * @time 2018-10-2 14:48:32
     */
    public function add(){
        if (IS_POST) {
            $SystemMsgModel = D('system_msg');
            $data = I('post.');

            if(empty($data['title'])){
                $this->error('标题不能为空');
            }
            if(empty($data['user_id'])){
                $this->error('UID不能为空');
            }
            $user_info = M('user')->where(['userid'=>$data['user_id']])->count();
            if(!$user_info){
                $this->error('UID不存在，请重新输入');
            }
            if(empty($data['content'])){
                $this->error('请输入内容');
            }

            $data['create_time'] = date('YmdHis',time());
            $data['is_read'] = 0;
            if ($data) {
                $id = $SystemMsgModel->add($data);
                if ($id) {
                    $this->success('新增成功', U('index'));
                } else {
                    $this->error('新增失败');
                }
            } else {
                $this->error($SystemMsgModel->getError());
            }
        }

        $this->display('edit');
    }

    /**
     * 编辑系统消息
     * @param $id
     * @time 2018-10-2 14:49:55
     */
    public function edit($id){
        $SystemMsgModel = D('system_msg');
        if (IS_POST) {
            // 提交数据
            $data = I('post.');
            if(empty($data['title'])){
                $this->error('标题不能为空');
            }
            if(empty($data['user_id'])){
                $this->error('UID不能为空');
            }
            $user_info = M('user')->where(['userid'=>$data['user_id']])->count();
            if(!$user_info){
                $this->error('UID不存在，请重新输入');
            }
            if(empty($data['content'])){
                $this->error('请输入内容');
            }
            $data['is_read'] = 0;

            if ($data) {
                $result = $SystemMsgModel
                    ->save($data);
                if ($result) {
                    $this->success('更新成功', U('index'));
                } else {
                    $this->error('更新失败', $SystemMsgModel->getError());
                }
            } else {
                $this->error($SystemMsgModel->getError());
            }
        }

        $SystemMsgModel = D('system_msg');
        $info = $SystemMsgModel->find($id);
        $this->assign('info',$info);
        $this->display();
    }

    /**
     * 删除系统消息
     * @param mixed|string $model
     * @time 2018-10-2 15:03:26
     */
    public function setStatus($model = CONTROLLER_NAME){
        $ids = I('request.ids');
        if(!$ids){
            $this->error('请选择一条数据');
        }
        parent::setStatus($model);
    }

}