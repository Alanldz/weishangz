<?php
// +----------------------------------------------------------------------
// | 零云 [ 简单 高效 卓越 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.lingyun.net All rights reserved.
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Model\UcoinsModel;

/**
 * 系统配置控制器
 *
 */
class ConfigController extends AdminController
{

    /**
     * 系统信息
     * @author ldz
     * @time 2020/3/6 18:03
     */
    public function index()
    {
        $arrFiles = ['collections_img'];
        $data = [];
        foreach ($arrFiles as $file) {
            $data[$file] = D('config')->where(['name' => $file])->getField("value");
        }

        $this->assign('data', $data);
        $this->assign('methods', 'index');
        $this->display();
    }

    public function msgs()
    {
        if (IS_POST) {

            $content = I('MSG');
            $account = I('MSG_account');
            $password = I('MSG_password');
            $re1 = M('config', 'nc')->where(array('name' => 'MSG_password'))->setField('value', $password);
            $re2 = M('config', 'nc')->where(array('name' => 'MSG_account'))->setField('value', $account);
            $re3 = M('config', 'nc')->where(array('name' => 'MSG'))->setField('value', $content);

            return $this->success('修改成功', U('Config/msgs'));

        }
        $this->breadcrumb2 = '短信设置';

        $content = M('config', 'nc')->where(array('name' => 'MSG'))->getField('value');
        $account = M('config', 'nc')->where(array('name' => 'MSG_account'))->getField('value');
        $password = M('config', 'nc')->where(array('name' => 'MSG_password'))->getField('value');
        $this->assign('account', $account);
        $this->assign('password', $password);
        $this->assign('content', $content);
        $this->display();
    }

    /**
     * 获取某个分组的配置参数
     */
    public function group($group = 4)
    {
        //根据分组获取配置
        $map['group'] = array('eq', $group);
        $field = 'name,value,tip,type';
        $data_list = D('Config')->lists($map, $field);
        $display = array(1 => 'base', 2 => 'system', 3 => 'siteclose', 4 => 'fee', 5 => 'price', 6 => 'zhongchou');
        $this->assign('info', $data_list)
            ->display($display[$group]);
    }

    /**
     * 实时价格设置
     */
    public function group1()
    {
        //根据分组获取配置
        $config_object = D('Config');
        $growem = $config_object->where("name='growem'")->getField('value');
        $data_list = [];
        $arrCid = array_keys(UcoinsModel::$TYPE_TABLE);
        foreach ($arrCid as $cid) {
            $data_list[$cid] = D('coindets')->where("cid=" . $cid)->order('coin_addtime desc')->find();
        }

        $this->assign('info', $data_list)
            ->assign('growem', $growem)
            ->assign('methods', 'price')
            ->display("price");
    }

    /**
     * 基本设置-页面
     */
    public function group2($group = 1)
    {
        //根据分组获取配置
        $mobile = D('config')->where("name='sellmobile'")->getField("value");
        $collection_multiple = D('config')->where("name='collection_multiple'")->getField("value");
        $subaccounts_num = D('config')->where("name='subaccounts_num'")->getField("value");
        $email_server = D('config')->where("name='email_server'")->getField("value");
        $email_server_port = D('config')->where("name='email_server_port'")->getField("value");
        $email_address = D('config')->where("name='email_address'")->getField("value");
        $email_account_name = D('config')->where("name='email_account_name'")->getField("value");
        $email_account_pwd = D('config')->where("name='email_account_pwd'")->getField("value");

//        $arrFiles = ['can_not_use_qbb', 'qbb_total', 'qbb_end_num', 'qbb_send_num', 'qbb_sell_num', 'qbb_sell_multiple', 'stock_right_price',
//            'qbb_sell_start_time', 'qbb_sell_end_time', 'over_time_qbb_num', 'online_number', 'customer_tel', 'qbb_sell_acount', 'qbb_buy_acount', 'qbb_buy_num', 'sign_in',];
        $arrFiles = ['lock_warehouse_day_one', 'lock_warehouse_day_two', 'lock_warehouse_day_three', 'lock_warehouse_day_four',
            'lock_warehouse_multiple_one', 'lock_warehouse_multiple_two', 'lock_warehouse_multiple_three', 'lock_warehouse_multiple_four',
            'lock_warehouse_release_ratio', 'bai_bao_flow_release_ratio', 'share_to_can_flow_ratio', 'share_to_flow_ratio', 'thankful_award_ratio', 'direct_release_ratio',
            'ecology_to_bao_dan_fee_ratio', 'transfer_accounts_fee_ratio', 'bai_bao_flow_release_from', 'bai_bao_share_release_ratio', 'bai_bao_merits_release_ratio',
            'bai_bao_thankful_release_ratio', 'bai_bao_feedback_release_ratio', 'withdrawal_ratio', 'reg_send_flow'];
        $data = [];
        foreach ($arrFiles as $file) {
            $data[$file] = D('config')->where(['name' => $file])->getField("value");
        }

        $this->assign('mobile', $mobile)
            ->assign('collection_multiple', $collection_multiple)
            ->assign('subaccounts_num', $subaccounts_num)
            ->assign('email_server', $email_server)
            ->assign('email_server_port', $email_server_port)
            ->assign('email_address', $email_address)
            ->assign('email_account_name', $email_account_name)
            ->assign('email_account_pwd', $email_account_pwd)
            ->assign('data', $data)
            ->assign('methods', 'base')
            ->display("base");
    }

