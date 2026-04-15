<?php
session_start();

include 'db_connection.php';

//check connection
if($_SERVER["REQUEST_METHOD"] == "POST"){

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT username FROM USERS where username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$username);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows>0){
    echo "ALREADY EXIST!"; 

}else{
    //HASH THE PASSWORD
    $hashedPassword = password_hash($password,PASSWORD_DEFAULT);

    $sql = "INSERT INTO users(username, password) VALUES (?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$username,$hashedPassword);

    if($stmt->execute()){
        echo "Register Successfully!";
    }else{
        echo " Error";
    }




    



}





}






