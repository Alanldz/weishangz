<?php

namespace SAdmin\Controller;

use Common\Util\Constants;
use SAdmin\Model\GoodsModel;
use SAdmin\Model\VerifyListModel;
use Think\Exception;

class GoodsController extends CommonController
{

    protected function _initialize()
    {
        parent::_initialize();
        $this->breadcrumb1 = '商城';
        $this->breadcrumb2 = '商品管理';
    }

    /**
     * 仓库区商品列表
     * @throws \Think\Exception
     * @author ldz
     * @time 2020/2/21 10:33
     */
    public function index()
    {
//        $filter = I('get.');
//        $search = array('shop_type' => Constants::SHOP_TYPE_BAO_DAN);
//        if (isset($filter['name'])) {
//            $search['name'] = trim($filter['name']);
//        }
//        if (isset($filter['category'])) {
//            $search['category'] = $filter['category'];
//            $this->assign('get_category', $search['category']);
//        }
//        if (isset($filter['status'])) {
//            $search['status'] = $filter['status'];
//            $this->assign('get_status', $search['status']);
//        }
        $search = [];
        $data = GoodsModel::show_goods_page($search);
        $this->assign('empty', $data['empty']);// 赋值数据集
        $this->assign('list', $data['list']);  // 赋值数据集
        $this->assign('page', $data['page']);  // 赋值分页输出
        $this->assign('breadcrumb2', '仓库区');
        $this->assign('category', GoodsModel::getGoodsCategoryByShopType());
        $this->display();
    }

    /**
     * 仓库区商品编辑
     * @author ldz
     * @time 2020/2/21 13:47
     */
    function edit()
    {
        if (IS_POST) {
            $model = new GoodsModel();
            $return = $model->edit_goods();
            $this->osc_alert($return);
        }

        $product_id = intval(I("id"));
        $crumbs = $product_id ? '编辑' : '新增';
        $goodsInfo = $product_id ? M("product_detail")->where(array("id" => $product_id))->find() : [];

        $this->assign("breadcrumb2", '仓库区');
        $this->assign("crumbs", $crumbs);
        $this->assign("id", $product_id);
        $this->assign("goods", $goodsInfo);
//        $this->assign("category", GoodsModel::getGoodsCategoryByShopType());
        $this->display();
    }

    /**
     * 再生商城商品列表
     * @throws \Think\Exception
     * @author ldz
     * @time 2020/2/21 10:33
     */
    public function regenerateShop()
    {
        $filter = I('get.');
        $search = array('shop_type' => Constants::SHOP_TYPE_REGENERATE);
        if (isset($filter['name'])) {
            $search['name'] = trim($filter['name']);
        }
        if (isset($filter['category'])) {
            $search['category'] = $filter['category'];
            $this->assign('get_category', $search['category']);
        }
        if (isset($filter['status'])) {
            $search['status'] = $filter['status'];
            $this->assign('get_status', $search['status']);
        }

        $data = GoodsModel::show_goods_page($search);
        $this->assign('empty', $data['empty']);// 赋值数据集
        $this->assign('list', $data['list']);  // 赋值数据集
        $this->assign('page', $data['page']);  // 赋值分页输出
        $this->assign('breadcrumb2', '再生商城');
        $this->assign('category', GoodsModel::getGoodsCategoryByShopType(Constants::SHOP_TYPE_REGENERATE));
        $this->display();
    }

    /**
     * 再生商城编辑
     * @author ldz
     * @time 2020/3/2 11:58
     */
    public function regenerateShopEdit()
    {
        if (IS_POST) {
            $model = new GoodsModel();
            $return = $model->edit_regenerate_shop_goods();
            $this->osc_alert($return);
        }

        $product_id = intval(I("id"));
        $crumbs = $product_id ? '编辑' : '新增';
        $goodsInfo = $product_id ? M("product_detail")->where(array("id" => $product_id))->find() : [];

        $this->assign("breadcrumb2", '再生商城');
        $this->assign("crumbs", $crumbs);
        $this->assign("id", $product_id);
        $this->assign("goods", $goodsInfo);
        $this->assign("category", GoodsModel::getGoodsCategoryByShopType(Constants::SHOP_TYPE_REGENERATE));
        $this->display();
    }