    //众筹设置
    public function group3()
    {
        //根据分组获取配置
        $time_n = time();
        $open_time = date("Y-m-d");

        $is_has = M('crowds')->where("open_time<=" . $time_n . " and status<>2")->order("create_time desc")->find();

        if ($is_has) {
            $jindu = $is_has['jindu'];
            $open_time = date("Y-m-d", $is_has['open_time']);
            $num = (int)$is_has['num'];
            $id = (int)$is_has['id'];
        }


        $this->assign('open_time', $open_time)->assign('is_has', $is_has)->assign('jindu', $jindu)->assign('id', $id)->assign('num', $num)->display("zhongchou");
    }


    //奖励设置
    public function group4()
    {

        $map['group'] = array('eq', 4);
        $field = 'name,value,tip,type';
        $data_list = D('Config')->lists($map, $field);
        $this->assign('info', $data_list);


        $map1['group'] = array('eq', 7);
        $data_list1 = D('Config')->lists($map1, $field);
        $this->assign('manage', $data_list1);

        $map2['group'] = array('eq', 9);
        $data_list2 = D('Config')->lists($map2, $field);
        $this->assign('qukuai', $data_list2);

        $map3['group'] = array('eq', 10);
        $data_list3 = D('Config')->lists($map3, $field);
        $this->assign('vip', $data_list3);
        $map4['group'] = array('eq', 6);
        $data_list4 = D('Config')->lists($map4, $field);
        $this->assign('fenx', $data_list4);


        $map5['group'] = array('eq', 8);
        $data_list5 = D('Config')->lists($map5, $field);
        $this->assign('zhuand', $data_list5);
        $this->display("fee");
    }

    /**
     * 管理奖保存配置
     */
    public function manage_Save()
    {
        $config = I('post.');
        if ($config && is_array($config)) {
            $config_object = D('Config');
            for ($i = 1; $i <= 3; $i++) {
                $map = array('name' => "guanli" . $i);
                // 如果值是数组则转换成字符串，适用于复选框等类型

                $config_object->where($map)->setField('value', $config["managej_" . ($i - 1)]);
                $config_object->where($map)->setField('tip', $config["manage_" . ($i - 1)]);
            }
        }

        $this->success('保存成功！');
    }

    /**
     * 区块奖保存配置
     */
    public function qukuai_Save()
    {
        $config = I('post.');
        if ($config && is_array($config)) {
            $config_object = D('Config');
            for ($i = 1; $i <= 5; $i++) {
                $map = array('name' => "qukuai" . $i);
                // 如果值是数组则转换成字符串，适用于复选框等类型

                $config_object->where($map)->setField('value', $config["qukuaij_" . ($i - 1)]);
                $config_object->where($map)->setField('tip', $config["qukuai_" . ($i - 1)]);
            }
        }

        $this->success('保存成功！');
    }

    /**
     * 区块奖保存配置
     */
    public function vip_Save()
    {
        $config = I('post.');
        if ($config && is_array($config)) {
            $config_object = D('Config');
            for ($i = 1; $i <= 2; $i++) {
                $map = array('name' => "vip" . $i);
                // 如果值是数组则转换成字符串，适用于复选框等类型

                $config_object->where($map)->setField('value', $config["vipj_" . ($i - 1)]);
                $config_object->where($map)->setField('tip', $config["vip_" . ($i - 1)]);
            }
        }

        $this->success('保存成功！');
    }

    /**
     * 区块奖保存配置
     */
    public function fenx_Save()
    {
        $config = I('post.');
        if ($config && is_array($config)) {
            $config_object = D('Config');
            for ($i = 1; $i <= 4; $i++) {
                $map = array('name' => "zhitui" . $i);
                // 如果值是数组则转换成字符串，适用于复选框等类型
                $config_object->where($map)->setField('value', $config["fenxj_" . ($i - 1)]);
                $config_object->where($map)->setField('tip', $config["fenx_" . ($i - 1)]);


                $map1 = array('name' => "zhuand" . $i);
                $config_object->where($map1)->setField('value', $config["zhuandj_" . ($i - 1)]);
                $config_object->where($map1)->setField('tip', $config["fenx_" . ($i - 1)]);
            }
        }

        $this->success('保存成功！');
    }

