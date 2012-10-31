<?
$db_user='lms';
$db_host='127.0.0.1';
$db_pass='password';
$db_name='lms';

mysql_connect($db_host,$db_user,$db_pass);
mysql_select_db($db_name);

function AddBalance($addbalance)
        {
                $addbalance['value'] =
str_replace(',','.',round($addbalance['value'],2));
                mysql_query("INSERT INTO cash (time, userid, value, type,
taxid, customerid, comment, docid, itemid) VALUES (".
                (isset($addbalance['time']) ? $addbalance['time'] :
time()).",".
                                            (isset($addbalance['userid'])
? $addbalance['userid'] : $this->AUTH->id).",".
                                            $addbalance['value'].",".
                                            (isset($addbalance['type']) ?
$addbalance['type'] : 0).",".
                                            (isset($addbalance['taxid']) ?
$addbalance['taxid'] : 0).",".

$addbalance['customerid'].",\"".
                                            $addbalance['comment']."\",".
                                            (isset($addbalance['docid']) ?
$addbalance['docid'] : 0).",".
                                            (isset($addbalance['itemid'])
? $addbalance['itemid'] : 0).")");
        }



$importlist = mysql_query('SELECT * FROM cashimport WHERE closed = 0 AND
value > 0 ORDER BY id');

while ($import = mysql_fetch_assoc($importlist)) {

                        mysql_query("UPDATE cashimport SET closed = 1
WHERE id = ".$import['id']);
                        $balance['time'] = $import['date'];
                        $balance['type'] = 3;
                        $balance['value'] = $import['value'];
                        $balance['customerid'] = $import['customerid'];
                        $balance['comment'] = $import['description'];
                        $balance['userid'] = 0;
                        AddBalance($balance);
                }


?>
