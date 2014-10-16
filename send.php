<?php

    include_once "util/trace.php";
    include_once "queries/messaging.php";
    include_once "queries/navigation.php";

    session_start();

    //==================================================================
    // Command Processor
    //        
    // Usage: POST to this url with a 'message' parameter. This page will 
    //     validate the syntax and permissions of the message.  If it passes, 
    //     the message will perform its commands and pushes updates to 
    //      relevant adventurer message queues.
    //
    // Return: void. All messages(including errors) are appended to the
    //     adventurer's message queue where they'll be pull in a
    //     separate call on the user's next regular update request.
    //     Doing this prevents messages from being out of order.
    //
    //------------------------------------------------------------------
    
    //=========== Define Commands
    
    // $id is the user's adventurer id.
    // $data is an array of remaining words with excess space removed.
    // returns false if an error occured.
    $commands = array(
        "help" => function($id, $data) {
            $msg = str_replace("'", "\\'", "Available commands are: \n\nhelp, look, say [msg], yell [msg], tell [name] [msg], north, south, west, east, up, down\n\n");
            send_message_system($id, $msg);
        },
        "say" => function($id, $data) {
            $adv = get_adventurer($id);
            
            $text = implode(" ", $data);
            $text = $adv["name"] . ' says "' . $text . '"';
            send_message_room($id, $text);
        },
        
        "yell" => function($id, $data) {
            $adv = get_adventurer($id);
            
            $text = implode(" ", $data);
            $text = $adv["name"] . ' yells "' . strtoupper($text) . '!"';
            
            send_message_global($text);
        },
        
        "tell" => function($id, $data) {
            if (count($data) == 0) {
                send_error($id, "You must specify a person to tell to.");
                return;
            }
            if (count($data) == 1) {
                send_error($id, "You must specify a message to tell.");
                return;
            }
          
            // validate other person.
            $receiver = get_adventurer_by_name($data[0], "id, name");
            $sender = get_adventurer($id, "id, name");
          
            if (!$receiver) {
                send_error($id, $data[0] . " is not online.");
                return;
            }
          
            $data = array_slice($data, 1);
            $text = '"' . implode(" ", $data) . '"';
            $sender_text = "You tell " . $receiver["name"] . ', ' . $text;
            $receiver_text = $sender["name"] . " tells you, " . $text;
            send_message($sender["id"], $sender_text);
            send_message($receiver["id"], $receiver_text);
        },
        
        "look" => function($id, $data) {
            look($id);
        },
        
        "north" => function($id, $data) {
            check_and_move_dir($id, "north");
        },
        "south" => function($id, $data) {
            check_and_move_dir($id, "south");
        },
        "east" => function($id, $data) {
            check_and_move_dir($id, "east");
        },
        "west" => function($id, $data) {
            check_and_move_dir($id, "west");
        },
        "up" => function($id, $data) {
            check_and_move_dir($id, "up");
        },
        "down" => function($id, $data) {
            check_and_move_dir($id, "down");
        },
        
        "map" => function($id, $data) {
            $a = get_adventurer($id, "x,y,z");
            $x = $a["x"];
            $y = $a["y"];
            $z = $a["z"];
            send_message_world($id, "You are at ($x, $y, $z).");
        },
        
        "teleport" => function($id, $data) {
            $x = intval($data[0]);
            $y = intval($data[1]);
            $z = intval($data[2]);
            
            require "config/sql_connect.php";
            mysqli_query($con, "UPDATE adventurers SET x=$x,y=$y,z=$z WHERE id=$id LIMIT 1");
            send_message_world($id, "You teleported to ($x, $y, $z).");
            look($id);
        }
        
    );
    
    //=========== Process Input

    $id = $_SESSION["adventurer_id"];
    
    // prevent bad input
    $id = intval($id);
    
    if ($_POST["message"])
        process_input($id, $_POST["message"], $commands);
    else
        process_input($id, $_GET["message"], $commands);

    function process_input($id, $input, $commands) {
        // Remove excess spaces and split into words.
        $words = explode(" ", reduce_spaces($input));

        // Blank input
        if (count($words) == 1 && $words[0] == "") {
            send_error($id, "Input was blank.");
            return;
        }
        
        // Separate command word with rest of words
        $command_word = $words[0];
        $data = array_slice($words, 1);

        if (array_key_exists($command_word, $commands)) {
            $commands[$command_word]($id, $data);
            //~ render_success();
        }
        else {
            //echo json_encode(array("success"=>false, "errors"=>array($input . " command not supported yet..")));
            render_error("$command_word command is not supported");
        }
    }
    
    
    //=========== Render Functions
    
    // Writes an error message onto the page.
    // Useful when user is not logged in as a valid adventurer, or if kicking out the user.
    function render_error($text) {
        $text = "(System Error: " . $text . ")";
        echo json_encode(array("success"=>false, "errors"=>array($text)));
    }
    
    function render_success() {
        echo json_encode(array("success"=>true));
    }
    

    
    
    //=========== Queries
    
    function get_adventurer($id, $columns="*") {
        require "config/sql_connect.php";
        $query = "SELECT $columns FROM adventurers WHERE id='$id' AND session_id IS NOT NULL LIMIT 1";
        trace($query);
        $results = mysqli_query($con, $query);
        $row = mysqli_fetch_assoc($results);
        mysqli_close($con);
        return ($row ? $row : null);
    }
    
    function get_adventurer_by_name($name, $columns="*") {
        require "config/sql_connect.php";
        $results = mysqli_query($con, "SELECT $columns FROM adventurers WHERE name='$name' AND session_id IS NOT NULL LIMIT 1");
        $row = mysqli_fetch_assoc($results);
        mysqli_close($con);
        return ($row ? $row : null);
    }
    
    function get_world_for_adventurer($id) {
        require 'config/sql_connect.php';
        $query = "SELECT worlds.* FROM worlds INNER JOIN adventurers ON worlds.id=adventurers.world_id WHERE adventurers.id=$id LIMIT 1";
        $result = mysqli_query($con, $query);
        $row = mysqli_fetch_assoc($result);
        mysqli_close($con);
        return $row;
    }

    // ignoring world_id for now. assuming there are only rooms built for a single world.
    function get_room_for_adventurer($id) {
        
        $a = get_adventurer($id, "x,y,z");
        require 'config/sql_connect.php';
        if ($a) {
            $x = $a["x"];
            $y = $a["y"];
            $z = $a["z"];
            $query = "SELECT * FROM rooms WHERE x=$x AND y=$y AND z=$z LIMIT 1";
            trace($query, "get room query");
            $result = mysqli_query($con, $query);
            return mysqli_fetch_assoc($result);
        }
        else {
            return null;
        }
    }
    
    function check_room ($x,$y,$z) {
        require 'config/sql_connect.php';
        $query = "SELECT id FROM rooms WHERE x=$x AND y=$y AND z=$z LIMIT 1";
        trace($query);
        $result = mysqli_query($con, $query);
        $response = (mysqli_fetch_row($result) ? true : false);
        mysqli_close($con);
        return $response;
    }
    
    // manually use check_room before moving to room.
    function move_to_room ($aid, $x,$y,$z) {
        require 'config/sql_connect.php';
        $query = "UPDATE adventurers SET x=$x, y=$y, z=$z WHERE id=$aid LIMIT 1";
        $result = mysqli_query($con, $query);
        mysqli_close($con);
    }
    
    function check_and_move_to_room($aid, $x, $y, $z) {
        if (check_room($x,$y,$z)) {
            move_to_room($aid,$x,$y,$z);
            return true;
        }
        return false;
    }
    
    function check_and_move_dir($id, $dir) {
        trace($id, $dir);
        $a = get_adventurer($id, "x,y,z,name");
        $x = $a["x"];
        $y = $a["y"];
        $z = $a["z"];
        $name = $a["name"];
        
        $room = get_room_for_adventurer($id);
        $text = "There is no path leading $dir.";
        
        if ($dir == "north") { check_and_move_to_room($id,$x,$y+1,$z) ? move_and_look($id,$dir,$name,$room) : send_message_world($id,$text); }
        elseif ($dir == "south") { check_and_move_to_room($id,$x,$y-1,$z) ? move_and_look($id,$dir,$name,$room) : send_message_world($id,$text); }
        elseif ($dir == "east") { check_and_move_to_room($id,$x+1,$y,$z) ? move_and_look($id,$dir,$name,$room) : send_message_world($id,$text); }
        elseif ($dir == "west") { check_and_move_to_room($id,$x-1,$y,$z) ? move_and_look($id,$dir,$name,$room) : send_message_world($id,$text); }
        elseif ($dir == "up") { check_and_move_to_room($id,$x,$y,$z+1) ? move_and_look($id,$dir,$name,$room) : send_message_world($id,$text); }
        elseif ($dir == "down") { check_and_move_to_room($id,$x,$y,$z-1) ? move_and_look($id,$dir,$name,$room) : send_message_world($id,$text); }
        
        trace("done");
    }
    
    function get_room_navigations($x,$y,$z) {
        $found = array();
        trace($x);
        trace($y);
        trace($z);
        
        if (check_room($x,$y+1,$z)) { array_push($found, "north"); }
        if (check_room($x,$y-1,$z)) { array_push($found, "south"); }
        if (check_room($x+1,$y,$z)) { array_push($found, "east"); }
        if (check_room($x-1,$y,$z)) { array_push($found, "west"); }
        if (check_room($x,$y,$z+1)) { array_push($found, "up"); }
        if (check_room($x,$y,$z-1)) { array_push($found, "down"); }
        
        $str = "There are paths leading ";
        $len = count($found);
        trace($len,"len");
        if ($len == 1) {
            return "There is a path leading " . $found[0] . ".";
        }
        
        foreach ($found as $index => $value) {
            if ($index == $len-1) {
                $str .= " and $value";
                continue;
            }
            if ($index != 0) {
                $str .= ", $value";
                continue;
            }
            $str .= $value;
        }
        
        return $str . ".";
    }

    function look($id) {
        trace($id, "look");
        $room = get_room_for_adventurer($id);
        trace($room, "room");
        $nav = get_room_navigations($room["x"],$room["y"],$room["z"]);
        trace($nav, "nav");
        
        if (strlen($nav) > 1) {
            $text = $room["description"] . "  $nav";
        }
        else {
            $text = $room["description"];
        }
        trace($text);
        send_message_world($id, $text);
    }
    
    function move_and_look($id, $dir, $name, $prev_room) {
        send_message_world($id, "You travel $dir" . "ward.");
        look($id);
        
        $other_dir = opposite_direction($dir);
        send_message_room($id, "$name has arrived from the $other_dir direction.", true);
        send_message_to_room($prev_room["id"], "$name has left the room travelling $dir" . "ward.");
    }
    
    
    //=========== Utilities
    
    function opposite_direction($dir) {
        switch($dir) {
            case "north": return "south";
            case "south": return "north";
            case "west": return "east";
            case "east": return "west";
            case "up": return "down";
            case "down": return "up";
        }
    }
    
    // removes leading and trailing spaces
    // reduces multi-spaces to single spaces
    function reduce_spaces($str) {
        $str = trim($str);
        $str = preg_replace('!\s+!', ' ', $str);
        return $str;
    }

?>
