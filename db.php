<?php
// Cấu hình và các kết nối dùng chung cho toàn bộ ứng dụng.
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', 3306);
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'eightmart');
}

function eightmart_mysqli_connection()
{
    static $connection = null;

    if ($connection === null) {
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_OFF);
        }

        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if (mysqli_connect_errno()) {
            $error = mysqli_connect_error();
            $connection = null;
            throw new Exception('Không kết nối được CSDL eightmart: ' . $error);
        }
        if (!$connection->set_charset('utf8')) {
            $error = $connection->error;
            $connection->close();
            $connection = null;
            throw new Exception('Không thể thiết lập mã hóa UTF-8 cho CSDL: ' . $error);
        }
    }

    return $connection;
}

function eightmart_mysqli_execute($sql, $params = array())
{
    $connection = eightmart_mysqli_connection();
    $statement = $connection->prepare($sql);
    if (!$statement) {
        throw new Exception('Không thể chuẩn bị truy vấn CSDL: ' . $connection->error);
    }

    if (count($params) > 0) {
        $values = array_values($params);
        $types = '';
        foreach ($values as $value) {
            if (is_int($value) || is_bool($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_string($value) || is_null($value)) {
                $types .= 's';
            } else {
                $statement->close();
                throw new InvalidArgumentException('Tham số truy vấn CSDL phải là giá trị scalar hoặc null.');
            }
        }

        $bindParams = array(&$types);
        foreach ($values as $index => $value) {
            $bindParams[] = &$values[$index];
        }
        if (!call_user_func_array(array($statement, 'bind_param'), $bindParams)) {
            $error = $statement->error;
            $statement->close();
            throw new Exception('Không thể gắn tham số truy vấn CSDL: ' . $error);
        }
    }

    if (!$statement->execute()) {
        $error = $statement->error;
        $statement->close();
        throw new Exception('Không thể thực thi truy vấn CSDL: ' . $error);
    }

    return $statement;
}

function eightmart_mysqli_fetch_all_assoc($statement)
{
    $metadata = $statement->result_metadata();
    if (!$metadata) {
        $statement->close();
        return array();
    }

    $fields = array();
    while ($field = $metadata->fetch_field()) {
        $fields[] = $field->name;
    }
    $metadata->free();

    $values = array_fill(0, count($fields), null);
    $bindParams = array();
    foreach ($values as $index => $value) {
        $bindParams[] = &$values[$index];
    }
    if (!call_user_func_array(array($statement, 'bind_result'), $bindParams)) {
        $error = $statement->error;
        $statement->close();
        throw new Exception('Không thể đọc kết quả truy vấn CSDL: ' . $error);
    }

    $rows = array();
    while (true) {
        $fetchResult = $statement->fetch();
        if ($fetchResult === null) {
            break;
        }
        if ($fetchResult === false) {
            $error = $statement->error;
            $statement->close();
            throw new Exception('Không thể tải kết quả truy vấn CSDL: ' . $error);
        }

        $row = array();
        foreach ($fields as $index => $field) {
            $row[$field] = $values[$index];
        }
        $rows[] = $row;
    }
    $statement->close();
    return $rows;
}

function eightmart_mysqli_fetch_one_assoc($statement)
{
    $rows = eightmart_mysqli_fetch_all_assoc($statement);
    return count($rows) > 0 ? $rows[0] : null;
}

function eightmart_mysqli_fetch_value($statement)
{
    $row = eightmart_mysqli_fetch_one_assoc($statement);
    if ($row === null) {
        return null;
    }
    return reset($row);
}

function eightmart_mysqli_affected_rows($statement)
{
    $affectedRows = $statement->affected_rows;
    $statement->close();
    return $affectedRows;
}

function eightmart_mysqli_begin_transaction()
{
    if (!eightmart_mysqli_connection()->query('START TRANSACTION')) {
        throw new Exception('Không thể bắt đầu giao dịch CSDL: ' . eightmart_mysqli_connection()->error);
    }
}

function eightmart_mysqli_commit()
{
    if (!eightmart_mysqli_connection()->query('COMMIT')) {
        throw new Exception('Không thể xác nhận giao dịch CSDL: ' . eightmart_mysqli_connection()->error);
    }
}

function eightmart_mysqli_rollback()
{
    if (!eightmart_mysqli_connection()->query('ROLLBACK')) {
        throw new Exception('Không thể hoàn tác giao dịch CSDL: ' . eightmart_mysqli_connection()->error);
    }
}
