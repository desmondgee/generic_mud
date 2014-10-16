<?php

    require_once 'util/trace.php';

    session_start();

    //==================================================================
    // Message And State Syncronization
    //
    // A url that is designed to be polled every 250-500ms. It keeps the
    // client informed and update with new messages and info about the
    // current state of the world and current room.
    //		
    // Usage: POST to this url.
    //
    // Return: JSON with various update parameters for the client.
    //
    //------------------------------------------------------------------
    
    require 'config/sql_connect.php';
    
	if (mysqli_connect_errno()) {
		echo json_encode(array("success"=>false,"errors"=>array("Failed to connect to MySQL: " . mysqli_connect_error())));
        return;
	}
    
    require 'config/memcached.php';
    
    // there is a case where if you close the tab and wait a few hours, and if nobody logged in
    // during that time, you you open the tab again and you effectively never logged out.  But..
    // thats a "if a tree falls down in the woods and nobody is around, does it make a sound?" 
    // type of scenario in which this case i'm willing to say no.
    $id = $_SESSION["adventurer_id"];
    
    // allow only integers since i use this for queries.
    if (!ctype_digit(strval($id))) {
        $id = null;
    }
    
    if ($id) {
        mysqli_query($con, "UPDATE adventurers SET last_ping_time=now() WHERE id=$id AND session_id IS NOT NULL LIMIT 1");
    }
    
    // setting for minimal time between periodic batch timeout checks
    $timeout_check_seconds = 10;
    
    // setting for minimal time for timeout
    $timeout_seconds = 20;
    
    // Perform logout purges if one hasn't been done for a while.
    if ($m->add('last_timeout_check_time', time()) || $m->get('last_timeout_check_time') < strtotime("-$timeout_check_seconds seconds")) {
        // Atomic add to prevent multiple running at same time.
        if ($m->add('timeout_check_in_progress', true)) {
            //Purge users that have not pinged for while.
            $timeout = strtotime("-$timeout_seconds seconds");            
            $query = "UPDATE adventurers SET session_id = NULL WHERE session_id IS NOT NULL AND last_ping_time < FROM_UNIXTIME($timeout)";
            mysqli_query($con, $query);
            $m->set('last_timeout_check_time', time());
            $m->delete('timeout_check_in_progress');
        }
    }
    
    // Count online users.
    // Could track in memcache instead.
    $result = mysqli_query($con, "SELECT COUNT(session_id) FROM adventurers");
    $row = mysqli_fetch_array($result);
    $online = $row[0];
    
    // Count registered users.
    // Could track in memcache instead.
    $result = mysqli_query($con, "SELECT COUNT(*) FROM adventurers");
    $row = mysqli_fetch_array($result);
    $registered = $row[0];
    
    // Get name, x, y, z. Presence of name determines if user is logged in or not.
    $result = mysqli_query($con, "SELECT name,x,y,z FROM adventurers WHERE id=$id AND session_id IS NOT NULL LIMIT 1");
    $row = mysqli_fetch_array($result);
    $name = ($row !== null ? $row[0] : "");
    $x = $row[1];
    $y = $row[2];
    $z = $row[3];
    
    // Grab room occupants
    //$in_room = (session_id=="" ? array() : array("Derek","Baldwin","Senkir","Malith","Valith","Miller","Helen","Warchild","Birch","Talius","Grude"));
    $result = mysqli_query($con, "SELECT name FROM adventurers WHERE session_id IS NOT NULL AND x=$x AND y=$y and z=$z");
    $in_room = array();
    while($row = mysqli_fetch_array($result)) {
        array_push($in_room, $row[0]);
    }
    
    // Get messages.
    // It is okay to lose all queued messages when server goes down, so could also store these in memcache.
    $result = mysqli_query($con, "SELECT id, adventurer_id, type, text FROM message_queue WHERE adventurer_id=$id");
    $messages = array(array("text"=>"..pong..","type"=>"pong"));
    while($row = mysqli_fetch_assoc($result)) {
        array_push($messages, array("type"=>$row["type"], "text"=>$row["text"]));
        $msgid = $row["id"];
        
        // delete individually so messages added after select are not lost.
        mysqli_query($con, "DELETE FROM message_queue WHERE id=$msgid LIMIT 1");
    }
    
    mysqli_close($con);
    
    $response = array(
        "online"=>$online, 
        "registered"=>$registered, 
        "name"=>$name, 
        "in_room"=>$in_room,
        "adventurer_id"=>$id,
        "test"=>$_SESSION["test"],
        "session_id"=>session_id(),
        "messages"=>$messages
    );
    
    echo json_encode($response);
    

?>
