<?php

namespace SAdmin\Model;

class OrderModel
{

    /**
     * 显示订单状态单位分页
     * @param $search
     * @return array
     */
    public function show_order_page($search)
    {
        $sql = "SELECT o.order_id,o.order_no,o.uid,o.status,o.pay_time,o.time,o.buy_price,o.ecological_total_assets,o.flow_pass_card,o.flow_amount,o.product_integral,o.pay_type,o.shop_type,o.buy_name,o.buy_phone,o.seluid FROM "
            . C('DB_PREFIX') . 'order o JOIN ysk_user u ON u.userid = o.uid WHERE 1=1 ';

        if (isset($search['order_no'])) {
            $sql .= " and  o.order_no like '%" . $search['order_no'] . "%'";
        }

        if (isset($search['user_id'])) {
            $sql .= " and o.uid like '%" . $search['user_id'] . "%'";
        }

        if (isset($search['user_phone'])) {
            $sql .= " and u.mobile like '%" . $search['user_phone'] . "%'";
        }

        if (isset($search['buy_name'])) {
            $sql .= " and o.buy_name like '%" . $search['buy_name'] . "%'";
        }

        if (isset($search['buy_phone'])) {
            $sql .= " and o.buy_phone like '%" . $search['buy_phone'] . "%'";
        }

        if (isset($search['status'])) {
            $sql .= " and o.status like '%" . $search['status'] . "%'";
        }

        $count = count(M()->query($sql));

        $Page = new \Think\Page($count, C('BACK_PAGE_NUM'));
        $Page->lastSuffix = false;
        $Page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $Page->setConfig('prev', '上一页');
        $Page->setConfig('next', '下一页');
        $Page->setConfig('last', '末页');
        $Page->setConfig('first', '首页');
        $Page->parameter = I('get.');
        $show = $Page->show();// 分页显示输出

        $sql .= 'ORDER BY field(o.status,1,2,0,3),o.time DESC LIMIT ' . $Page->firstRow . ',' . $Page->listRows;

        $list = M()->query($sql);
        return array(
            'empty' => '<tr><td colspan="20" style="text-align: center;line-height: 50px">~~暂无数据</td></tr>',
            'list' => $list,
            'page' => $show
        );

    }

    //订单信息
    public function order_info($id)
    {
        $orderInfo = M('order')->where(['order_id' => $id])->find();
        return $orderInfo;
    }

    function addOrderHistory($order_id, $data)
    {
        $order['order_id'] = $order_id;
        $order['date_modified'] = time();
        $order['order_status_id'] = $data['order_status_id'];
        M('Order')->save($order);

        $oh['order_id'] = $order_id;
        $oh['order_status_id'] = $data['order_status_id'];
        $oh['notify'] = (isset($data['notify']) ? (int)$data['notify'] : 0);
        $oh['comment'] = strip_tags($data['comment']);
        $oh['date_added'] = time();
        $oh_id = M('OrderHistory')->add($oh);

        return $oh_id;
    }

    public function getOrderHistories($order_id)
    {
        $query = M()->query("SELECT oh.date_added, os.name AS status, oh.comment, oh.notify FROM "
            . C('DB_PREFIX') . "order_history oh LEFT JOIN "
            . C('DB_PREFIX') . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id
            . "' ORDER BY oh.date_added ASC");

        return $query;
    }

    function del_order($id)
    {
        M('order')->where(array('order_id' => $id))->delete();
        M('order_detail')->where(array('order_id' => $id))->delete();
        return array(
            'status' => 'success',
            'message' => '删除成功',
            'jump' => U('Order/index')
        );
    }

    /**
     * 订单导出数据
     * @return mixed
     * @throws \Think\Exception
     */
    public static function orderExportData()
    {
        $status = I('status');
        $mobile = trim(I('user_phone'));
        $where = [];
        if ($status != -1) {
            $where['o.status'] = $status;
        }
        if ($mobile) {
            $where['u.mobile'] = ['like', '%' . $mobile . '%'];
        }

        $field = "o.order_id,o.order_no,o.order_sellerid,o.buy_name,o.buy_phone,o.buy_address,o.status,o.pay_time,o.time";
        $list = M('order o')->field($field)->join('ysk_user u on u.userid = o.uid')->where($where)->order('order_id asc')->select();
        foreach ($list as $key => $item) {
            $orderDetail = M('order_detail')->where(['order_id' => $item['order_id']])->field('com_num,com_name')->select();
            $num = 0;
            foreach ($orderDetail as $v) {
                $num += $v['com_num'];
            }
            $list[$key]['product_name'] = $orderDetail[0]['com_name'];
            $list[$key]['num'] = $num;
            $list[$key]['pay_time'] = toDate($item['pay_time']);
            $list[$key]['time'] = toDate($item['time']);

            $list[$key]['status_text'] = getOrderStatusItems($item['status']);
            $list[$key]['business_name'] = M('verify_list')->where(['uid' => $item['order_sellerid']])->getField('store_name');
        }
        return $list;
    }

}
