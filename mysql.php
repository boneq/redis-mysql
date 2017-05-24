<?php
/**
 * Created by PhpStorm.
 * User: bone
 * Date: 2017/5/24
 * Time: 9:23
 * redis做缓存数据入库(Redis cache MySQL data storage, in cli mode )
 */
//检测有没有Redis扩展 (Detection extension redis)
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

//程序开始时间(Program start time)
$starttime=time();

//尝试连接Redis失败返回信息(Attempting to connect to Redis failed to return information)
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

//尝试连接数据库失败返回信息(Attempting to connect to database failed to return information)
try{
    $pdo=new PDO("mysql:host=localhost;dbname=dianbiao","root","112244",array(PDO::ATTR_AUTOCOMMIT=>0));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    echo"数据库连接失败：".$e->getMessage();
    $pdo=null;
    $redis=null;
    exit;
}

//redis列队长度(Redis Queue length)
$max = $redis->lLen("test");
//成功执行sql条数(The SQL number is successfully executed)
$number=0;

//执行数据插入(Perform data insertion)

//开启事务处理(Open transaction processing)
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
        //关闭redis连接(Close the redis connection)
        $redis->close();
        $redis=null;
        //关闭数据库连接(Close database connections)
        $pdo=null;
        exit;
    }
    $conunt++;
}
$pdo->commit();


//关闭redis连接(Close the redis connection)
$redis->close();
$redis=null;
//关闭数据库连接(Close database connections)
$pdo=null;

//程序结束时间(program end time)
$endtime=time();
var_dump('redis中的数据'.$max.'条');
var_dump('导入数据库的数据'.$number.'条');
var_dump('运行时间'.($endtime-$starttime).'秒');
