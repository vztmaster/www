<?php

class Med {
    private $host = "localhost"; // адрес сервера 
    private $database = "kir"; // имя базы данных
    private $user = "vztmaster"; // имя пользователя
    private $password = "cp2qwe82"; // пароль
    
    private $connection; //Для выполнения запросов к БД
    
    private function mysql_connect() {
        if ((mysqli_connect($this->host, $this->user, $this->password, $this->database))) {
            $this->connection = mysqli_connect($this->host, $this->user, $this->password, $this->database);
            $this->connection->set_charset("utf8");
            return TRUE;
        } else {
            return FALSE;
        }   
    }
    
    public function receipt_date () {
        if(!($this->mysql_connect())) {
            unlink("$this->filename");
            return "Нет подключения к БД.";
        }
        
        $query = "SELECT id, date FROM date ORDER BY id";
        $result = $this->connection->query($query);
        $i = 0;
        while ($row = $result->fetch_assoc()) {
            $date[$i] = $row['date'];
            $date_id[$i] = $row['id'];
            $i++;
        }
        
        $query = "SELECT id, name, quantity  FROM medicine ORDER BY id";
        $result = $this->connection->query($query);
        $i = 0;
        while ($row = $result->fetch_assoc()) {
            $medicine[$i] = $row['name'];   
            $medicine_id[$i] = $row['id'];
            $i++;
        }
        
        //print_r($medicine_id);
        //print_r($medicine);
        
        $count_date = count($date_id);
        $count_medicine = count($medicine_id) - 1;
        
        for($i=0; $i <= $count_date; $i++){
            for($y=0; $y <= $count_medicine; $y++){
               $query = "SELECT id, time, medicine_id, date_id  FROM schedule WHERE date_id = '$date_id[$i]' AND medicine_id = '$medicine_id[$y]'";
               $result = $this->connection->query($query);
               $z = 0;
               while ($row = $result->fetch_assoc()) {
                   //print $medicine_id[$y];
                   //print $z;
                   $shedule[$medicine_id[$y]][$z] = $row['time'];
                   $z++;
               }
            }        
        }
        
        
        
        ?><table border="1">
            <tr>
                <td></td>
                <?php
                    for($i=0; $i <= $count_date; $i++){
                        print "<td>$date[$i]</td>";
                    }
                ?>
            </tr>
            <?php
                for($i=0; $i <= $count_medicine; $i++){
                    print   "<tr>"
                               ."<td>$medicine[$i]</td>";
                        for($i=0; $i <= $count_date; $i++){
                            
                        }
                           
                }
            ?>
            </table>
        <?php
        
        return TRUE;
    }
    
    
    
}