    /**
     * 批量保存配置
     */
    public function groupSave()
    {
        $config = I('post.');
        unset($config['file']);
        if ($config && is_array($config)) {
            $config_object = D('Config');
            foreach ($config as $name => $value) {
                $map = array('name' => $name);
                // 如果值是数组则转换成字符串，适用于复选框等类型
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $config_object->where($map)->setField('value', $value);
            }
        }

        $this->success('保存成功！');
    }

    /**
     * 保存实时价格
     */
    public function groupSave1()
    {
        $config = I('post.');

        $config_object = D('Config');
        $growem = $config["growem"];
        $config_object->where("name='growem'")->setField('value', $growem);

        $arr = UcoinsModel::$TYPE_TABLE;
        $arrCid = array_keys($arr);
        $time = time();
        foreach ($arrCid as $cid) {
            $coinone['cid'] = $cid;
            $coinone['coin_price'] = $config["s" . $cid];
            $coinone['coin_name'] = $arr[$cid];
            $coinone['coin_addtime'] = $time;
            M('coindets')->add($coinone);
        }

        $this->success('保存成功！');
    }

    /**
     * 基本设置
     */
    public function groupSave2()
    {
        $jifen = I('post.jifen');
        $regjifen = I('post.regjifen');

        D('Config')->where("name='jifen'")->setField('value', $jifen);
        D('Config')->where("name='regjifen'")->setField('value', $regjifen);

        $this->success('保存成功！');
    }

    /**
     * 基本设置
     */
    public function tuijian()
    {
        $jifens = I('post.jifens');
        $rens = I('post.rens');

        D('Config')->where("name='jifens'")->setField('value', $jifens);
        D('Config')->where("name='rens'")->setField('value', $rens);

        $this->success('保存成功！');
    }

    /**
     * 基本设置-设置卖出发送手机号码
     */
    public function sellSendMobile()
    {
        $mobile = I('post.mobile');
        D('Config')->where("name='sellmobile'")->setField('value', $mobile);
        $this->success('保存成功！');
    }

    /**
     * 设置E派总值、原动力总值
     */
    public function setEzong()
    {
        $Epaizhi = I('post.Epaizhi');
        $yuandongli = I('post.yuandongli');
        D('Config')->where("name='Epaizhi'")->setField('value', $Epaizhi);
        D('Config')->where("name='yuandongli'")->setField('value', $yuandongli);
        $this->success('保存成功！');
    }

    /**
     * 发布众筹
     */
    public function groupSave3()
    {
        $num = I('post.num', 'intval', 0);
        $dprice = (float)I('post.dprice');
        $date = I('post.open_time');
        $jindu = I('post.jindu', 'intval', 0);
        $open_time = strtotime($date);

        //把其它众筹项目状态改为2，表示已完成
        M('crowds')->where("status<>2")->save(array("status" => 2));

        $datas["num"] = $num;
        $datas["dprice"] = $dprice;
        $datas["open_time"] = $open_time;
        $datas["create_time"] = time();
        $datas["status"] = 0;
        $datas["jindu"] = $jindu;
        M('crowds')->add($datas);

        $this->success('发布成功！');
    }

    /**
     * 修改众筹
     */
    public function groupSave4()
    {
        $id = I('post.tid', 'intval', 0);
        $jindu = (float)I('post.jindu');

        $datas["jindu"] = $jindu;
        M('crowds')->where("id=" . $id)->save($datas);

        $this->success('修改成功！');
    }

    public function BaseSave()
    {
        $ids = I('post.ids');
        $limit_num = I('post.limit_num');
        $test = M('limit');
        foreach ($ids as $k => $v) {
            $where['id'] = $v;
            $data['limit_num'] = $limit_num[$k];
            $test->where($where)->save($data);
        }
        $this->success('保存成功！');
    }

    public function sitecloseSave()
    {
        $config = I('post.');
        $key = (array_keys($config));

        if ($config && is_array($config)) {
            $map['name'] = $key[0];
            $config_object = D('Config');
            $data['value'] = $config[$key[0]];
            $data['tip'] = $config['tip'];

            $config_object->where($map)->save($data);
        }

        $this->success('保存成功！');
    }

    public function turntable()
    {
        $info = M('turntable_lv')->order('id')->find();
        $this->assign('info', $info);
        $this->display();
    }

    //保存转盘数据
    public function savezhuanpan()
    {
        $data = I('post.');
        $info = M('turntable_lv')->where('id=1')->save($data);
        $this->success('保存成功！');
    }

