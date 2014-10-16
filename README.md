generic_mud
===========

Project Description
-------------------

This is a programming challenge created by Desmond Gee.

Users login with a name and are able to chat with other users in the same room.

The rooms are pre-generated with random descriptions and connection paths on a 3D grid.

Users can traverse between rooms using "north" "south" "east" "west" "up" and "down" commands.

Uses can also communicate using "say" "tell" and "yell" commands.

It is built on LAMP(Linux, Apache, MySQL, PHP).
  

Database Queries
----------------

This game uses MySQL and requires this database and these tables to be created.

    create database generic_mud;
    use generic_mud;

    CREATE TABLE worlds (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      name CHAR(12) NOT NULL,
      
      root_x INT,
      root_y INT,
      root_z INT,
      
      PRIMARY KEY (id)
    );

    CREATE TABLE rooms (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      world_id INT UNSIGNED NOT NULL,
      x INT NOT NULL,
      y INT NOT NULL,
      z INT NOT NULL,
      description TEXT NOT NULL,
      phase_state CHAR(12) NOT NULL,
      PRIMARY KEY (id),
      FOREIGN KEY (world_id) REFERENCES worlds(id)
    );

    CREATE TABLE adventurers (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      world_id INT UNSIGNED,
      x INT,
      y INT,
      z INT,
      
      name CHAR(12) NOT NULL UNIQUE,
      owner_type CHAR(12),
      session_id CHAR(26),  // maybe 40 to be safe in case using different hashing.
      
      last_ping_time TIMESTAMP,
      last_action_time TIMESTAMP,
      
      PRIMARY KEY (id),
      FOREIGN KEY (room_id) REFERENCES rooms (id)
    );


    CREATE TABLE message_queue (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        adventurer_id BIGINT UNSIGNED NOT NULL,
        text TEXT NOT NULL,
        type CHAR(12) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        
        PRIMARY KEY (id)
    );


World Generation
----------------

In addition to databases, some default rooms need to be created.  queries/generate_rooms.php exists for auto-generating random rooms.  You can visit that url to create your original rooms.  Since there is no admin permission infrastructure to prevent anybody from visiting this url, it is advised to remove this file from the server after it has done its job.

The room generation algorith works as follows:

1. Create a room at (0,0). This will be the starting point.
2. Randomize number of branches from current room that it will "try" to create.
3. When choosing directions, cardinal directions have a weight of 4 while up and down have a weight of 1. Select random directions with these weight biases for each room to be created.  If a room exists in that direction, skip creation for that direction.  Otherwise, create a room in that direction and put that room on the iteration queue.
4. If # of rooms parameter has been satisfied, complete world generation.  Otherwise, go back to step 3 utilizing the next room on the iteration queue.  If no other rooms are on the queue, use a random previously created room instead.

The algorithm temporarily stores the above in a Lazy3DArray(located in util/lazy3darray.php) structure.  This structure allows you to set and unset(delete) for various (x,y,z) coordinates. It also allows you to generate a list of coordinates when done to iterate over.

The Lazy3DArray is then iterated upon to generate rooms at each (x,y,z) coordinate. There is also a random description generator inside queries/generate_rooms.php which applies a random description.


Game Frontend Structure
-----------------------

The frontend uses the following files:

* index.php
* game.php
* assets/game.js
* assets/game.css 

index.php just forwards to game.php.  game.php is the main html rendering file.  assets/game.js holds the javascript for game.php.  assets/game.css holds the css for game.php.


Game Backend Structure
----------------------

The game has two primary access files which the client sends AJAX requests to to inform and receive information to/from the server.  These files are:

* remote.php and
* send.php

remote.php is pinged by the client every 250ms. This value can be changed in the game.js file where it does setInterval with an ajax call.  The response from remote.php returns a lot of useful information including number of users online, current room occupants, login state, and queued game log messages.

send.php is used by the client to send commands you type in to the server.  This file chops up and sanitizes the string input and redirects it to various command functions.

Some other important back-end files are:

* config/sql_connect.php
* config/memcached.php
* queries/messaging.php
* util/trace.php

config/sql_connect.php contains the database and password information which is shared by all database requests. It creates a $con variable for use in the current scope.
config/memcached.php contains the server list being used for caching variables which is shared by all memcached actions. This creates a $m variable for use in the current scope.
queries/messaging.php contains a variety of functions used to send system / global / room / world / private messages to the game log of one or multiple logged in users.
util/trace.php contains the trace() function which is great for printing out a variety of formatted debug information.
  

How The Login System Works
--------------------------

1. The client submits a name to the server
2. The client checks if the name exists. If not, it creates a new adventurer. If that adventurer already exists and is not logged in, the user is logged in as the existing adventurer. Otherwise it gives an error back in JSON format to the browser.
3. The server associates a session with each user and will set their session_id() to the adventurer's session_id field.  A non-null value in that field is used to identify if that adventurer is in use.
4. When the adventurer logs out or timesout, the session_id is simply set to null.
5. Login state information is relayed to the client through the remote.php file which is pinged every X milliseconds. Through this ping, the cilent will automatically remove the login screen or kick the user back out to the login screen.


Clearing Timed Out Adventurers
----------------------------

The server is pinged by the client every X milliseconds to remote.php. If that user is logged in as an adventurer, it refreshes the last_ping_time field on that adventurer.

During those ping requests, the server has a memcached last_timeout_check_time variable with which it compares to the current time.  If X seconds have elapsed since the last purge, a new purge begins.  An atomic add with the timeout_check_in_progress variable is done on memcached to prevent multiple timeout checks from occuring at the same time(not consequential, but saves a few cycles).  Adventurers who have not pinged for a while are logged out.

How The Messaging System Works
------------------------------

The mud ensures messages are sent in correct order as interpreted by the server. It does this by instead of directly putting inputs from the user into the game log, and instead of putting the result of the input in the ajax response for that input, this mud plugs the message data in order as interpreted by the server into a message_queue table.

The server is pinged every X milliseconds by the clients in a unified update request. If the client's session matches a logged in adventurer, the system pulls and purges from the message_queue table all messages for that adventurer and sends the back to the client as part of the response to the unified update request.

