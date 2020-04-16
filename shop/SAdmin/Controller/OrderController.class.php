<?php

namespace SAdmin\Controller;

use SAdmin\Model\OrderModel;

class OrderController extends CommonController
{

    protected function _initialize()
    {
        parent::_initialize();
        $this->breadcrumb1 = '订单';
    }

    public function index()
    {
        $model = new OrderModel();
        $filter = I('get.');

        $search = array();

        if (isset($filter['order_no'])) {
            $search['order_no'] = $filter['order_no'];
            $this->order_no = $search['order_no'];
        }
        if (isset($filter['user_id'])) {
            $search['user_id'] = $filter['user_id'];
            $this->user_id = $search['user_id'];
        }
        if (isset($filter['user_phone'])) {
            $search['user_phone'] = $filter['user_phone'];
            $this->user_phone = $search['user_phone'];
        }
        if (isset($filter['buy_name'])) {
            $search['buy_name'] = $filter['buy_name'];
            $this->buy_name = $search['buy_name'];
        }

        if (isset($filter['buy_phone'])) {
            $search['buy_phone'] = $filter['buy_phone'];
            $this->buy_phone = $search['buy_phone'];
        }

        if (isset($filter['status']) && $filter['status'] != '-1') {
            $search['status'] = $filter['status'];
            $this->status = $search['status'];
        }else{
            $this->status = -1;
        }

        $data = $model->show_order_page($search);
        $this->assign('empty', $data['empty']);// 赋值数据集
        $this->assign('list', $data['list']);// 赋值数据集
        $this->assign('page', $data['page']);// 赋值分页输出
        $this->assign('breadcrumb2', '订单管理');
        $this->display();
    }


    public function duobao()
    {
        $this->breadcrumb2 = '夺宝订单';
        $model = new OrderModel();
        $filter = I('get.');
        $search = array();
        if (isset($filter['order_no'])) {
            $search['order_no'] = $filter['order_no'];
            $this->order_no = $search['order_no'];
        }
        if (isset($filter['user_phone'])) {
            $search['user_phone'] = $filter['user_phone'];
            $this->user_phone = $search['user_phone'];
        }
        if (isset($filter['buy_name'])) {
            $search['buy_name'] = $filter['buy_name'];
            $this->buy_name = $search['buy_name'];
        }

        if (isset($filter['buy_phone'])) {
            $search['buy_phone'] = $filter['buy_phone'];
            $this->buy_phone = $search['buy_phone'];
        }

        if (isset($filter['status'])) {
            $search['status'] = $filter['status'];
            $this->status = $search['status'];
        }
        $search['is_duobao'] = 2;
        $this->status = $search['is_duobao'];
        $data = $model->show_order_page($search);
        $this->assign('empty', $data['empty']);// 赋值数据集
        $this->assign('list', $data['list']);// 赋值数据集
        $this->assign('page', $data['page']);// 赋值分页输出
        $this->display();
    }

    /**
     * 发货
     */
    public function give()
    {
        $oid = intval(I("oid"));
        $kd_no = trim(I("kd_no"));
        $kd_name = trim(I("kd_name"));

        if (!$kd_name) {
            $this->error("货运方式不为空");
        }
        if (!$kd_no) {
            $this->error("快递单号不为空");
        }

        //该订单是否存在 并且是待发货状态
        $order = M("order")->where(array('order_id' => $oid))->field('status')->find();
        if (empty($order)) {
            $this->error("该订单不存在");
        }

        if ($order['status'] == 1) {
            $res = M("order")->where(array('order_id' => $oid))->save(['kd_no' => $kd_no, 'kd_name' => $kd_name, 'status' => 2]);
            if (!$res) {
                $this->error("发货失败");
            }
            $this->success("发货成功");
        } elseif ($order['status'] == 2) {
            $res = M("order")->where(array('order_id' => $oid))->save(['kd_no' => $kd_no, 'kd_name' => $kd_name]);
            if (!$res) {
                $this->error("修改失败");
            }
            $this->success("修改成功");
        } else {
            $this->error("该订单未支付，或者交易完成");
        }
    }

    /**
     * 打印订单
     */
    function print_order()
    {
        $model = new OrderModel();

        $this->order = $model->order_info(I('id'));
        $this->print = true;
        $this->display('./Themes/Home/default/Mail/order.html');
    }

    /**
     * 订单详情
     */
    public function show_order()
    {
        $model = new OrderModel();

        $this->assign('order', $model->order_info(I('id')));
        $this->assign('crumbs', '订单详情');
        $this->assign('breadcrumb2', '订单管理');
        $this->display('show');
    }

    function history()
    {
        $model = new OrderModel();
        if (IS_POST) {
            if (I('order_status_id') == C('cancel_order_status_id')) {
                $Order = new \Home\Model\OrderModel();
                $Order->cancel_order($_GET['id']);
                storage_user_action(session('user_auth.uid'), session('user_auth.username'), C('BACKEND_USER'), '取消了订单  ' . $_GET['id']);
                $result = true;
            } else {
                $result = $model->addOrderHistory($_GET['id'], $_POST);
            }

            /**
             * 判断是否选择了通知会员，并发送邮件
             */
            if (I('notify') == 1) {
                $order_info = M('order')->find($_GET['id']);

                $status = get_order_status_name(I('order_status_id'));

                $model = new \SAdmin\Model\OrderModel();
                $this->order = $model->order_info($_GET['id']);
                $this->seller_comment = $_POST['comment'];
                $html = $this->fetch('./Themes/Home/default/Mail/order.html');
                think_send_mail($order_info['email'], $order_info['name'], '订单-' . $status . '-' . C('SITE_NAME'), $html);
            }

            if ($result) {
                $this->success = '新增成功！！';
            } else {
                $this->error = '新增失败！！';
            }
        }

        $results = $model->getOrderHistories($_GET['id']);

        foreach ($results as $result) {
            $histories[] = array(
                'notify' => $result['notify'] ? '是' : '否',
                'status' => $result['status'],
                'comment' => nl2br($result['comment']),
                'date_added' => date('Y/m/d H:i:s', $result['date_added'])
            );
        }

        $this->histories = $histories;

        $this->display();
    }

    function del()
    {
        $model = new OrderModel();
        $return = $model->del_order(I('get.id'));
        $this->osc_alert($return);
    }

    function odel()
    {
        $sql = 'truncate table yilianka_order';
        $sql2 = 'truncate table yilianka_order_data';
        $ok = M()->execute($sql);
        M()->execute($sql2);
        echo "<script>alert('删除成功！');history.go(-1);</script>";
    }

    function oodel()
    {
        M('order')->where('order_status_id=3')->delete();
        echo "<script>alert('删除成功！');history.go(-1);</script>";
    }

    /**
     * 导出订单
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \Think\Exception
     */
    public function order_export()
    {
        $data = OrderModel::orderExportData();
        $indexKey = ['order_no','business_name','product_name','num','buy_name','buy_phone','buy_address','status_text','pay_time','time'];
        $headArr = array('订单号', '店铺名', '商品名', '数量', '收货人', '手机号', '收货地址', '订单状态', '支付时间', '下单时间');//头部信息
        $filename = "订单列表" . date('Ymd', time());//文件名称

        exportExcel($data, $indexKey, $filename, $headArr, true);
    }

}
