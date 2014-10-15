<?php

    // Some essential libraries i wrote.
    require_once "../util/point3d.php";
    require_once "../util/lazy3darray.php";
    require_once "../util/trace.php";

    // Connect to SQL database (defines the $con variable).
    require "../config/sql_connect.php";

    // Make sure world exists
    $result = mysqli_query($con, "SELECT id FROM worlds LIMIT 1");
    $row = mysqli_fetch_array($result);
    if ($row) {
        $world_id = $row[0];
        // remove existing rooms so we can rebuild them.
        mysqli_query($con, "DELETE FROM rooms WHERE world_id=$world_id");
    }
    else {
        mysqli_query($con, "INSERT INTO worlds (name) VALUES ('Generic World')");
        $world_id = mysqli_insert_id($con);
    }

    // Create an array of [x,y,z] coordinates of rooms.
    $coords = generate_world(200)->keys();
    
    // Build rooms
    foreach ($coords as $coord) {
        $x = $coord[0];
        $y = $coord[1];
        $z = $coord[2];
        $desc = create_room_description();
        $query = "INSERT INTO rooms (world_id, x, y, z, phase_state, description) VALUES ($world_id, $x, $y, $z, 'transparent', '$desc')";
        mysqli_query($con, $query);
    }
    
    mysqli_close($con);
    
    // $quota is the number of rooms to create
    // Returns a Lazy3DArray representation fo the world.
    function generate_world($quota) {
        
        // map data, 3d array of room coordinates
        $map = new Lazy3DArray();
        $map->set(0,0,0);
        
        // queue of rooms for algorithm to build rooms off of.
        $queue = new SplQueue();
        $queue->enqueue(new Point3D(0,0,0));
        
        // x,y,z of current room
        $x=0;
        $y=0;
        $z=0;
        
        // possible directions for room to be built
        $directions = array(
            0 => new Point3D(0,1,0),
            1 => new Point3D(1,0,0),
            2 => new Point3D(0,-1,0),
            3 => new Point3D(-1,0,0),
            4 => new Point3D(0,0,1),
            5 => new Point3D(0,0,-1)
        );
        
        // weights for each direction
        $weights = array(0,0,0,0,1,1,1,1,2,2,2,2,3,3,3,3,4,5);
        $num_weights = count($weights);
        
        // Set random rooms around current $x, $y, $z
        $lambda = function($x, $y, $z) use (&$map, &$directions, &$weights, &$num_weights, &$queue, &$quota) {
        
            // pick up to 6 directions (note may be fewer than $num_pick since there may be duplicates)
            $num_picks = rand(1,6); 
            while ($num_picks-- > 0) {
            
                // get a random room position next to current $x, $y, $z
                $n = rand(0,$num_weights-1);
                $choice = $directions[$weights[$n]];
                
                $pos = new Point3D($x,$y,$z);
                $pos = $pos->offset($choice);
                
                // skip if already set
                if ($map->has($pos->x,$pos->y,$pos->z)) {
                    continue;
                }
                
                // set room and add to queue
                $map->set($pos->x,$pos->y,$pos->z);
                $queue->enqueue($pos);
                
                // exit early if finished
                if ($map->count() >= $quota) {
                    return;
                }
            }
        };
        
        // build new rooms until quota is filled.
        while($map->count() < $quota) {
        
            // In case we run out of rooms on the queue, grab the coordinates of a random one.
            if ($queue->count() == 0) {
                $key = $map->random_key();
                $x = $key[0];
                $y = $key[1];
                $z = $key[2];
            }
            else {
                $next = $queue->dequeue();    
                $x = $next->x;
                $y = $next->y;
                $z = $next->z;
            }
            
            $lambda($x,$y,$z);
            
            //$map->set(rand(-100,100),rand(-100,100),rand(-100,100));
               
        }
        
        return $map;
    }
    

    // Returns a randomized descript for a room.
    function create_room_description() {
    
        $materials = array(
            "paved stone",
            "rusted metal",
            "red brick",
            "sandstone",
            "white marble",
            "stained marble",
            "etched marble",
            "carved stone",
            "painted",
            "bejeweled",
            "steel",
            "wood log",
            "planked",
            "moldy old stone",
            "partially crumbled",
            "illuminated",
            "emblazoned",
            "placid",
            "towering",
            "worn down",
            "spiked",
            "fenced off",
            "blood stained",
            "cavernous",
            "reflecting",
            "beat up",
            "barricaded",
            "heavily guarded",
            "bright yellow",
            "dilapidated"
        );
        
        $qualities = array(
            "shoddy",
            "well visited",
            "run down",
            "rancid old",
            "shady looking",
            "dimly lit",
            "very large",
            "rather small",
            "eerie",
            "dangerous looking",
            "two story",
            "barricaded",
            "collapsing",
            "richly enameled",
            "horrible",
            "stone bricked",
            "rickety",
            "rich looking",
            "boarded up",
            "haunted looking"
        );
            
        $merchants = array(
            "tavern",
            "inn",
            "house",
            "weapon store",
            "armor store",
            "alchemist laboratory",
            "wizard's tower",
            "bakery",
            "casino",
            "cathedral",
            "palace",
            "ranch",
            "theater"
        );
            
        $views = array(
            "open",
            "lucious",
            "breath taking",
            "mystical",
            "inspiring",
            "shattered",
            "burnt down",
            "evil tainted",
            "light bathed",
            "dried out",
            "serene",
            "ordinary looking",
            "vast",
            "surreal",
            "luminescent",
            "dried out",
            "dark",
            "monster filled",
            "vacant",
            "ominous",
            "quiet",
            "gloomy",
            "arid",
            "stormy"
        );
            
        $places = array(
            "vista",
            "field",
            "forest",
            "plains",
            "valley",
            "farmland",
            "desert"
        );
            

        $n = rand(1,3);
        
        switch($n) {
            case 1:
                return "You are in a room with " . $materials[array_rand($materials)] . " walls.";
            case 2:
                return "You are in a " . $qualities[array_rand($qualities)] . " " . $merchants[array_rand($merchants)] . ".";
            case 3:
                return "You are in a " . $views[array_rand($views)] . " " . $places[array_rand($places)] . ".";
        }
        
    }  
    

    

    
?>
