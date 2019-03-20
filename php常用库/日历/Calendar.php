<?php
/**
 * @package: 日历常用函数
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 8:36
 */

namespace Calendar;

class Calendar extends BaseModel
{

    /**
     * @todo: 1. 获取本周周一 | 周日
     * @author： friker
     * @date: 2019/3/19
     * @param string $day 指定日期
     * @param string $date_format 返回日期格式
     * @param bool $first 获取周一 还是 周日
     * @return false|string
     */
    public function getWeekDay($day = '', $date_format = 'Ymd', $first = true)
    {
        $day    = empty($day) ? date($date_format) : $day;
        $now    = strtotime($day);
        $number = date("w", $now);  //当时是周几
        $number = $number == 0 ? 7 : $number; //如遇周末,将0换成7
        if ($first) {
            return date($date_format, $now - (($number - 1) * 60 * 60 * 24));
        }
        return date($date_format, $now + ((7 - $number) * 60 * 60 * 24));
    }

    /**
     * @todo: 2. 获取本月月初 | 月末
     * @author： friker
     * @date: 2019/3/19
     * @param string $day 指定日期
     * @param string $date_format 日期格式
     * @param bool $first 是否是第一天
     * @return false|string
     */
    public function getMonthDay($day = '', $date_format = 'Ymd', $first = true)
    {
        $day       = empty($day) ? date($date_format) : $day;
        $now       = strtotime($day);
        $first_day = date($date_format, strtotime(date('Ym01', $now)));
        if ($first) {
            return $first_day;
        }
        return date($date_format, strtotime("$first_day +1 month -1 day"));
    }


    /**
     * @todo: 3. 获取本周第一天 至 最后一天 数组
     * @author： friker
     * @date: 2019/3/19
     * @param string $day 指定日期
     * @param string $date_format 指定日期格式
     * @return array
     */
    public function getWeekArr($day = '', $date_format = 'Ymd')
    {
        $start_day   = $this->getWeekDay($day, $date_format, true);
        $return_data = array();
        for ($i = 0; $i < 7; $i++) {
            $return_data[$i] = date($date_format, strtotime("$start_day +$i day"));
        }
        return $return_data;
    }


    /**
     * @todo: 4. 获取本月第一天 至 最后一天
     * @author： friker
     * @date: 2019/3/19
     * @param string $day 指定日期
     * @param string $date_format 指定日期格式
     * @return array
     */
    public function getMonthArr($day = '', $date_format = 'Ymd')
    {
        $start_day   = $this->getMonthDay($day, $date_format, true);
        $end_day     = $this->getMonthDay($day, $date_format, false);
        $return_data = array();
        $i           = 0;
        for ($key = $start_day; $key <= $end_day; $key = date($date_format, strtotime("$key +1 day"))) {
            $return_data[$i] = $key;
            $i++;
        }
        return $return_data;
    }

    /**
     * @todo: 5. 获取指定日期前 n 天
     * @author： friker
     * @date: 2019/3/19
     * @param string $day 指定日期
     * @param int $n 前n天
     * @param string $date_format 指定日期格式
     * @return array
     */
    public function getPastDaysArr($day = '', $n = 7, $date_format = 'Ymd')
    {
        $start_day   = empty($day) ? date($date_format) : $day;
        $return_data = array();
        for ($i = $n; $i > 0; $i--) {
            $return_data[] = date($date_format, strtotime("$start_day -{$i} day"));
        }
        return $return_data;
    }

    /**
     * @todo: 5. 获取指定日期前 n 个月
     * @author： friker
     * @date: 2019/3/19
     * @param string $day 指定日期
     * @param int $n 前n个月
     * @param string $date_format 指定日期格式
     * @return array
     */
    public function getPastMonthsArr($day = '', $n = 6, $date_format = 'Ym')
    {
        $start_day   = empty($day) ? date($date_format) : $day;
        $return_data = array();
        for ($i = $n; $i > 0; $i--) {
            $return_data[] = date($date_format, strtotime("$start_day -{$i} month"));
        }
        return $return_data;
    }

}