    public function tool()
    {
        $info = M('tool')->order('id')->select();
        $this->assign('info', $info);
        $this->display();
    }

    //保存转盘数据
    public function savetool()
    {
        $ids = I('post.id');
        $nums = I('post.num');
        $tool = M('tool');
        foreach ($ids as $k => $val) {
            $tool->where(array('id' => $val))->save(array('t_num' => $nums[$k]));
        }
        $this->success('保存成功！');
    }

    /**
     * 释放原动力
     * @time 2018-7-21 22:40:14
     */
    public function setisReward()
    {
        if (IS_POST) {
            M("user")->where(['is_reward' => 1])->save(['is_reward' => 0]);
            ajaxReturn('释放成功', 1);

        }
        $this->display();
    }

    /**
     * 设置归集倍数
     */
    public function setMultiple()
    {
        $collection_multiple = intval(I('post.collection_multiple'));
        if ($collection_multiple == 0) {
            $this->error('倍数不能为0');
        }
        D('Config')->where("name='collection_multiple'")->setField('value', $collection_multiple);
        $this->success('保存成功！');
    }

    /**
     * 设置归集倍数
     */
    public function setNotUseQBB()
    {
        $collection_qbb = intval(I('post.not_use_qbb'));
        if ($collection_qbb < 0) {
            $this->error('不可用qbb不能为负数');
        }
        if (floor($collection_qbb * 100) != $collection_qbb * 100) {
            $this->error('不可用qbb仅不允许超出两位小数');
        }
        D('Config')->where("name='can_not_use_qbb'")->setField('value', $collection_qbb);
        $this->success('保存成功！');
    }

    /**
     * 设置会员子账户个数
     */
    public function setSubaccounts()
    {
        $subaccounts_num = intval(I('post.subaccounts_num'));
        D('Config')->where("name='subaccounts_num'")->setField('value', $subaccounts_num);
        $this->success('保存成功！');
    }

    /**
     * 设置邮箱配置
     */
    public function setEmailConfiguration()
    {
        $email_server = trim(I('post.email_server'));
        $email_server_port = trim(I('post.email_server_port'));
        $email_address = trim(I('post.email_address'));
        $email_account_name = trim(I('post.email_account_name'));
        $email_account_pwd = trim(I('post.email_account_pwd'));
        if ($email_server == '') {
            $this->error('请输入邮箱服务器');
        }
        if ($email_server_port == '') {
            $this->error('请输入服务器端口');
        }
        if ($email_address == '') {
            $this->error('请输入发信邮箱地址');
        }
        if ($email_account_name == '') {
            $this->error('请输入SMTP账户名');
        }
        if ($email_account_pwd == '') {
            $this->error('请输入SMTP账户名密码');
        }
        M()->startTrans();
        try {
            $res = D('Config')->where("name='email_server'")->setField('value', $email_server);
            $res1 = D('Config')->where("name='email_server_port'")->setField('value', $email_server_port);
            $res2 = D('Config')->where("name='email_address'")->setField('value', $email_address);
            $res3 = D('Config')->where("name='email_account_name'")->setField('value', $email_account_name);
            $res4 = D('Config')->where("name='email_account_pwd'")->setField('value', $email_account_pwd);
            if ($res === false || $res1 === false || $res2 === false || $res3 === false || $res4 === false) {
                throw new \Exception('修改失败');
            }
            M()->commit();
            $this->success('保存成功！');
        } catch (\Exception $ex) {
            M()->rollback();
            $this->error('保存失败！');
        }
    }

    /**
     * 修改设置的值
     */
    public function setConfig()
    {
        $data = I('post.config');
        M()->startTrans();
        foreach ($data as $field => $value) {
            if (!is_numeric($value)) {
                M()->rollback();
                $this->error('输入格式有误');
            }
            if ($value < 0) {
                M()->rollback();
                $this->error('输入值不能小于零');
            }

            $res = D('Config')->where(['name' => $field])->setField('value', $value);
            if ($res === false) {
                M()->rollback();
                $this->error('保存失败，请重试');
            }
        }
        M()->commit();
        $this->success('保存成功！');
    }

    /**
     * 上传收款账户图片
     * @author ldz
     * @time 2020/3/9 10:39
     */
    public function setCollectionsImg()
    {
        $data = uploading('image', 'admin_collections');
        if (!$data['status']) {
            $this->error($data['msg']);
        } else {
            $res = M('config')->where(['name' => 'collections_img'])->save(['value' => $data['path']['filepath']]);
            if ($res === false) {
                $this->error('上传图片失败');
            }
            $this->success('上传成功');
        }
    }
}
