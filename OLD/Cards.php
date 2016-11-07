<?php

class Cards {
    
    //Создаем свойства класса и задаем начальные параметры
    private $host = "localhost"; // адрес сервера 
    private $database = "card"; // имя базы данных
    private $user = "root"; // имя пользователя
    private $password = "cp2qwe82"; // пароль
    private $mail_admin = "admin@p396071.for-test-only.ru";
    private $headers  = "Content-type: text/html; charset=windows-1251 \r\nОт: Администратора <>\r\n";
    private $connection;
    
    //метод для установления связи с БД
    private function mysql_connect() {
        if ((mysqli_connect($this->host, $this->user, $this->password, $this->database))) {
            $this->connection = mysqli_connect($this->host, $this->user, $this->password, $this->database);
            $this->connection->set_charset("utf8");
            return TRUE;
        } else {
            return FALSE;
        }   
    }
    
    //метод для проверки записи в таблице БД
    private function checkRecord($table, $row, $namebase) {
        $query = "SELECT $row FROM $table WHERE $row = '$namebase'";
        $stmt = $this->connection->query($query);
        
        if ($stmt->num_rows != 0) {
            return FALSE;
        }
        return TRUE;
    }
    
    //метод для получения имени без расширения
    private function nameExt($files) {
        $info = new SplFileInfo($files["filename"]["name"]);
        $extension =  '.'.$info->getExtension();
        $namebase = $info->getBasename("$extension");
        
        return array ($info, $namebase);
    }
    
    //метод для внесения данных в БД из csv
    public function csv2mysql($filename, $namebase) {
        if(!($this->mysql_connect())) {
            unlink("$filename");
            return "Нет подключения к БД.";
        }
        
        //Делаем запрос, существует ли уже такой набор и активен ли он
        $query = "SELECT set_card FROM cards_set WHERE set_card = '$namebase' AND active = '1'";
        $stmt = $this->connection->query($query);
        
        if ($stmt->num_rows != 0) {
            unlink("$filename");
            return "Ошибка! Данный набор уже был загружен и присвоен менеджеру.";
        } elseif (!($this->checkRecord('cards_set', 'set_card', $namebase))) {
            unlink("$filename");
            return "Ошибка! Данный набор уже загружен в список наборов в БД. Присвойте его менеджеру.";
        }
        
        //Открываем файл, производим его чтение и внесение данных
        if (($handle = fopen("$filename", "r")) !== FALSE) {
            $activ_number = 0; //Количество невнесенных номеров
            $number2mysql = 0; //Количество внесенных номеров
            $today = date("Y-m-d-H:i:s"); 
            //
            while (($data = fgetcsv($handle, 100, ",")) !== FALSE) {
                $num = count($data);
                for ($c=0; $c < $num; $c++) {
                    
                    $xr = 0; //переменная для проверки номера
                    $query = "SELECT number FROM no_activation WHERE number = '$data[$c]' LIMIT 10";
                    $result = $this->connection->query($query);
                    if (($result->num_rows) != 0) {
                        $xr = 1;                      
                    }
                    if ($xr == 0){
                        $query = "SELECT number FROM activation WHERE number = '$data[$c]' LIMIT 10";
                        $result = $this->connection->query($query);
                        if (($result->num_rows) != 0){
                            $xr = 1; 
                        }
                    }
                    
                    if ($xr == 0) {
                        $query = "INSERT INTO no_activation (number, set_card) VALUES ('$data[$c]', '$namebase')";
                        $this->connection->query($query);
                        $number2mysql++;
                    } else {
                            $fp = fopen("$namebase-$today.txt", 'a');
                            fwrite($fp, $data[$c]."\n");
                            fclose($fp);
                            //$handle2 = fopen("no_active.csv", "w"); 
                            //fputcsv($handle2, $data[$c]);
                            $activ_number++;
                    }                   
                }
            }
 
            $query = "INSERT INTO cards_set (set_card, active) VALUES ('$namebase', '0')";
            $this->connection->query($query);   
            
            //производим индексацию
            $this->index2mysql('no_activation', 'number', 'DROP');
            $this->index2mysql('no_activation', 'number', 'ADD');
            $this->index2mysql('no_activation', 'set_card', 'DROP');
            $this->index2mysql('no_activation', 'set_card', 'ADD');

            fclose($handle); //закрываем файл
            unlink("$filename"); //удаляем файл
            mysqli_close($this->connection); //закрываем соединение
            return    "Данные внесены в БД.<br>"
                    . "Внесенных номер: $number2mysql<br>"
                    . "Невнесенных номеров: $activ_number<br>"
                    . "<a href='http://46.188.37.109/$namebase-$today.txt'>Сохранить невнесенные номера</a>";    
        } else {
            return "Ошибка! Невозможно открыть файл.";
        }
    } 
    
    //метод для загрузки файла на сервер
    public function upload($files, $set_card) {
        
        if(!(is_uploaded_file($files["filename"]["tmp_name"]))) {
            return "Ошибка! Файл не загружен на сервер.";
        }
        
        list($info, $namebase) = $this->nameExt($files);
        
        if ($set_card != $namebase) {
            return "Ошибка. Проверьте номер партии и номер загруженного файла.";
        }
       
        //проверяем какое расширение у файла, и если csv, то запускаем функцию
        if ($info->getExtension() == "csv") {
            move_uploaded_file($files["filename"]["tmp_name"], $files["filename"]["name"]);
            return $this->csv2mysql($info, $namebase);
        } else {
            return "Ошибка! Неизвестный формат файла.";
        }
    }
    
