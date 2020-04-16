<?php
/**
 * Created by PhpStorm.
 * Date: 2019-2-28
 * Time: 21:22:02
 */

namespace Common\Model;

class UserModel extends ModelModel
{
    /**
     * 数据库表名
     */
    protected $tableName = 'user';

    const two_thousand_exchange = 1.5;
    const five_thousand_exchange = 1.8;
    const ten_thousand_exchange = 2;

    const two_thousand_multiple_ratio = 1.2;//2000的兑换额
    const five_thousand_multiple_ratio = 1.4;//5000的兑换额
    const multiple_ratio = 1.6; //10000的兑换额

    const repeat_purchase_des_ratio = 0.1; //复购递减
    const two_five_min_multiple_ratio = 1;//2000,5000最小兑换额
    const min_multiple_ratio = 1.2; //10000最小兑换额

    /**
     * 盐，用于加密
     * @return bool|string
     */
    public function getSalt()
    {
        $salt = substr(md5(time()), 0, 3);
        return $salt;
    }

    /**
     * [pwdMd5 用户密码加密]
     * @param $value
     * @param $salt
     * @return string
     */
    public function pwdMd5($value, $salt)
    {
        $user_pwd = md5(md5($value) . $salt);
        return $user_pwd;
    }

    /**
     * 获取用户团队人员、个数、业绩
     * @param int $userid
     * @param string $zone 空：查询全部 1 ：A区 2： B区
     * @return mixed
     * @time 2019-2-28 21:43:21
     */
    public function getUserTeam($userid, $zone = '')
    {
        $is_child = true;
        $where = [];
        $model = M('user');

        $investment_grade = UserModel::$INVESTMENT_GRADE;
        if (!empty($zone)) {
            $childsUser = $model->where(['junction_id' => $userid, 'zone' => $zone])->field('userid,level')->find();
            if (empty($childsUser)) {
                $is_child = false;
            } else {
                $junction_path = '-' . $userid . '-' . $childsUser['userid'] . '-';
                $where['junction_path'] = array('like', '%' . $junction_path . '%');
            }
        } else {
            $where['junction_path'] = array('like', '%-' . $userid . '-%');
        }

        if ($is_child) {
            $results = 0;   //业绩
            $user = $model->where($where)->field('userid,level')->select();
            if (!empty($childsUser)) {
                $user[] = $childsUser;
            }
            foreach ($user as $item) {
                if ($item['level'] > 0) {
                    $results += $investment_grade[$item['level']];
                }
            }
            $data = ['user' => $user, 'num' => count($user), 'results' => ($results / 100)];
        } else {
            $data = ['user' => 0, 'num' => 0, 'results' => 0];
        }

        return $data;
    }

    /**
     * 获取默认银行卡或者选择的银行卡
     * @param $uid
     * @param string $cid
     * @return mixed
     */
    public static function getUserBankInfo($uid, $cid = '')
    {
        $table = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid')
            ->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre')
            ->limit(1);

        if (empty($cid)) {
            $where['user_id&is_default'] = array($uid, 1, '_multi' => true);
            $where['delete_time'] = 0;
            $carInfo = M('ubanks')->where($where)->count(1);
            if ($carInfo < 1) {
                $info = $table->where(array('u.user_id' => $uid, 'u.delete_time' => 0))->find();
            } else {
                $info = $table->where(array('u.user_id' => $uid, 'is_default' => 1, 'u.delete_time' => 0))->find();
            }
        } else {
            $info = $table->where(array('u.id' => $cid, 'u.delete_time' => 0))->find();
        }
        return $info;
    }

