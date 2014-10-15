<?php

    require "config/sql_connect.php";
    session_start();
    $sid = session_id(); 
    mysqli_query($con, "UPDATE adventurers SET session_id=null WHERE session_id='$sid'");
    session_unset();
    
?>