    //метод для получения списка менеджеров
    public function list_manager() {
        
        if(!($this->mysql_connect())) {
            return "Нет подключения к БД.";
        }
        
        $query = "SELECT id, name, surname FROM manager";
                       
        return $this->connection->query($query);
    }
    
     //метод для получения списка наборов карт
    public function list_set() {
        
        if(!($this->mysql_connect())) {
            return "Нет подключения к БД.";
        }
        
        $query = "SELECT id, set_card FROM cards_set WHERE active = '0'";
                       
        return $this->connection->query($query);
    }
    
    //метод для присвоение набора карт менеджеру
    public function set_manager($id_set, $id_manager) {
        
        if(!($this->mysql_connect())) {
            return "Нет подключения к БД.";
        }
        
        $query = "UPDATE cards_set SET manager_id='$id_manager' WHERE id=$id_set;"; 
        $query .= "UPDATE cards_set SET active='1' WHERE id=$id_set"; 
        $this->connection->multi_query($query);
        mysqli_close($this->connection);
        
        return "Набор карт привязан к менеджеру.";
    }
    
    //метод для активации карты
    public function activation($name, $email, $number) {
        
        if(!($this->mysql_connect())) {
            return "Нет подключения к БД.";
        }
        
        //проверем активирована ли карта ранее
        if (!($this->checkRecord('activation', 'number', $number))) {
            return "Ошибка! Карта была ранее активирована.";
        }
        
        //проверяем существует ли карта в списке
        if (($this->checkRecord('no_activation', 'number', $number))) {
            return "Ошибка! Неверный номер карты.";
        }
        
        
        $query = "SELECT set_card FROM no_activation WHERE number = '$number'";
        $result = $this->connection->query($query);
        $row = $result->fetch_row();
        $query = "INSERT INTO activation (number, set_card, name, email) VALUES ('$number', '$row[0]', '$name', '$email')";       
        $this->connection->query($query);
        $query = "DELETE FROM no_activation WHERE number = '$number'";
        $this->connection->query($query);
        
        //производим индексацию
        $this->index2mysql('no_activation', 'number', 'DROP');
        $this->index2mysql('no_activation', 'number', 'ADD');
        $this->index2mysql('no_activation', 'set_card', 'DROP');
        $this->index2mysql('no_activation', 'set_card', 'ADD');
        $this->index2mysql('activation', 'number', 'DROP');
        $this->index2mysql('activation', 'number', 'ADD');
        $this->index2mysql('activation', 'set_card', 'DROP');
        $this->index2mysql('activation', 'set_card', 'ADD');
        mysqli_close($this->connection);
        
        //отправем email
        $this->email($email, $name, $number);
        
        return "Поздравляем! Карта активирована.";       
    }
    
    //метод для индексации столбцов в таблицах БД
    private function index2mysql($table, $row, $res) {
        if ($res == 'DROP'){
            $query = "ALTER TABLE $table $res INDEX $row";
            $this->connection->query($query);
            return TRUE;
        }
        
        if ($res == 'ADD'){
            $query = "ALTER TABLE $table $res INDEX ($row)";
            $this->connection->query($query);
            return TRUE;
        }
        
        return FALSE;
    }
    
    //метод для отправки email 
    private function email($to, $name, $number) {
        $subject = "Активация карты";
        $message = ' 
            <html> 
                <head> 
                    <title>Активация карты</title> 
                </head> 
            <body> 
                <p> Уважаемый, '.$name.'.<br>
                    Ваша карта №'.$number.' успешно активирована.</p>
                <p> Благодарим за использование нашего продукта.</p>
            </body> 
            </html>';
        $msg_admin = ' 
        <html> 
            <head> 
                <title>Активация карты</title> 
            </head> 
            <body> 
                <p> Карта №'.$number.' активирована.</p>
                <p> Имя: '.$name.'<br>
                    email: '.$to.' </p>
            </body> 
        </html>';
     
        mail($to, $subject, $message, $this->headers);
        mail($this->mail_admin, $subject, $msg_admin, $this->headers);
        
        return true;
    }
    
    //метод для вывода статиски менеджеров
    public function statistics() {
        
        if(!($this->mysql_connect())) {
            return "Нет подключения к БД.";
        }
        
        $query = "SELECT id, name, surname FROM manager";                   
        $stmt = $this->connection->query($query);
        while ($result = $stmt->fetch_assoc()) {
            $id = $result['id'];
            $name = $result['name'];
            $surname = $result['surname'];
            
            $temp = $temp2 = 0;
            $query2 = "SELECT set_card FROM cards_set WHERE manager_id = '$id'";
            $stmt2 = $this->connection->query($query2);
            while ($result2 = $stmt2->fetch_assoc()) {
                $set_card = $result2['set_card'];
                $query3 = "SELECT id FROM no_activation WHERE set_card = '$set_card'";
                $stmt3 = $this->connection->query($query3);
                $temp = $temp + $stmt3->num_rows;
                $query4 = "SELECT id FROM activation WHERE set_card = '$set_card'";
                $stmt4 = $this->connection->query($query4);
                $temp2 = $temp2 + $stmt4->num_rows;
            }
            print $name." ".$surname.": ";
            print "Неактивированных карт: $temp. Активированных карт: $temp2. <br>";              
        }      
        mysqli_close($this->connection);
    }
}
?>