    /**
     * 自营商城商品列表
     * @throws \Think\Exception
     * @author ldz
     * @time 2020/2/21 10:33
     */
    public function selfSupportShop()
    {
        $filter = I('get.');
        $search = array('shop_type' => Constants::SHOP_TYPE_SELF_SUPPORT);
        if (isset($filter['name'])) {
            $search['name'] = trim($filter['name']);
        }
        if (isset($filter['category'])) {
            $search['category'] = $filter['category'];
            $this->assign('get_category', $search['category']);
        }
        if (isset($filter['status'])) {
            $search['status'] = $filter['status'];
            $this->assign('get_status', $search['status']);
        }

        $data = GoodsModel::show_goods_page($search);
        $this->assign('empty', $data['empty']);// 赋值数据集
        $this->assign('list', $data['list']);  // 赋值数据集
        $this->assign('page', $data['page']);  // 赋值分页输出
        $this->assign('breadcrumb2', '自营商城');
        $this->assign('category', GoodsModel::getGoodsCategoryByShopType(Constants::SHOP_TYPE_SELF_SUPPORT));
        $this->display();
    }

    /**
     * 自营商城编辑
     * @author ldz
     * @time 2020/3/2 11:58
     */
    public function selfSupportShopEdit()
    {
        if (IS_POST) {
            $model = new GoodsModel();
            $return = $model->edit_self_support_shop_goods();
            $this->osc_alert($return);
        }

        $product_id = intval(I("id"));
        $crumbs = $product_id ? '编辑' : '新增';
        $goodsInfo = M("product_detail")->where(array("id" => $product_id))->find();

        $this->assign("breadcrumb2", '自营商城');
        $this->assign("crumbs", $crumbs);
        $this->assign("id", $product_id);
        $this->assign("goods", $goodsInfo);
        $this->assign("category", GoodsModel::getGoodsCategoryByShopType(Constants::SHOP_TYPE_SELF_SUPPORT));
        $this->display();
    }

    /**
     * 爱心商城商品列表
     * @throws \Think\Exception
     * @author ldz
     * @time 2020/2/21 10:33
     */
    public function loveShop()
    {
        $filter = I('get.');
        $search = array('shop_type' => Constants::SHOP_TYPE_LOVE);
        if (isset($filter['name'])) {
            $search['name'] = trim($filter['name']);
        }
        if (isset($filter['category'])) {
            $search['category'] = $filter['category'];
            $this->assign('get_category', $search['category']);
        }
        if (isset($filter['status'])) {
            $search['status'] = $filter['status'];
            $this->assign('get_status', $search['status']);
        }

        $data = GoodsModel::show_goods_page($search);
        $this->assign('empty', $data['empty']);// 赋值数据集
        $this->assign('list', $data['list']);  // 赋值数据集
        $this->assign('page', $data['page']);  // 赋值分页输出
        $this->assign('breadcrumb2', '爱心商城');
        $this->assign('category', GoodsModel::getGoodsCategoryByShopType(Constants::SHOP_TYPE_LOVE));
        $this->display();
    }

    /**
     * 爱心商城编辑
     * @author ldz
     * @time 2020/3/2 11:58
     */
    public function loveShopEdit()
    {
        if (IS_POST) {
            $model = new GoodsModel();
            $return = $model->edit_love_shop_goods();
            $this->osc_alert($return);
        }

        $product_id = intval(I("id"));
        $crumbs = $product_id ? '编辑' : '新增';
        $goodsInfo = $product_id ? M("product_detail")->where(array("id" => $product_id))->find() : [];

        $this->assign("breadcrumb2", '爱心商城');
        $this->assign("crumbs", $crumbs);
        $this->assign("id", $product_id);
        $this->assign("goods", $goodsInfo);
        $this->assign("category", GoodsModel::getGoodsCategoryByShopType(Constants::SHOP_TYPE_LOVE));
        $this->display();
    }

    /**
     * 获取商家信息
     * @author ldz
     * @time 2020/3/5 11:37
     */
    public function getBusinessInfo()
    {
        if (IS_AJAX) {
            $business_phone = trim(I('business_phone'));
            if (empty($business_phone)) {
                $this->ajaxReturn(['status' => 0, 'message' => '请输入商家手机号']);
            }
            $businessInfo = M('verify_list')->where(['account' => $business_phone])->field('status,store_name')->find();

            if (!$businessInfo || $businessInfo['status'] != Constants::VERIFY_STATUS_PASS) {
                $this->ajaxReturn(['status' => 0, 'message' => '该用户还不是商家，请重新输入']);
            }

            $this->ajaxReturn(['status' => 1, 'message' => $businessInfo['store_name']]);
        } else {
            $this->ajaxReturn(['status' => 0, 'message' => '请求类型有误']);
        }
    }

