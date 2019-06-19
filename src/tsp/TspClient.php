<?php
/**
 * TSP-Client-SDK
 * @author macnie<mac@lenmy.com>
 * @since 2019.6.19
 * @version 1.0.0
 * @example
 * try{
 *      $client = new \Macnie\Tsp\TspClient( ['gateway'=>'','token'=>''] );
 *      $client->setHost($imei_sn,$host,$port);
 * }catch(Excetion $e){
 *      ......
 * }
 */

namespace Macnie\Tsp;

use Curl\Curl;

class TspClient
{
    private $config = null;


    public function __construct($params = [])
    {
        $this->config = $params;
        if(!isset($this->config['gateway']) || empty($this->config['gateway']) || !isset($this->config['token']) || empty($this->config['token'])){
            throw new \Exception('Configure File Miss Gateway Or Token Param!');
        }
    }

    /**
     * 请求设备当前位置
     * @param string $imei_sn
     */
    public function setLocate($imei_sn){
        return $this->post('setLocate',['imei_sn'=>$imei_sn]);
    }

    /**
     * 下发监听命令
     * @param string $imei_sn
     * @param string $mobile 此手机号必须已设置过亲情号码
     * @return mixed
     * @throws \Exception
     */
    public function setMonitor($imei_sn,$mobile = ''){
        return $this->post('setMonitor',['imei_sn'=>$imei_sn,'mobile'=>$mobile]);
    }

    /**
     * @param string $imei_sn
     * @param array $families
     *                  param $relation 关系名称
     *                  param $mobile 手机号
     * @return mixed
     * @throws \Exception
     */
    public function setFamily($imei_sn,$families = []){
        if(empty($families)){
            throw new \Exception('亲情号不能为空');
        }
        return $this->post('setFamily',['imei_sn'=>$imei_sn,'families'=>$families]);
    }

    /**
     * 设置上报时间间隔
     * @param string $imei_sn
     * @param int $second
     */
    public function setUpload($imei_sn,$second = 60){
        return $this->post('setUpload',['imei_sn'=>$imei_sn,'second'=>$second]);
    }

    /**
     * 设置设备的主机和端口
     * @param string $imei_sn 设备编号
     * @param string $host 主机IP
     * @param integer $port 端口
     */
    public function setHost($imei_sn,$host,$port = 2232){
        return $this->post('setUpload',['imei_sn'=>$imei_sn,'host'=>$host,'port'=>$port]);
    }

    /**
     * 让设备关机
     * @param string $imei_sn
     */
    public function setPowerOff($imei_sn){
        return $this->post('setPowerOff',['imei_sn'=>$imei_sn]);
    }

    /**
     * 让设备重启
     * @param string $imei_sn
     */
    public function setRestart($imei_sn){
        return $this->post('setRestart',['imei_sn'=>$imei_sn]);
    }

    /**
     * 寻找设备
     * @param string $imei_sn
     */
    public function setFind($imei_sn){
        return $this->post('setFind',['imei_sn'=>$imei_sn]);
    }

    /**
     * 判断设备是否在线
     * @param string $imei_sn
     */
    public function isOnline($imei_sn){
        $res = $this->get('isOnline',['imei_sn'=>$imei_sn]);
        return $res['online'];
    }

    /**
     * 获取IMEI当前所有信息
     * @param string $imei_sn
     */
    public function getImei($imei_sn){
        return $this->get('getImei',['imei_sn'=>$imei_sn]);
    }
    /**
     * 系统设置
     * @param string $imei_sn
     * @param array $params
     *          param $mobile 手机号
     *          param $class_dispar  上课禁用时段 如：08:00-11:30|14:00-16:30|12345，表示：上午八点到十一点半，下午两点到四点半。从周一到周五
     *          param $sleep_time   休眠时间
     *          param $awake_time   唤醒时间
     */
    public function setOptions($imei_sn,$params = []){
        return $this->post('setOptions',['imei_sn'=>$imei_sn,'params'=>$params]);
    }

    /**
     * 透传报文
     * @param string $imei_sn 设备编号
     * @param string $message 报文内容
     */
    public function sendMessage($imei_sn,$message){
        return $this->post('sendMessage',['imei_sn'=>$imei_sn,'message'=>$message]);
    }

    /**
     * 封装的GET方法
     * @param $action
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    private function get($action,$params){
        return $this->request('get',$action,$params);
    }
    /**
     * 封装的POST方法
     * @param $action
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    private function post($action,$params){
        return $this->request('post',$action,$params);
    }

    /**
     * 请求中间件
     * @param string $action
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    private function request($type = 'post',$action = 'test',$params = [])
    {
        $http = new Curl();
        $http->setHeader('ContentType', 'application/json');
        $http->setHeader('source', 'TspClientSdk');
        $http->setHeader('token',$this->config['token']);
        $res = $http->$type($this->config['gateway'], ['action'=>$action,'data'=>$params]);
        if ($http->error) {
            throw new \Exception($http->errorMessage,$http->errorCode);
        } else {
            $result = json_decode($res,true);
            return $result;
        }
    }

}