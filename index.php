<?php
header('Content-type: text/html; charset=utf-8');

class Utalas {
    
    private $mysqli = null;
    public $sikeres = false;


    public function __construct() {
        try {
            $this->mysqli = new mysqli('localhost', 'root', '', 'bankszamlak');
            $this->mysqli->set_charset("utf8");
        } catch (Exception $ex) {
            die($ex->getMessage());
        }
    }
    
    public function utalas($from, $to, $osszeg) {
        try {
            echo '<ul>';
            $this->mysqli->begin_transaction(); //-- amelyre vissza lehet állni
            // --  Lekérdezzük a rendelkezésre álló összeget
            $kuldoEgyenlege = 0;
            if($this->mysqli->query("SELECT osszeg FROM szamlak WHERE id = ".$from)->fetch_assoc()['osszeg'] < $osszeg){
                echo '<li class="rollback">Nem áll rendelkezésre az utalni kívánt '.number_format($osszeg,0,","," ").' Ft!</li>';
                return false;
            } else {
                echo '<li>Rendelkezésre áll az összeg!</lil>';    
            }
            //-- levonás -------------------------------------
            if($this->mysqli->query("UPdate szamlak SET osszeg = osszeg - ".$osszeg." WHERE id = ".$from) && $this->mysqli->affected_rows == 1){
                echo '<li>'.$osszeg.' Ft összeg levonása sikeres!</li>';
            } else {
                echo '<li class="rollback">'.$osszeg.' Ft összeg levonása sikertelen</li>';
                return;
            }
            //-- Jóváírás ---------------------------------------
            $sql ="UPdate szamlak SET osszeg = osszeg + ".$osszeg." WHERE id = ".$to;
//            var_dump($sql);
            $result = $this->mysqli->query($sql);
//            var_dump($result);
//            var_dump($this->mysqli->affected_rows );
            if($this->mysqli->affected_rows === 1){
                echo '<li class="commit">'.$osszeg.' Ft összeg jóváírása sikeres!</li>';
                $this->mysqli->commit();
                $this->sikeres = true;
            } else {
                $this->mysqli->rollback();
                echo '<li class="rollback">'.$osszeg.' Ft összeg jóváírása sikertelen!</li>';
                $this->sikeres = false;
            }
            echo '</ul>';
        } catch (Exception $ex) {
            echo '<p>Nem történt semmi!</p>';
            $this->mysqli->rollback();
            die($ex->getMessage());
        }
    }
    
    public function __destruct() {
        $this->mysqli->close();
    }
}
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html lang="hu">
    <head>
        <meta charset="UTF-8">
        <title>Banki tranzakció</title>
        <style>
            body {
                margin: 4vmax;
                font-size: 3vmax;
            }
            table {
                width: 50%;
                margin-left: 10vmax;
            }
            table, td, th {
                border: 1px solid;
                border-collapse: collapse;
            }
            td {
                padding: .5rem 1.5rem;
            }
            .commit {
                color: green;
                background-color: #C6EFCE;
            }
            .rollback {
                color: red;
                background-color: #FFC7CE;
            }
        </style>
    </head>
    <body>
        <?php 
            $obj = new Utalas();
            try {
                $mysqli = new mysqli('localhost', 'root', '', 'bankszamlak');
                $mysqli->set_charset("utf8");
            } catch (Exception $ex) {
                die($ex->getMessage());
            }
        ?>
        <h2>Számlák induláskor</h2>
        <table>
            <tr>
                <th>id</th>
                <th>név</th>
                <th>összeg</th>
            </tr>
        <?php
        $sql = "SELECT `id`,`nev`,`osszeg` FROM `szamlak`";
        $result = $mysqli->query($sql);

        if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
              echo '<tr>'
              . '<td>' . $row["id"]. '</td>'
              . '<td>' . $row["nev"]. '</td>'
              . '<td>' . $row["osszeg"]. '</td>'
              . '</tr>';
          }
        } else {
          echo "0 results";
        }
//        $mysqli->close();
        ?>
        </table>

        <h1>Banki utalás</h1>
        <?php
            $obj->utalas(1, 2, 10000);
        ?>
        <h2>Számlák utalás után</h2>
        <table class="<?php echo $obj->sikeres?'commit':'rollback'; ?>">
            <tr>
                <th>id</th>
                <th>név</th>
                <th>összeg</th>
            </tr>
        <?php
        $result = $mysqli->query("SELECT `id`,`nev`,`osszeg` FROM `szamlak`");

        if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
              echo '<tr>'
              . '<td>' . $row["id"]. '</td>'
              . '<td>' . $row["nev"]. '</td>'
              . '<td>' . $row["osszeg"]. '</td>'
              . '</tr>';
          }
        } else {
          echo "0 results";
        }
        $mysqli->close();
        ?>
        </table>
    </body>
</html>