    /**
     * 图谱
     * @return mixed
     */
    public function map()
    {
        $model = M('user');
        $parent_id = intval(I('user_id'));
        $parent = $model->where(['userid' => $parent_id])->field('userid,username,level,username')->find();
        if (!$parent) {
            ajaxReturn('该用户不存在', 0);
        }
        $data = $parent;
        $leftUser = $this->getUserTeam($parent_id, 1); // A区团队
        $rightUser = $this->getUserTeam($parent_id, 2); //B区团队
        $data['left_num'] = $leftUser['num'];//A区团队人员个数
        $data['right_num'] = $rightUser['num'];//B区团队人员个数
        $data['left_results'] = $leftUser['results'];//A区团队业绩
        $data['right_results'] = $rightUser['results'];//B区团队业绩

        $childs = $model->where(['junction_id' => $parent_id])->field('userid,username,junction_id,level,zone,username')->order('zone asc')->select();
        if (!empty($childs)) {
            if (count($childs) == 2) {
                $data['is_childs'] = 'all';
                $data['childs'] = $childs;
                $num = 0;
                foreach ($data['childs'] as $key => $item) {
                    $leftUser = $this->getUserTeam($item['userid'], 1); // A区团队
                    $rightUser = $this->getUserTeam($item['userid'], 2); //B区团队
                    $data['childs'][$key]['left_num'] = $leftUser['num'];//A区团队人员个数
                    $data['childs'][$key]['right_num'] = $rightUser['num'];//B区团队人员个数
                    $data['childs'][$key]['left_results'] = $leftUser['results'];//A区团队业绩
                    $data['childs'][$key]['right_results'] = $rightUser['results'];//B区团队业绩
                    $son = $model->where(['junction_id' => $item['userid']])->field('userid,username,junction_id,level,zone,username')->order('zone asc')->select();
                    if (!empty($son)) {
                        if (count($son) == 2) {
                            foreach ($son as $v) {
                                $son_info = $v;
                                $leftUser = $this->getUserTeam($v['userid'], 1); // A区团队
                                $rightUser = $this->getUserTeam($v['userid'], 2); //B区团队
                                $son_info['left_num'] = $leftUser['num'];//A区团队人员个数
                                $son_info['right_num'] = $rightUser['num'];//B区团队人员个数
                                $son_info['left_results'] = $leftUser['results'];//A区团队业绩
                                $son_info['right_results'] = $rightUser['results'];//B区团队业绩
                                $data['son'][] = $son_info;
                            }
                        } else if ($son[0]['zone'] == 1) {
                            $son_info = $son[0];
                            $leftUser = $this->getUserTeam($son[0]['userid'], 1); // A区团队
                            $rightUser = $this->getUserTeam($son[0]['userid'], 2); // B区团队
                            $son_info['left_num'] = $leftUser['num'];//A区团队人员个数
                            $son_info['right_num'] = $rightUser['num'];//B区团队人员个数
                            $son_info['left_results'] = $leftUser['results'];//A区团队业绩
                            $son_info['right_results'] = $rightUser['results'];//B区团队业绩
                            $data['son'][] = $son_info;
                            $data['son'][] = $this->mapDefaultArray($item['userid'], 2, $item['level']);
                        } else {
                            $data['son'][] = $this->mapDefaultArray($item['userid'], 1, $item['level']);
                            $son_info = $son[0];
                            $leftUser = $this->getUserTeam($son[0]['userid'], 1); //A区团队
                            $rightUser = $this->getUserTeam($son[0]['userid'], 2); //B区团队
                            $son_info['left_num'] = $leftUser['num'];//A区团队人员个数
                            $son_info['right_num'] = $rightUser['num'];//B区团队人员个数
                            $son_info['left_results'] = $leftUser['results'];//A区团队业绩
                            $son_info['right_results'] = $rightUser['results'];//B区团队业绩
                            $data['son'][] = $son_info;
                        }
                        $num += 2;
                    } else {
                        $data['son'][$num] = $this->mapDefaultArray($item['userid'], 1, $item['level']);
                        $num++;
                        $data['son'][$num] = $this->mapDefaultArray($item['userid'], 2, $item['level']);
                        $num++;

                    }
                }
            } else {
                if ($childs[0]['zone'] == 1) {
                    $data['is_childs'] = 'left';
                    $data['childs'][0] = $childs[0];
                    $leftUser = $this->getUserTeam($childs[0]['userid'], 1); // A区团队
                    $rightUser = $this->getUserTeam($childs[0]['userid'], 2); // B区团队
                    $data['childs'][0]['left_num'] = $leftUser['num'];//A区团队人员个数
                    $data['childs'][0]['right_num'] = $rightUser['num'];//B区团队人员个数
                    $data['childs'][0]['left_results'] = $leftUser['results'];//A区团队业绩
                    $data['childs'][0]['right_results'] = $rightUser['results'];//B区团队业绩
                    $data['childs'][1] = $this->mapDefaultArray($parent_id, 2, $parent['level']);
                } else {
                    $data['is_childs'] = 'right';
                    $data['childs'][0] = $this->mapDefaultArray($parent_id, 1, $parent['level']);
                    $data['childs'][1] = $childs[0];
                    $leftUser = $this->getUserTeam($childs[0]['userid'], 1); //A区团队
                    $rightUser = $this->getUserTeam($childs[0]['userid'], 2); //B区团队
                    $data['childs'][1]['left_num'] = $leftUser['num'];//A区团队人员个数
                    $data['childs'][1]['right_num'] = $rightUser['num'];//B区团队人员个数
                    $data['childs'][1]['left_results'] = $leftUser['results'];//A区团队业绩
                    $data['childs'][1]['right_results'] = $rightUser['results'];//B区团队业绩
                }
                $son = $model->where(['junction_id' => $childs[0]['userid']])->field('userid,username,junction_id,level,zone,username')->order('zone asc')->select();
                if (!empty($son)) {
                    if (count($son) == 2) {
                        foreach ($son as $v) {
                            $son_info = $v;
                            $leftUser = $this->getUserTeam($v['userid'], 1); // A区团队
                            $rightUser = $this->getUserTeam($v['userid'], 2); //B区团队
                            $son_info['left_num'] = $leftUser['num'];//A区团队人员个数
                            $son_info['right_num'] = $rightUser['num'];//B区团队人员个数
                            $son_info['left_results'] = $leftUser['results'];//A区团队业绩
                            $son_info['right_results'] = $rightUser['results'];//B区团队业绩
                            $data['son'][] = $son_info;
                        }
                    } else if ($son[0]['zone'] == 1) {
                        $son_info = $son[0];
                        $leftUser = $this->getUserTeam($son[0]['userid'], 1); // A区团队
                        $rightUser = $this->getUserTeam($son[0]['userid'], 2); // B区团队
                        $son_info['left_num'] = $leftUser['num'];//A区团队人员个数
                        $son_info['right_num'] = $rightUser['num'];//B区团队人员个数
                        $son_info['left_results'] = $leftUser['results'];//A区团队业绩
                        $son_info['right_results'] = $rightUser['results'];//B区团队业绩
                        $data['son'][] = $son_info;
                        $data['son'][] = $this->mapDefaultArray($childs[0]['userid'], 2, $childs[0]['level']);
                    } else {
                        $data['son'][] = $this->mapDefaultArray($childs[0]['userid'], 1, $childs[0]['level']);
                        $son_info = $son[0];
                        $leftUser = $this->getUserTeam($son[0]['userid'], 1); //A区团队
                        $rightUser = $this->getUserTeam($son[0]['userid'], 2); //B区团队
                        $son_info['left_num'] = $leftUser['num'];//A区团队人员个数
                        $son_info['right_num'] = $rightUser['num'];//B区团队人员个数
                        $son_info['left_results'] = $leftUser['results'];//A区团队业绩
                        $son_info['right_results'] = $rightUser['results'];//B区团队业绩
                        $data['son'][] = $son_info;
                    }
                } else {
                    $data['son'][] = $this->mapDefaultArray($childs[0]['userid'], 1, $childs[0]['level']);
                    $data['son'][] = $this->mapDefaultArray($childs[0]['userid'], 2, $childs[0]['level']);
                }

            }
        } else {
            $data['childs'] = [
                '0' => $this->mapDefaultArray($parent_id, 1, $parent['level']),
                '1' => $this->mapDefaultArray($parent_id, 2, $parent['level'])
            ];

            $data['is_childs'] = 'not';
        }

        return $data;
    }

    /**
     * 获取用户最左区的用户ID
     * @param $userid
     * @return mixed
     */
    public function getLastLeftUser($userid)
    {
        $lastLeftUserID = self::where(['junction_id' => $userid, 'zone' => 1])->getField('userid');
        if ($lastLeftUserID) {
            return $this->getLastLeftUser($lastLeftUserID);
        }
        return $userid;
    }

    /**
     * 默认返回数据
     */
    public static function mapDefaultArray($junction_id, $zone, $junction_level)
    {
        return [
            'userid' => 0,
            'junction_id' => $junction_id,
            'zone' => $zone,
            'junction_level' => $junction_level,
            'left_num' => 0,
            'right_num' => 0,
            'left_results' => 0,
            'right_results' => 0
        ];
    }

    /**
     * 不可用QBB总数
     * @return mixed
     * @author ldz
     * @time 2020/1/15 15:34
     */
    public static function getAllUserCanNotUseQbb()
    {
        return M('store')->sum('can_not_use_qbb');
    }


}