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

use Aliyun\OTS\Consts\DirectionConst;
use Aliyun\OTS\OTSClient;
use Curl\Curl;
use think\facade\Config;

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
     * 判断设备是否在线
     * @param string $imei_sn
     */
    public function isOnline($imei_sn){
        $res = $this->get('isOnline',['imei_sn'=>$imei_sn]);
        return $res['data']['is_online'];
    }

    /**
     * 获取当前合作方的在线设备数量
     * @return mixed
     * @throws \Exception
     */
    public function getOnlineCount(){
        $res = $this->get('getOnlineCount');
        return $res['data']['online_count'];
    }
    /**
     * 获取IMEI当前所有信息
     * @param string $imei_sn
     */
    public function getImei($imei_sn){
        return $this->get('getImei',['imei_sn'=>$imei_sn]);
    }

    /**
     * 获取指定时间内的轨迹
     * @param string $imei_sn
     * @param int $start 开始时间戳
     * @param int $end 结束时间戳
     * @return array
     */
    public function getTracks($imei_sn,$start_time,$end_time){

        $client = new OTSClient([
            'EndPoint' => $this->config['tablestore']['gateway'],
            'AccessKeyID' => $this->config['tablestore']['appkey'],
            'AccessKeySecret' => $this->config['tablestore']['secret'],
            'InstanceName' => $this->config['tablestore']['database'],
            'DebugLogHandler'=>false
        ]);
        $startPK = [
            ['imei', md5($imei_sn)],
            ['created', $start_time]
        ];
        $endPK = [
            ['imei', md5($imei_sn)],
            ['created',$end_time]
        ];
        $tracks = [];
        while (! empty ($startPK)) {
            $request = [
                'table_name' => 'tracks',
                'max_versions' => 1,
                'direction' => DirectionConst::CONST_FORWARD, // 方向可以为 FORWARD 或者 BACKWARD
                'inclusive_start_primary_key' => $startPK, // 开始主键
                'exclusive_end_primary_key' => $endPK,
                'limit'=>20
            ];
            $response = $client->getRange ($request);
            foreach ($response['rows'] as $rowData) {
                // 处理每一行数据
                $tracks[] = ['direction'=>$rowData['attribute_columns'][3][1],'speed'=>$rowData['attribute_columns'][9][1],'blng'=>$rowData['attribute_columns'][2][1],'blat'=>$rowData['attribute_columns'][1][1],'locate_type'=>$rowData['attribute_columns'][7][1],'locate_time'=>$rowData['attribute_columns'][5][1]];
            }
            $startPK = $response['next_start_primary_key'];
        }
        return $tracks;
    }

    /**
     * 获取终端在某段时间内的报文
     * @param $imei_sn 设备编号
     * @param int $start_time 时间戳
     * @param int $end_time 时间戳
     * @return array 报文列表
     */
    public function getMessages($imei_sn, $start_time, $end_time)
    {
        $client = new OTSClient([
            'EndPoint' => $this->config['tablestore']['gateway'],
            'AccessKeyID' => $this->config['tablestore']['appkey'],
            'AccessKeySecret' => $this->config['tablestore']['secret'],
            'InstanceName' => $this->config['tablestore']['database'],
            'DebugLogHandler'=>false
        ]);
        $startPK = [
            ['imei', md5($imei_sn)],
            ['created', $end_time]
        ];
        $endPK = [
            ['imei', md5($imei_sn)],
            ['created',$start_time]
        ];
        $messages = [];
        while (! empty ($startPK)) {
            $request = [
                'table_name' => 'messages',
                'max_versions' => 1,
                'direction' => DirectionConst::CONST_BACKWARD, // 方向可以为 FORWARD 或者 BACKWARD
                'inclusive_start_primary_key' => $startPK, // 开始主键
                'exclusive_end_primary_key' => $endPK
            ];
            $response = $client->getRange ($request);
            foreach ($response['rows'] as $rowData) {
                $messages[] = ['message'=>$rowData['attribute_columns'][0][1],'created'=>date('Y-m-d H:i:s',$rowData['primary_key'][1][1])];
            }
            $startPK = $response['next_start_primary_key'];
        }
        return $messages;
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
    public function setFamilies($imei_sn,$families = []){
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
        return $this->post('setHost',['imei_sn'=>$imei_sn,'host'=>$host,'port'=>$port]);
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
        $http->setDefaultJsonDecoder($assoc = true);
        $result = $http->$type($this->config['gateway'], ['action'=>$action,'data'=>$params]);
        if ($http->error) {
            throw new \Exception($http->errorMessage,$http->errorCode);
        } else {
            return $result;
        }
    }

}