    public function dianpu()
    {
        if (IS_POST) {
            $model = new GoodsModel();
            $data = I('post.');
            $return = $model->add_dianpu($data);
            $this->osc_alert($return);
        }
        $category = array();
        $cateList = M('product_cate')->select();

        //查出最后一级
        foreach ($cateList as $key => $value) {
            $count = M("product_cate")->where(array("pid" => $value["id"]))->count();
            if ($count == 0) {
                $category[] = $value;
            }
        }
        $proid = 1;
        $this->goods = M("gerenshangpu")->where(array("id" => $proid))->find();
        $this->id = $proid;
        $this->category = $category;
        $this->action = U('Goods/dianpu');
        $this->breadcrumb2 = '总后台店铺';
        $this->crumbs = '修改店铺';

        $this->display('dianpu');
    }

    /**
     * 分类列表
     * @throws \Think\Exception
     */
    public function cate()
    {
        //查询当前产品分类
        $cate_list = M('product_cate')->field('id,name,shop_type,status')->order("sort desc")->select();
        $shop_type = Constants::getShopTypeItems();
        foreach ($cate_list as $k => $item) {
            $array_shop_type = getArray($item['shop_type']);
            $shop_type_text = [];
            foreach ($shop_type as $i => $vo) {
                if (in_array($i, $array_shop_type)) {
                    $shop_type_text[] = $vo;
                }
            }
            if ($shop_type_text) {
                $cate_list[$k]['shop_type_text'] = join("，", $shop_type_text);
            } else {
                $cate_list[$k]['shop_type_text'] = '';
            }
            $cate_list[$k]['status_name'] = Constants::getYesNoItems($item['status']);
        }
        $this->assign("cate_list", $cate_list);
        $this->assign("shop_type", $shop_type);
        $this->assign("breadcrumb2", '分类管理');
        $this->display();
    }

    /**
     * 新增分类
     */
    public function cateAdd()
    {
        $name = trim(I("name"));
        $pid = I("pid");
        if (!$name) {
            $this->error("参数不能为空");
        }

        //当前分类下是否存在相同名字
        $count = M("product_cate")->where(array("name" => $name, "pid" => $pid))->count(1);
        if ($count > 0) {
            $this->error("已经存在该分类标题");
        }

        $shop_type = I('shop_type');
        if (empty($shop_type)) {
            $this->error("请选择所属商城");
        } else {
            $data['shop_type'] = join("-", $shop_type);
        }

        if (!$pid) $pid = 0;
        $data['name'] = $name;
        $data['pid'] = $pid;
        $data['status'] = intval(I("status"));
        $data['ctime'] = time();

        $isAdd = M("product_cate")->add($data);
        if ($isAdd) {
            $this->success("添加商品分类成功");
        } else {
            $this->error("商品分类添加失败");
        }
    }

    /**
     * 编辑分类页
     * @throws \Think\Exception
     */
    public function editCate()
    {
        $id = I("id");
        $cate = M('product_cate')->where(array("id" => $id))->find();
        $cate['shop_type'] = getArray($cate['shop_type']);
        $shop_type = Constants::getShopTypeItems();
        $this->assign("cate", $cate);
        $this->assign("breadcrumb2", '分类管理');
        $this->assign("shop_type", $shop_type);
        $this->display("edcate");
    }

    /**
     * 编辑分类
     */
    public function cateUpdate()
    {
        $shop_type = I('shop_type');
        if (empty($shop_type)) {
            $this->error("请选择所属商城");
        } else {
            $data['shop_type'] = join("-", $shop_type);
        }
        $data['id'] = intval(I("id"));
        $data['name'] = trim(I("name"));
        $data['status'] = intval(I("status"));
        $isSave = M("product_cate")->save($data);
        if ($isSave) {
            $this->success("修改成功", U("Goods/cate"));
        } else {
            $this->error("修改失败");
        }
    }

    /**
     * 删除分类
     */
    public function delCate()
    {
        $id = I("id");
        //该ID是否存在下级分类
        $count = M("product_cate")->where(array("pid" => $id))->count();
        if ($count > 0) {
            $this->error("存在子分类，无法删除。");
        } else {
            M("product_cate")->where(array("id" => $id))->delete();
            $this->success("删除成功");
        }
    }

