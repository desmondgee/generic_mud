<?php

    // Use require_once when including this on pages. It may be re-used in
    // various other packages in global scope and does not benefit from
    // being redefined.

    // trace function by Desmond Gee
    //
    // Allows you to print out a data structure in html.
    // 
    // Formatted to include line breaks and an optional label.
    //
    function trace($msg="", $label="") {
        echo "<p>";
        if (strlen($label) > 0) {
            echo "<b>$label: </b>";
        }
        var_dump($msg);
        echo "</p>";
    }
    
    
?>
