<?php

    //=========== Message Queue Functions
    
    // Adds an error to the adventurer's message queue.
    // Should use this form of error in most cases.
    function send_error($aid, $text) {
        $text = "(System Error: " . $text . ")";
        $type = "error";
        //echo json_encode(array("success"=>false, "errors"=>array($text)));
        
        require "config/sql_connect.php";
        $query = "INSERT INTO message_queue (type, text, adventurer_id) VALUES ('$type', '$text', $aid)";
        mysqli_query($con, $query);
        mysqli_close($con);
    }
    
    // Send message in context of room to occupants of $room_id
    function send_message_to_room($room_id, $text) {
        $text = "(Room) $text";
        $type = "text";
        
        require "config/sql_connect.php";
        
        // Get online adventurers in room
        $query = "SELECT adventurers.id FROM adventurers JOIN rooms ON adventurers.x = rooms.x AND adventurers.y = rooms.y AND adventurers.z = rooms.z WHERE session_id IS NOT NULL AND rooms.id=$room_id";
        trace($query, "send_message_to_room query 1");
        $result = mysqli_query($con, $query);
        
        // Map to value blocks
        $rows = array();
        while($row = mysqli_fetch_assoc($result)) {
            array_push($rows, $row);
        }
        $values = array_map(function($row) use (&$text, &$type) {
            $id = $row["id"];
            return "('$type', '$text', $id)";
        }, $rows);
        $values = implode(", ", $values);
        
        // Insert value blocks
        $query = "INSERT INTO message_queue (type, text, adventurer_id) VALUES $values";
        mysqli_query($con, $query);
        mysqli_close($con);
    }
    
    // Send message in context of room to given adventurer $id
    function send_message_room($id, $text,$exclude_sender=false) {
        $text = "(Room) $text";
        $type = "text";
        //echo json_encode(array("success"=>false, "errors"=>array($text)));
        
        require "config/sql_connect.php";
        
        // Get room x,y,z
        $query = "SELECT x,y,z FROM adventurers WHERE id=$id";
        $result = mysqli_query($con, $query);
        $row = mysqli_fetch_row($result);
        $x = $row[0];
        $y = $row[1];
        $z = $row[2];
        
        // Get online adventurers in room
        if ($exclude_sender)
            $query = "SELECT adventurers.id FROM adventurers JOIN rooms ON adventurers.x = rooms.x AND adventurers.y = rooms.y AND adventurers.z = rooms.z WHERE session_id IS NOT NULL AND rooms.x=$x AND rooms.y=$y AND rooms.z=$z AND adventurers.id!=$id";
        else
            $query = "SELECT adventurers.id FROM adventurers JOIN rooms ON adventurers.x = rooms.x AND adventurers.y = rooms.y AND adventurers.z = rooms.z WHERE session_id IS NOT NULL AND rooms.x=$x AND rooms.y=$y AND rooms.z=$z";
        $result = mysqli_query($con, $query);
        
        // Map to value blocks
        $rows = array();
        while($row = mysqli_fetch_assoc($result)) {
            array_push($rows, $row);
        }
        $values = array_map(function($row) use (&$text, &$type) {
            $id = $row["id"];
            return "('$type', '$text', $id)";
        }, $rows);
        $values = implode(", ", $values);
        
        // Insert value blocks
        $query = "INSERT INTO message_queue (type, text, adventurer_id) VALUES $values";
        mysqli_query($con, $query);
        mysqli_close($con);
    }
    
    // Send message in context of global
    function send_message_global($text) {
        $text = "(Global) $text";
        $type = "text";
        
        require "config/sql_connect.php";
        
        // get all online adventurers
        $query = "SELECT id FROM adventurers WHERE session_id IS NOT NULL";
        $result = mysqli_query($con, $query);
        
        // map to value blocks
        $rows = array();
        while($row = mysqli_fetch_assoc($result)) {
            array_push($rows, $row);
        }
        $values = array_map(function($row) use (&$text, &$type) {
            $id = $row["id"];
            return "('$type', '$text', $id)";
        }, $rows);
        $values = implode(", ", $values);
        
        $query = "INSERT INTO message_queue (type, text, adventurer_id) VALUES $values";
        mysqli_query($con, $query);
        mysqli_close($con);
    }
    
    // Send message in context of private to given aventurer $id
    function send_message($id, $text) {
        $text = "(Private) $text";
        $type = "text";
        
        require "config/sql_connect.php";
        $query = "INSERT INTO message_queue (type, text, adventurer_id) VALUES ('$type', '$text', $id)";
        mysqli_query($con, $query);
        mysqli_close($con);
    }
    
    function send_message_world($id, $text) {
        $text = "(World) $text";
        $type = "text";
        
        require "config/sql_connect.php";
        $query = "INSERT INTO message_queue (type, text, adventurer_id) VALUES ('$type', '$text', $id)";
        mysqli_query($con, $query);
        mysqli_close($con);
    }
    
    function send_message_system($id, $text) {
        $text = "(System) $text";
        $type = "text";
        
        require "config/sql_connect.php";
        $query = "INSERT INTO message_queue (type, text, adventurer_id) VALUES ('$type', '$text', $id)";
        mysqli_query($con, $query);
        mysqli_close($con);
    }

?>
