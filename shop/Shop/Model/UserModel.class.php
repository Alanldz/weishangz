<?php
/**
 * Created by PhpStorm.
 * User: ldz
 * Date: 2019/8/15
 * Time: 16:53
 */

namespace Shop\Model;

use Common\Util\Constants;
use Think\Exception;

class UserModel extends \Common\Model\UserModel
{

    const total_release_a_one = 150000;//15万
    const a_two_share_one = 3;//直推人3个

    const total_release_a_two = 1000000;//100万
    const a_two_share_num = 6;//直推人6个

    const total_release_a_three = 5000000;//500万
    const other_achievements_a_three = 1000000;//100万

    const total_release_a_four = 20000000;//2000万
    const other_achievements_a_four = 5000000;//500万

    const total_release_a_five = 60000000;//6000万
    const other_achievements_a_five = 20000000;//2000万

    const total_release_a_six = 180000000;//1.8个亿
    const other_achievements_a_six = 80000000;//8000万

    /**
     * 其他业绩（用户链条下，最大的业绩去掉，剩下的之和就是其他业绩）
     * @param int $uid
     * @return int
     * @author ldz
     * @time 2020/2/27 13:54
     */
    public static function otherAchievements($uid)
    {
        $achievement_id = M('user')->where(['pid' => $uid])->getField('userid', true);
        $store = M('store')->where(['uid' => ['in', $achievement_id]])->getField('total_release', true);
        rsort($store);
        array_shift($store);
        $total_release = 0;
        foreach ($store as $value) {
            $total_release += $value;
        }
        return $total_release;
    }

    /**
     * 会员升级
     * @param $uid
     * @return int
     * @throws Exception
     * @author ldz
     * @time 2020/3/9 13:38
     */
    public function updateUserLevel($uid)
    {
        $userInfo = M('user')->where(['userid' => $uid])->field('level,activation_time')->find();
        $total_release = M('store')->where(['uid' => $uid])->getField('total_release');//总业绩
        $other_achievements = $this->otherAchievements($uid);//其他业绩
        $share_number = M('user')->where(['pid' => $uid])->count(); //直推人数

        if ($total_release >= self::total_release_a_six && $other_achievements >= self::other_achievements_a_six) {
            $level = Constants::USER_LEVEL_A_SIX;
        } elseif ($total_release >= self::total_release_a_five && $other_achievements >= self::other_achievements_a_five) {
            $level = Constants::USER_LEVEL_A_FIVE;
        } elseif ($total_release >= self::total_release_a_four && $other_achievements >= self::other_achievements_a_four) {
            $level = Constants::USER_LEVEL_A_FOUR;
        } elseif ($total_release >= self::total_release_a_three && $other_achievements >= self::other_achievements_a_three) {
            $level = Constants::USER_LEVEL_A_THREE;
        } elseif ($total_release >= self::total_release_a_two && $share_number >= self::a_two_share_num) {
            $level = Constants::USER_LEVEL_A_TWO;
        } elseif ($total_release >= self::total_release_a_one && $share_number >= self::a_two_share_one) {
            $level = Constants::USER_LEVEL_A_ONE;
        } else {
            $level = $userInfo['level'];
        }

        if ($level > $userInfo['level']) {
            if (empty($userInfo['activation_time'])) {
                $data['activation_time'] = time();
            }
            //激活用户
            $data['level'] = $level;
            $res = M('user')->where(['userid' => $uid])->save($data);
            if (!$res) {
                throw new Exception('修改用户等级失败');
            }
        } else {
            $level = $userInfo['level'];
        }
        return $level;
    }


}