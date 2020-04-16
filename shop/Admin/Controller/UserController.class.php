<?php

namespace Admin\Controller;

use Admin\Model\StoreModel;
use Admin\Model\UserModel;
use Common\Model\UcoinsModel;
use Common\Util\Constants;

/**
 * 用户控制器
 */
class UserController extends AdminController
{
    /**
     * 用户列表
     */
    public function index()
    {
        // 搜索
        $keyword = I('keyword', '', 'string');
        $queryType = I('queryType', 'userid', 'string');
        if ($keyword) {
            $condition = $keyword;
            $map[$queryType] = $condition;
        }

        //按日期搜索
        $date = date_query('reg_date');
        if ($date) {
            $where = $date;
            if (isset($map))
                $map = array_merge($map, $where);
            else
                $map = $where;
        }
        // 获取所有用户
        $user = M('user a');
        if (!isset($map)) {
            $map = true;
        }

        //分页
        $table = $user->join('ysk_store b on a.userid = b.uid', 'left');
        $p = getpage($table, $map, 15);
        $page = $p->show();

        $data_list = $table
            ->field('a.userid,a.level,a.username,a.email,a.yinbi,a.account,a.mobile,a.junction_id,a.account_type,a.reg_date,a.activation_time,a.status,a.pid,
            b.cangku_num,b.fengmi_num,b.purchase_integral,b.can_flow_amount,b.current_assets')
            ->where($map)
            ->order('a.userid desc')
            ->select();

        //取管理员会员列表的权限
        $admin_id = is_login();
        $hylbs = "1,2,3,4,5";
        $auth_id = M('admin')->where(array('id' => $admin_id))->getField('auth_id');
        if ($auth_id <> 1) {
            $hylbs = M('group')->where(array('auth_id' => $auth_id))->getField('hylb');
        }

        foreach ($data_list as $key => $item) {
            $data_list[$key]['pass_card_amount'] = UcoinsModel::getAmount($item['userid']);
        }

        $hylb = explode(",", $hylbs);
        $this->assign('hylb', $hylb);
        $this->assign('list', $data_list);
        $this->assign('queryType', $queryType);
        $this->assign('table_data_page', $page);
        $this->display();
    }

    /**
     * 新增用户
     */
    public function add()
    {
        if (IS_POST) {
            $model = new UserModel();
            $res = $model->addUser();
            if (!$res) {
                $this->error($model->getError());
            }
            $this->success('新增成功', U('index'));
        }
        $this->display();
    }

    /**
     * 开通交易列表
     * @author ldz
     * @time 2020/3/9 15:33
     */
    public function recharge()
    {
        $recharge = M('recharge r');
        // 搜索
        $keyword = trim(I('keyword'));
        if ($keyword) {
            $map['u.mobile'] = ['like', '%' . $keyword . '%'];
        }
        $map['delete_time'] = '';
        $order_str = 'r.id desc';
        //分页
        $table = $recharge->join('ysk_user u on r.uid = u.userid', 'left');
        $p = getpage($table, $map, 15);
        $page = $p->show();
        $data_list = $table
            ->field('u.userid,u.username,u.mobile,r.id,r.amount,r.status,r.image,r.create_time')
            ->where($map)
            ->order($order_str)
            ->select();
        foreach ($data_list as $k => $v) {
            $data_list[$k]['create_time'] = toDate(strtotime($v['create_time']));
            if ($v['status'] == 1) {
                $data_list[$k]['status_name'] = '审核通过';
            } elseif ($v['status'] == 2) {
                $data_list[$k]['status_name'] = '审核未通过';
            } else {
                $data_list[$k]['status_name'] = '待审核';
            }
        }
        $this->assign('list', $data_list);
        $this->assign('keyword', $keyword);
        $this->assign('table_data_page', $page);
        $this->display();
    }

    /**
     * 充值-提交
     */
    public function submitRecharge()
    {
        $id = intval(I('id'));
        if (empty($id)) {
            $this->error('参数不足');
        }
        $model = new UserModel();
        $res = $model->recharge($id);
        if (!$res) {
            $this->error($model->getError());
        }

        $this->success('更新成功');
    }

    /**
     * 编辑用户信息
     * @throws \Think\Exception
     */
    public function edit()
    {
        if (IS_POST) {
            $model = new UserModel();
            $res = $model->editUser();
            if (!$res) {
                $this->error($model->getError());
            }
            $this->success('更新成功', U('index'));
        }
        // 获取账号信息
        $user_id = intval(I('id'));
        $info = M('user')->find($user_id);
        unset($info['password']);
        $parent = M('user')->where(array('userid' => $info['pid']))->getField('account');
        $info['parent'] = $parent ? $parent : '无';
        $quanxian = explode("-", $info['quanxian']);
        $userLevel = Constants::getUserLevelItems();
        $this->assign('info', $info);
        $this->assign('userLevel', $userLevel);
        $this->assign('quanxian', $quanxian);
        $this->display();
    }

    /**
     * 设置一条或者多条数据的状态
     * @param mixed|string $model
     */
    public function setStatus($model = CONTROLLER_NAME)
    {
        $ids = I('request.ids');
        if (is_array($ids)) {
            if (in_array('1', $ids)) {
                $this->error('超级管理员不允许操作');
            }
        } else {
            if ($ids === '1') {
                $this->error('超级管理员不允许操作');
            }
        }
        parent::setStatus($model);
    }

    /**
     * 设置会员隐蔽的状态
     * @param $model
     */
    public function setStatus1($model = CONTROLLER_NAME)
    {
        $id = (int)I('request.id');
        $user_id = (int)I('request.userid');

        $user_object = D('User');
        $result = D('User')->where(array('userid' => $user_id))->setField('yinbi', $id);
        if ($result) {
            $this->success('更新成功', U('index'));
        } else {
            $this->error('更新失败', $user_object->getError());
        }
    }

    /**
     * 编辑用户财富
     */
    public function AddFruits()
    {
        if (IS_POST) {
            $model = new StoreModel();
            $res = $model->changeWealth();
            if (!$res) {
                $this->error($model->getError());
            }
            $this->success('修改成功');
        }
        // 获取账号信息
        $user_id = intval(I('id'));
        $userInfo = D('User')->field('userid,username,mobile')->find($user_id);
        $storeInfo = D('store')->where(['uid' => $user_id])->field('cangku_num,fengmi_num,can_flow_amount,current_assets')->find();
        $storeInfo['pass_card_amount'] = UcoinsModel::getAmount($user_id);

        $this->assign('userInfo', $userInfo);
        $this->assign('storeInfo', $storeInfo);
        $this->display();
    }

    /**
     * 前台用户登录
     */
    public function userLogin()
    {
        $user_id = I('userid', 0, 'intval');
        $user = D('Home/User');
        $info = $user->find($user_id);
        if (empty($info)) {
            $this->error('该用户不存在', U('Home/Login/login'));
        }

        $login_id = $user->auto_login($info);
        if ($login_id) {
            session('in_time', time());
            session('login_from_admin', 'admin', 10800);
            $this->redirect('Shop/Index/index');
        } else {
            $this->error('登录失败', U('Home/Login/login'));
        }
    }

    /**
     * 资产购入列表
     */
    public function AssetPurchaseList()
    {
        $list = M('exchange')->where(['delete_time' => ''])->select();

        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 资产购入审核
     */
    public function audit()
    {
        $id = trim(I('id'));
        $type = trim(I('type'));

        $exchange = M('exchange')->where(['id' => $id])->find();
        if (!$exchange) {
            $this->error('没有找到相关信息');
        }

        if ($type == 'through') {
            $params['status'] = 1;
        }

        if ($type == 'not_through') {
            $params['status'] = -1;
        }

        $res = M('exchange')->where(['id' => $id])->save($params);
        if (!$res) {
            $this->error('审核失败');
        }

        if ($type == 'not_through') {
            $pay_nums = $exchange['pay_nums'];
            $cangku_num = M('store')->where(['uid' => $exchange['uid']])->getField('cangku_num');
            $allNum = $pay_nums + $cangku_num;
            M('store')->where(['uid' => $exchange['uid']])->save(['cangku_num' => $allNum]);
        }

        $this->success('审核成功');
    }

    /**
     * 手机充值记录
     */
    public function rechargeRecord()
    {
        // 搜索
        $keyword = I('keyword', '', 'string');
        $querytype = I('querytype', 'userid', 'string');
        $map = [];
        if ($keyword) {
            $map[$querytype] = array("LIKE", '%' . $keyword . '%');
        }
        //按日期搜索
        $date = date_search('create_time');
        if ($date) {
            $where = $date;
            if (isset($map))
                $map = array_merge($map, $where);
            else
                $map = $where;
        }

        // 获取所有用户
        $recharge_record = M('recharge_record');
        //分页
        $p = getpage($recharge_record, $map, 10);
        $page = $p->show();
        $field = 'id,uid,mobile,price,status,create_time,game_area,message';
        $record_list = $recharge_record->field($field)->where($map)->order('id desc')->select();

        $this->assign('list', $record_list);
        $this->assign('querytype', $querytype);
        $this->assign('table_data_page', $page);
        $this->display();
    }

    /**
     * 求购列表
     */
    public function buyList()
    {
        $map = [];
        $model = M('buy_list');
        $p = getpage($model, $map, 10);
        $page = $p->show();
        $list = $model->order('id desc')->select();

        $this->assign('list', $list);
        $this->assign('table_data_page', $page);
        $this->display();
    }

    //求购列表审核
    public function buy_audit()
    {
        $id = trim(I('id'));
        $exchange = M('buy_list')->where(['id' => $id])->find();
        if (!$exchange) {
            $this->error('没有找到相关信息');
        }

        $params['status'] = 1;
        $res = M('buy_list')->where(['id' => $id])->save($params);
        if (!$res) {
            $this->error('审核失败');
        }

        $this->success('审核成功');
    }

    /**
     * 资产管理
     */
    public function asset_management()
    {
        // 搜索
        $keyword = I('keyword', '', 'string');
        $querytype = I('querytype', 'userid', 'string');
        $map = [];
        if ($keyword) {
            $map[$querytype] = array("LIKE", '%' . $keyword . '%');
        }
        //类型
        $type_list = UcoinsModel::$TYPE_TABLE;

        $model = M('ucoins');
        //分页
        $allUcoins = $model->where($map)->group('c_uid')->field('id')->select();//连惯操作后会对join等操作进行重置
        $count = count($allUcoins);
        $p = new \Think\PageAdmin($count, 10);
        $p->lastSuffix = false;
        $p->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $p->setConfig('prev', '上一页');
        $p->setConfig('next', '下一页');
        $p->setConfig('last', '末页');
        $p->setConfig('first', '首页');
        $p->parameter = I('get.');
        $model->limit($p->firstRow, $p->listRows);
        $page = $p->show();

        //列表
        $arrData = $model->where($map)->group('c_uid')->field('c_uid')->order('c_uid desc')->select();
        $record_list = [];
        foreach ($arrData as $key => $v) {
            $arrType = [];
            foreach ($type_list as $cid => $item) {
                $c_nums = $model->where(['cid' => $cid, 'c_uid' => $v['c_uid']])->getField('c_nums');
                $arrType[$cid] = $c_nums ? $c_nums : 0;
            }
            $record_list[$v['c_uid']] = $arrType;
        }

        $this->assign('type_list', $type_list);
        $this->assign('list', $record_list);
        $this->assign('querytype', $querytype);
        $this->assign('table_data_page', $page);
        $this->display();
    }

    /**
     * 资产管理-编辑
     */
    public function editManage()
    {
        if (IS_POST) {
            $arrPostData = I('value');
            $c_uid = I('c_uid');
            if (!$c_uid || empty($arrPostData)) {
                $this->error('参数不能为空,请重新选择');
            }
            foreach ($arrPostData as $key => $value) {
                $ucoins = M('ucoins')->where(['cid' => $key, 'c_uid' => $c_uid])->getField('id');
                if (empty($ucoins)) {   //新增数据
                    $add = [
                        'cid' => $key,
                        'c_nums' => $value,
                        'c_uid' => $c_uid
                    ];
                    M('ucoins')->add($add);
                } else {              //修改数据
                    $updataData = [
                        'id' => $ucoins,
                        'c_nums' => $value
                    ];
                    M('ucoins')->save($updataData);
                }
            }
            $this->success('修改成功');
        }

        $uid = intval(I('c_uid'));
        $type_list = UcoinsModel::$TYPE_TABLE;
        $record_list = [];
        foreach ($type_list as $cid => $item) {
            $c_nums = M('ucoins')->where(['cid' => $cid, 'c_uid' => $uid])->getField('c_nums');
            $record_list[$cid] = $c_nums ? $c_nums : 0;
        }
        $this->assign('uid', $uid);
        $this->assign('type_list', $type_list);
        $this->assign('list', $record_list);
        $this->display();
    }

    /**
     * 锁仓列表
     * @throws \Think\Exception
     * @author ldz
     * @time 2020/3/9 14:21
     */
    public function lockWarehouseList()
    {
        // 搜索
        $searchType = trim(I('searchType', 'mobile'));
        $keyword = trim(I('keyword'));
        $map = [];
        if ($searchType == 'mobile' && !empty($keyword)) {
            $map['mobile'] = ['like', '%' . $keyword . '%'];
        }

        //分页
        $table = M('lock_warehouse_pass lwp')->join('ysk_user u on lwp.uid = u.userid', 'left');
        $p = getpage($table, $map, 10);
        $page = $p->show();
        $data_list = $table->field('u.username,u.mobile,lwp.*')->where($map)->order('lwp.id desc')->select();
        foreach ($data_list as $k => $item) {
            $data_list[$k]['status_name'] = Constants::getLockWarehouseItems($item['status']);
        }

        $this->assign('list', $data_list);
        $this->assign('table_data_page', $page);
        $this->assign('keyword', $keyword);
        $this->display();
        $this->display();
    }

}
