<?php

session_start();

if (!isset($_SESSION["user_id"])){
header("location: auth.php");
exit;
}
require_once __DIR__ . '/../config/database.php';

$database = new Database() ;
$db = $database->getConnection();

try{
$query = "select (*) from users";
$result = $db-> query($query);
$allusers = $result->fetchAll(PDO::FETCH_ASSOC);

}
   