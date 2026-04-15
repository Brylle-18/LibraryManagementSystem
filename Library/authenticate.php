<?php
session_start();
include 'db_connection.php';


if($_SERVER["REQUEST_METHOD"] == "POST"){
  $username =  $_POST['username'];
  $password =  $_POST['password'];

$sql = "SELECT * FROM USERS where username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$username);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    $row = $result->fetch_assoc();

    // if($password == $row['password']){
    if(password_verify($password,$row['password'])){

    $_SESSION['usrID'] = $row['id'];
        $_SESSION['username'] = $row['username'];

        header("Location:dashboard.php");
        exit();
    }else{
         echo "Error!";
    }
}else{
     echo "invalid username and password!";

}




}

$conn->close();