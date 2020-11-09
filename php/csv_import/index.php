<?php

class Import
{

    protected static $_settings = [
        'servername' => 'n96028z0.beget.tech',
        'username' => 'n96028z0_import',
        'password' => 'oYyE%9*I',
        'db' => 'n96028z0_import',
    ];

    public static $_data = [];
    protected static $connection;
    public static $filename;
    public static $filesize;

    public static function Init()
    {
        if (!self::getDbConnection()) {
            return false;
        }

        self::getDbData();

        if (isset($_POST["Export"])) {
            self::ExportData();
        }

        if (isset($_POST["Import"])) {
            self::ImportData();
        }

        return self::$_data;
    }

    protected static function ExportData()
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=data.csv');
        $output = fopen("php://output", "w");
        fputcsv($output, array('ID', 'ARTICUL', 'PRICE', 'COUNT'));
        $query = "SELECT * from test ORDER BY `COUNT` DESC";
        $result = mysqli_query(self::$connection, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, $row);
        }
        fclose($output);
    }

    protected static function ImportData()
    {
        self::$filename = $_FILES["file"]["tmp_name"];
        self::$filesize = $_FILES["file"]["size"] > 0 ? $_FILES["file"]["size"] : false;

        $fileArray = pathinfo($_FILES["file"]["name"]);

        if($fileArray['extension'] != 'csv'){
            echo "<script type=\"text/javascript\">
                    alert(\"Invalid File:Please Upload CSV File.\");
                    window.location = \"index.php\"
                  </script>";
        }

        if (self::$filesize && !empty(self::$filename)) {
            $file = fopen(self::$filename, "r");
            while (($lineData = fgetcsv($file, 10000, ",")) !== FALSE) {

                list($articul, $price, $count) = explode(';', current($lineData));

                if(!empty($articul) && !empty($price) && !empty($count)){

                    if(!empty(self::$_data[$articul])){
                        $sql = "UPDATE `test` SET `PRICE` = $price, `COUNT` = $count WHERE `ARTICUL` = '" . $articul . "'";
                    } else {
                        $sql = "INSERT into test (`ARTICUL`, `PRICE`, `COUNT`)
                            VALUES ('" . $articul . "',
                                    '" . $price . "',
                                    '" . $count . "'
                            )";
                    }

                    $result = mysqli_query(self::$connection, $sql);
                    if($result){
                        self::$_data['UPDATED'][$articul] = [
                            'ARTICUL' => $articul,
                            'PRICE' => $price,
                            'COUNT' => $count,
                        ];
                        unset(self::$_data[$articul]);
                    }
                }
            }
            self::setDefaultData();
            fclose($file);
        }
    }

    protected static function setDefaultData()
    {
        $sql = "UPDATE `test` SET `COUNT` = 0 WHERE `ARTICUL` IN('" . implode('\', \'', array_keys(self::$_data)) . "')";
        mysqli_query(self::$connection, $sql);
    }

    protected static function getDbData()
    {
        $sql = "SELECT * FROM test";
        $result = mysqli_query(self::$connection, $sql);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                self::$_data['ALL'][$row['ARTICUL']] = $row;
            }
        }
    }

    public static function getDbConnection()
    {
        try {
            self::$connection = mysqli_connect(
                self::$_settings['servername'],
                self::$_settings['username'],
                self::$_settings['password'],
                self::$_settings['db']
            );
        } catch (exception $e) {
            echo "Connection failed: " . $e->getMessage();
            return false;
        }
        return true;
    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Kelnik Test Import</title>
    <script src="asset/js/jquery.min.js"></script>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css" />
    <script src="asset/js/bootstrap.min.js"></script>

</head>

<body>
<div id="wrap">
    <div class="container">
        <div class="row">

            <!-- import data -->
            <form class="form-horizontal" action="" method="post" name="upload_excel" enctype="multipart/form-data">
                <fieldset>

                    <!-- Form Name -->
                    <legend>Тестовый импорт данных</legend>

                    <!-- File Button -->
                    <div class="form-group">
                        <label class="col-md-4 control-label" for="filebutton">Выберите файл</label>
                        <div class="col-md-4">
                            <input type="file" name="file" id="file" class="input-large">
                        </div>
                    </div>

                    <!-- Button for import data -->
                    <div class="form-group">
                        <label class="col-md-4 control-label" for="singlebutton">Импорт данных</label>
                        <div class="col-md-4">
                            <button type="submit" id="submit" name="Import" class="btn btn-primary button-loading" data-loading-text="Loading...">Импорт</button>
                        </div>
                    </div>

                    <form class="form-horizontal" action="" method="post" name="upload_excel"
                          enctype="multipart/form-data">
                        <div class="form-group">
                            <div class="col-md-4 col-md-offset-4">
                                <input type="submit" name="Export" class="btn btn-success" value="Экспорт в Excel"/>
                            </div>
                        </div>
                    </form>

                </fieldset>
            </form>
        </div>

        <?php $allData = Import::Init(); ?>

        <?php if(!empty($allData['UPDATED'])): ?>
            <!-- Large modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModal">
                Список последних импортированных записей
            </button>

            <div id="myModal" class="modal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Список последних импортированных записей</h4>
                        </div>
                        <div class="modal-body">
                            <p id="updated_info">
                            <div class='table-responsive'>
                                <table id='myTable' class='table table-striped table-bordered'>
                                    <thead>
                                    <tr>
                                        <th>ARTICUL</th>
                                        <th>PRICE</th>
                                        <th>COUNT</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($allData['UPDATED'] as $row): ?>
                                        <tr>
                                            <td><?= $row['ARTICUL'] ?></td>
                                            <td><?= $row['PRICE'] ?></td>
                                            <td><?= $row['COUNT'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-dismiss="modal">Закрыть</button>
                        </div>
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->
        <?php endif; ?>

        <!-- Large modal -->
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModalAll">
            Список всех записей
        </button>

        <div id="myModalAll" class="modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Список всех записей</h4>
                    </div>
                    <div class="modal-body">
                        <p id="updated_info">
                            <?php if(!empty($allData['ALL'])): ?>
                                <div class='table-responsive'>
                                    <table id='myTable' class='table table-striped table-bordered'>
                                        <thead>
                                        <tr>
                                            <th>ARTICUL</th>
                                            <th>PRICE</th>
                                            <th>COUNT</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($allData['ALL'] as $row): ?>
                                            <tr>
                                                <td><?= $row['ARTICUL'] ?></td>
                                                <td><?= $row['PRICE'] ?></td>
                                                <td><?= $row['COUNT'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <h2>Список пуст</h2>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Закрыть</button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
    </div>
</div>
</body>

</html>

<script>
    $(document).ready(function(){
        $('#myModal').modal('show');
    });
</script>