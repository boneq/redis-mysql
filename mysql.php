<?php
/**
 * Created by PhpStorm.
 * User: bone
 * Date: 2017/5/24
 * Time: 9:23
 * redis做缓存数据入库
 */
//检测有没有Redis扩展
if ( !class_exists('redis') )
{
    echo "PHP extension does not exist: Redis";
    exit;
}
if ( !class_exists('pdo') )
{
    echo "PHP extension does not exist: PDO";
    exit;
}

//程序开始时间
$starttime=time();

//尝试连接Redis失败返回信息
try{
    $redis = new \Redis();
    $state=$redis->connect('127.0.0.1', 6379);
    if (!$state) {
        throw new RedisException();
        $redis=null;
        exit;
    }
}catch(RedisException $e){
    echo"Redis连接失败：".$e->getMessage();
    $redis=null;
    exit;
}

//尝试连接数据库失败返回信息
try{
    $pdo=new PDO("mysql:host=localhost;dbname=dianbiao","root","112244",array(PDO::ATTR_AUTOCOMMIT=>0));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    echo"数据库连接失败：".$e->getMessage();
    $pdo=null;
    $redis=null;
    exit;
}

//redis列队长度
$max = $redis->lLen("test");
//成功执行sql条数
$number=0;

//执行数据插入

//开启事务处理
$pdo->beginTransaction();
$conunt=0;
while ($conunt<$max){
    $mess=$redis->rPop('test');
    $sql="insert into `cc` (`name`) values ('".$mess."');";
    $row=$pdo->exec($sql);
    if ($row) {
        $number++;
    }else{
        echo $sql;
        //关闭redis连接
        $redis->close();
        $redis=null;
        //关闭数据库连接
        $pdo=null;
        exit;
    }
    $conunt++;
}
$pdo->commit();


//关闭redis连接
$redis->close();
$redis=null;
//关闭数据库连接
$pdo=null;

//程序结束时间
$endtime=time();
var_dump('redis中的数据'.$max.'条');
var_dump('导入数据库的数据'.$number.'条');
var_dump('运行时间'.($endtime-$starttime).'秒');
