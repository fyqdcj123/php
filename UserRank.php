<?php
$t1 = microtime(true);
echo json_encode(getRange(0,100));
$t2 = microtime(true);
echo '耗时'.round($t2-$t1,3).'秒';
//桶排序
function updateScore($uid,$newScore){
    $r = Db::table('UserRank')->where('uid',$uid)->select();
    if (!empty($r)) {
        $oldScore = -1;
        #获得用户旧的得分
        $QueryOldScore = Db::table('UserRank')->where('uid',$uid)->select();
        if (!empty($QueryOldScore)) {
            $oldScore = $QueryOldScore[0]['score'];
        };
        $update['score'] = $newScore;
        $UpdateScore = Db::table('UserRank')->where('uid',$uid)->update($update);
        updateCount($oldScore,true);
        updateCount($newScore,false);
    }else {
        #新用户添加得分
        Db::table('UserRank')->add(['uid'=>$uid,'score'=>$newScore]);
        $exist = Db::table('UserCount')->where('score',$newScore)->find();
        if (!empty($exist)) {
            $update['count'] = $exist['count']+1;
            Db::table('UserCount')->where('score',$newScore)->update($update);
        }else {
            Db::table('UserCount')->add(['score'=>$newScore,'count'=>1]);
        }
    }
    
}
function getRange($start,$end){
    $rangeLength = $end - $start;
    $allUser = [];
    $total = 0;
    $res = Db::table('UserCount')->order('score desc')->select();
    $lastScore = -1;
    $lastTotal = 0;
    $startIndex = 0;
    foreach ($res as $i) {
        $lastScore = $i['score'];
        $total += $i['count'];
        if($total > $start)
            break;
        $lastTotal = $total;
        $startIndex += 1;
    }
    $leftNum = $start - $lastTotal;
    $curIndex = $startIndex;
    $limitLength = $rangeLength;
    while($curIndex < count($res)){
        $curScore = $res[$curIndex]['score'];
        $users = Db::table('UserRank')->where('score',$curScore)->limit($leftNum,$limitLength)->select();
        if (!empty($users)) {
            $allUser[]=$users;
        }
        $leftNum = 0;
        $curIndex += 1;
        $limitLength -= count($users);
    }
    return $allUser;
}
function getRank($score){
    $getRank = Db::table('UserCount')->where('score','>',$score)->sum('count');
    return $getRank > 0?$getRank:0;
}
function getUser($rank){
    $total = 0;
    $res = Db::table('UserCount')->order('score desc')->select();
    $lastScore = -1;
    $lastTotal = 0;
    foreach ($res as $i) {
        $lastScore = $i['score'];
        $total += $i['count'];
        if($total > $rank)
            break;
        $lastTotal = $total;
    }
    $leftNum = $rank - $lastTotal;
    $res = Db::table('UserRank')->where('score',$lastScore)->limit($leftNum,1)->select();
    return $res[0]['uid'];
}
function initRank(){
    Db::table('UserCount')->del();
    $res = Db::table('UserRank')->select();
    foreach ($res as $i) {
        $r = Db::table('UserCount')->where('score',$i['score'])->select();
        if (!empty($r)) {
            $update['count'] =$r[0]['count']+1;
            Db::table('UserCount')->where('score',$i['score'])->update($update);
        }else {
            Db::table('UserCount')->add(['score'=>$i['score'],'count'=>1]);
        }
    }
}
function updateCount($Score,$ReduceOrAdd = true){
    $UpdateCount = Db::table('UserCount')->where('score',$Score)->find();
    $score = $ReduceOrAdd?$UpdateCount['count']-1:$UpdateCount['count']+1;
    $update['count'] = $score;
    $UpdateScore = Db::table('UserCount')->where('score',$Score)->update($update);
    return $UpdateScore;
}
?>