    function copy_goods()
    {
        $id = I('id');
        $model = new GoodsModel();
        if ($id) {
            foreach ($id as $k => $v) {
                $model->copy_goods($v);
            }
            $data['redirect'] = U('Goods/index');
            $this->ajaxReturn($data);
            die;
        }
    }

    /**
     * 删除商品
     */
    function del()
    {
        $model = new GoodsModel();
        $return = $model->del_goods();
        $this->osc_alert($return);
    }

    /**
     * 个人店铺
     */
    public function ggshop()
    {
        $id['id'] = array('neq', 1);
        $ggshop = M('gerenshangpu')->where($id)->select();
        $this->breadcrumb1 = '商家入驻';
        $this->breadcrumb2 = '个人店铺';
        $this->assign('ggshop', $ggshop);
        $this->display();
    }

    /**
     * 入驻认证列表
     * @throws Exception
     */
    public function verify()
    {
        $verify_list = M('verify_list')->order("status asc")->select();
        foreach ($verify_list as $k => $item) {
            $verify_list[$k]['type_name'] = Constants::getVerifyTypeItems($item['type']);
        }
        $this->breadcrumb1 = '商家入驻';
        $this->breadcrumb2 = '认证列表';
        $this->assign('data', $verify_list);
        $this->display();
    }

    public function verifyInfo()
    {
        if (IS_POST) {
            $model = new VerifyListModel();
            $res = $model->editInfo();
            if (!$res) {
                $this->error($model->getError());
                die();
            }
            $this->success('修改成功', U('Goods/verify'));
            die();
        }
        $id = intval(I('id'));
        $info = M('verify_list')->where(['id' => $id])->find();
        $info['type_name'] = Constants::getVerifyTypeItems($info['type']);
        $this->assign('info', $info);
        $this->breadcrumb1 = '商家入驻';
        $this->breadcrumb2 = '认证列表';
        $this->crumbs = '认证信息';
        $this->display();
    }

    /**
     * 审核认证
     */
    public function saveVerify()
    {
        $id = intval(I("id"));
        $status = intval(I("status"));
        if (empty($id) || !isset($id) || empty($status) || !isset($status)) {
            $this->error("参数错误");
        }

        $arrStatus = [Constants::VERIFY_STATUS_PASS, Constants::VERIFY_STATUS_NOT_PASS];
        if (!in_array($status, $arrStatus)) {
            $this->error("操作错误，请重试");
        }
        M()->startTrans();
        try {
            $verifyInfo = M("verify_list")->where(['id' => $id])->find();
            if (empty($verifyInfo)) {
                throw new Exception('您选择审核内容不存在，请重新选择');
            }
            if ($verifyInfo['status'] != Constants::VERIFY_STATUS_WAIT) {
                throw new Exception('该用户已经审核了，请刷新页面重试');
            }
            $type = $verifyInfo['type'];
            $typeField = ($type == Constants::VERIFY_BUSINESS) ? "is_e_verify" : "is_p_verify";
            $statusField = $status == Constants::VERIFY_STATUS_PASS ? Constants::YesNo_Yes : Constants::YesNo_No;
            $res = M("verify_list")->where(["id" => $id])->setField("status", $status);
            if (!$res) {
                throw new Exception('操作失败，请重试');
            }

            $res = M("user")->where(array("userid" => $verifyInfo['uid']))->setField($typeField, $statusField);
            if ($res === false) {
                throw new Exception('操作失败，请重试');
            }
            M()->commit();
            $this->success("操作成功");
        } catch (Exception $ex) {
            M()->rollback();
            $this->error($ex->getMessage());
        }
    }

    /**
     * 认证协议
     */
    public function verifyAgreement()
    {
        if (IS_POST) {
            $data['value'] = I('content');
            $res = M('Config')->where(['name' => 'verify_agreement'])->save($data);
            if ($res === false) {
                $this->error("修改失败");
            }
            $this->success("修改成功");
        } else {
            $this->content = M('Config')->where(['name' => 'verify_agreement'])->getField('value');
            $this->breadcrumb1 = '商家入驻';
            $this->breadcrumb2 = '认证协议';
            $this->display();
        }
    }

    public function level()
    {
        $data = M('level_list')->order("status asc")->select();
        foreach ($data as $key => $value) {
            $data[$key]['account'] = M("member")->where(array("member_id" => $value['uid']))->getField("phone");
            $data[$key]['username'] = M("member")->where(array("member_id" => $value['uid']))->getField("uname");
        }

        $this->data = $data;
        $this->breadcrumb1 = '会员等级';
        $this->breadcrumb2 = '升级列表';
        $this->display();
    }

