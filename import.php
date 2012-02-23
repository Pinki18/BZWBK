<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Pinki
 * Date: 23.02.12
 * Time: 18:14
 * To change this template use File | Settings | File Templates.
 */

echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
header ('Content-type: text/html; charset=utf-8');
#   Konfiguracja
$imap_serwer            = "imap.googlemail.com";
$imap_serwer_port       = "993";
$imap_serwer_opcje      = "/imap/ssl/novalidate-cert";
$imap_username          = "";
$imap_password          = "";
$imap_katalog           = "BZWBK";

$tmpdir                 = "/tmp/import/";
$import_dir             = "/var/www/hinet.pl/lms/import_archiwum/";

$db_user                = '';
$db_pass                = '';
$db_name                = '';
$db_host                = '';


mysql_connect($db_host,$db_user,$db_pass) or die("Nie mogę sie połączyć z bazą danych: " . mysql_error());
mysql_select_db($db_name) or die("Nie znaleziono bazy : " . mysql_error());
mysql_query('SET NAME UTF-8');
mysql_query('SET CHARACTER SET UTF8');

error_reporting(E_ALL);
ini_set("display_errors", 1);

$mbox = imap_open("{" . $imap_serwer . ":" . $imap_serwer_port.$imap_serwer_opcje . "}" . $imap_katalog, $imap_username, $imap_password) or die("Nie mogę sie połączyć: " . imap_last_error());

echo "Połączenie nawiązane...\n";

$msgs = imap_search($mbox, 'UNSEEN');

if (is_array($msgs)) {

    foreach($msgs as $jk) {

        $structure = imap_fetchstructure($mbox, $jk);
        $parts = $structure->parts;
        $fpos=2;
        $header = imap_header($mbox, $jk);
        $data_wiadomosci = date("Y-m-d_H:i", strtotime($header->date));

        for($i = 1; $i < count($parts); $i++) {

            $message["pid"][$i] = ($i);
            $part = $parts[$i];

            if($part->disposition == 'ATTACHMENT') {

                echo '<p>' . $part->dparameters[0]->value . '</p>';
                $filename=$part->dparameters[0]->value;
                $mege="";
                $data="";
                #                $mege = imap_fetchbody($mbox,$jk,$fpos, FT_PEEK);
                $mege = imap_fetchbody($mbox,$jk,$fpos);
                $data = imap_base64($mege);

                $fp=fopen("$tmpdir$filename",'w');
                fputs($fp,$data);
                fclose($fp);

                $fpos+=1;
                $plik = "$tmpdir$filename";

                parsuj_plik($plik);

                rename($tmpdir.$filename,$import_dir . $data_wiadomosci . "_" . $filename);

            }
        }
    }
}

function parsuj_plik ($plik) {

    # Line format:
    # 882773572|29112011|2.00|Nazwa|70109024280000000117547076|87109000048970000000000002|tytuł
    $content = fopen($plik, "r");

    while (!feof($content)) {

        $line = fgets ($content);
        if (preg_match("/^[0-9]{8,16}(.*)/", $line,$z)) {
            //echo $z[0]."<br>";
            $l=explode("|",$z[0]);

            $hash          = $l[0];
            $data_wplaty   = $l[1];
            $kwota         = $l[2];
            $opis          = $l[3] . " " . $l[6];
            $opis_pl       = addslashes(iconv('CP1250','UTF-8//TRANSLIT',$opis));
            $customerid    = substr($l[5], -4);


            //echo $hash . " " . $data_wplaty . " " . $kwota . " " . $opis_pl . " " . $customerid . "<br>";
            dodaj_wpis($hash,$data_wplaty,$kwota,$opis_pl,$customerid);
        }

    }

}

function dodaj_wpis ($hash,$data_wplaty,$kwota,$opis_pl,$customerid) {

    #echo $hash . " " . $data_wplaty . " " . $kwota . " " . $opis_pl . " " . $customerid . "<br>";
    $rs = mysql_query("SELECT id FROM cashimport WHERE hash='" . $hash . "'");

    if (mysql_num_rows($rs)==0) {

        $rs = mysql_query("SELECT lastname,name FROM customersview WHERE id = '" . $customerid . "'");
        //echo "SELECT id,lastname,name FROM customersview WHERE id = '" . $customerid . "'<br>";
        $row = mysql_fetch_row($rs);
        $customer = $row[0] . " " . $row[1];

        //echo "INSERT INTO cashimport (date, value, customer, description, customerid, hash, sourceid) values (UNIX_TIMESTAMP(str_to_date('" . $data_wplaty . "','%d%m%Y')), '$kwota', '$customer', '$opis_pl', '$customerid', '$hash', 2)";
        mysql_query("INSERT INTO cashimport (date, value, customer, description, customerid, hash, sourceid) values (UNIX_TIMESTAMP(str_to_date('" . $data_wplaty . "','%d%m%Y')), '" . $kwota . "', '" . $customer . "', '" . $opis_pl . "', '" . $customerid . "', '" . $hash . "', 2)") or die("Nie dodano wpisu : " . mysql_error());
        echo "Dodano: " . $customer . "<br />";

    } else {

        echo "Hash: " . $hash . " istnieje, pomijam wpis...<br>";

    }


}