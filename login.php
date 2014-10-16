<?php

    require_once 'util/trace.php';

    session_start();

    if ($_POST["name"])
        login($_POST["name"]);
    else
        login($_GET["name"]);

    //==================================================================
    // Login Processor
    //        
    // Usage: POST to this url with a 'name' parameter to login as an 
    //       Adventurer with that name. Automatically creates that 
    //    adventurer if one with that name doesn't already exist.
    //
    // Return: JSON format
    //    {success:true}  // on success
    //    {success:false, errors:["an error", "another error"]}  // on failure
    //
    //------------------------------------------------------------------

    function generate_key($length) {
        $choices = '0123456789abcdefghijklmnopqrstuvwxyz';
        $len = strlen($choices);
        $result = '';
        while ($length-- > 0) {
            $result .= $choices[rand(0,$len-1)];
        }
        return $result;
    }

    function render_error($msg) {
        echo json_encode(array('errors' => array($msg), 'success' => false));
    }
    
    function render_success($adventurer_id) {
        $_SESSION["adventurer_id"] = $adventurer_id;
        echo json_encode(array("success"=>true));
    }

    // wrapped in function just so we can early escape via returns.
    function login($name) {
        
        $con = mysqli_connect("localhost","root","easygo123","generic_mud");
        
        if (mysqli_connect_errno()) {
            render_error("Failed to connect to MySQL: " . mysqli_connect_error());
            mysqli_close($con);
            return;
        }
        
        if (!$name) {
            // error. no name supplied
            render_error('Name cannot be blank.');
            mysqli_close($con);
            return;
        }
        
        if (strlen($name) < 3 || strlen($name) > 12) {
            // error. no name supplied
            render_error('Name must be between 3 and 12 characters long.');
            mysqli_close($con);
            return;
        }
        
        if (preg_match("/[^0-9a-zA-Z]/", $name)) {
            // error. name cannot contain spaces and non-english alphanumeric characters.
            render_error('Name cannot contain spaces and/or non-alphanumeric characters.');
            mysqli_close($con);
            return;
        }
        
        // escaped name
        $ename = mysqli_real_escape_string($con, $name);
        
        if ($ename != $name) {
            // error. name cannot contain spaces and non-english alphanumeric characters.
            render_error('Name cannot contain spaces and/or non-alphanumeric characters!');
            mysqli_close($con);
            return;
        }
        
        // lock row 
        mysqli_query($con, "TRANSACTION START");
        $query = "SELECT id, name, session_id, owner_type FROM adventurers WHERE name='$ename' LIMIT 1 FOR UPDATE";
        $result = mysqli_query($con, $query);
        $adv = mysqli_fetch_assoc($result);
        $sid = session_id();
        
        // CASE 1: adventurer exists
        if ($adv) {
            $aid = $adv["id"];
            
            // CASE 1A: already in-game
            if ($adv["session_id"] != null) {
                render_error('That adventurer is already logged in.');
                mysqli_query($con, "COMMIT");
                mysqli_close($con);
                return;
            }
            
            // CASE 1B: not in-game
            render_success($aid);
            // also update name in case you want different casing.
            $query = "UPDATE adventurers SET name='$ename', session_id='$sid', last_ping_time=now() WHERE id=$aid LIMIT 1";
            mysqli_query($con, $query);
            mysqli_query("COMMIT");
            mysqli_close($con);
        }
        
        // CASE 2: adventurer doesn't exist
        else {
            // CASE 2A: successful insert
            $insert = mysqli_query($con, "INSERT INTO adventurers (name, owner_type, session_id, x, y, z) VALUES ('$name', 'user', '$sid', 0, 0, 0)");
            if ($insert) {
                $aid = mysqli_insert_id($con);
                render_success($aid);
                mysqli_query("COMMIT");
                mysqli_close($con);
                return;
            }
            
            // CASE 2B: conflicting name. rare case where two users make adventurer with same name.
            render_error('This adventurer is already logged in!');
            mysqli_query("COMMIT");
            mysqli_close($con);
            return;
            
        }
        
        mysqli_query("COMMIT");
        mysqli_close($con);
    }


?>