    public function levelup()
    {
        $id = I("id");
        $status = I("status");

        if (empty($id) || !isset($id)) {
            $this->error("参数错误");
        }

        if (empty($status) || !isset($status)) {
            $this->error("参数错误");
        }

        $verifyInfo = M("level_list")->where(array("status" => 0, "id" => $id))->find();
        $isVerify = M("level_list")->where(array("status" => 0, "id" => $id))->setField("status", $status);

        if ($status == 1) {
            $isVerify = M("member")->where(array("member_id" => $verifyInfo['uid']))->setField("member_grade", $verifyInfo['level']);
        }

        if ($isVerify) {
            $this->success("操作成功");
        } else {
            $this->error("操作失败");
        }
    }

    public function wen()
    {
        $this->breadcrumb1 = '商家入驻';
        $this->breadcrumb2 = '个人店铺';

        $id = I('id');
        $configsAll = M('gerenshangpu');
        $msgll = $configsAll->where(array('id' => $id))->find();
        $this->assign('id', $id);
        $this->assign('msgll', $msgll);
        $this->display();
    }

    public function Savewen()
    {
        $id = I('id');
        $configsAll = M('gerenshangpu');
        $data['shop_stort'] = I('shop_stort');
        if (empty($id)) {
            echo '<script>alert("没有找到店铺"); window.history.back(-1); </script>';
            return;
        } else {
            $configsAll->where(array('id' => $id))->save($data);
            $this->success("修改成功", U("Goods/ggshop"));
        }
    }

    public function zhuangtai()
    {
        $id = I('id');
        $configsAll = M('gerenshangpu');
        $data['shop_zhuangtai'] = I('shop_zhuangtai');
        if (empty($id)) {
            echo '<script>alert("没有找到店铺"); window.history.back(-1); </script>';
            return;
        } else {
            $configsAll->where(array('id' => $id))->save($data);
            $this->success("修改成功", U("Goods/ggshop"));
        }

    }

    public function dltgeren()
    {
        $id = I('id');
        $configsAll = M('gerenshangpu');

        if (empty($id)) {
            echo '<script>alert("没有找到店铺"); window.history.back(-1); </script>';
            return;
        } else {
            $configsAll->where(array('id' => $id))->delete();
            $this->success("删除成功", U("Goods/ggshop"));
        }
    }

    /**
     * 淘宝天猫商城
     */
    public function taoBao()
    {
        $where = [];
        $table = M('tao_bao_product')->where($where);
        $count = $table->count();
        $Page = new \Think\Page($count, C('BACK_PAGE_NUM'));
        $show = $Page->show();// 分页显示输出
        $list = $table->limit($Page->firstRow, $Page->listRows)->order('id desc')->select();

        $this->assign('list', $list);
        $this->assign('empty', '<tr><td colspan="20" style="text-align: center">~~暂无商品</td></tr>');
        $this->assign('page', $show);  // 赋值分页输出
        $this->assign('breadcrumb2', '淘宝天猫商城');
        $this->display();
    }

    /**
     * 编辑淘宝天猫商城
     */
    public function taoBaoEdit()
    {
        if (IS_POST) {
            $model = new GoodsModel();
            $return = $model->edit_tao_bao_goods();
            $this->osc_alert($return);
        }

        $product_id = intval(I("id"));
        $crumbs = $product_id ? '编辑' : '新增';
        $goodsInfo = $product_id ? M('tao_bao_product')->where(array("id" => $product_id))->find() : [];

        $this->assign("breadcrumb2", '淘宝天猫商城');
        $this->assign("crumbs", $crumbs);
        $this->assign("id", $product_id);
        $this->assign("goods", $goodsInfo);
        $this->display();
    }

    /**
     * 删除商品
     */
    function taoBaoDel()
    {
        $id = intval(I('id'));
        $res = M("tao_bao_product")->where(array("id" => $id))->delete();
        if ($res === false) {
            $return = ['status' => 'fail', 'message' => '删除失败,未知异常', 'jump' => U('Goods/taoBao')];
        } else {
            $return = ['status' => 'success', 'message' => '删除成功', 'jump' => U('Goods/taoBao')];
        }

        $this->osc_alert($return);
    }

}