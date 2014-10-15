<?php

    // Use require_once when including this on pages. It may be re-used in
    // various other packages in global scope and does not benefit from
    // being redefined.

    // Point3D class by Desmond Gee
    //
    // A simple class that just stores x, y and z coordinates.
    //
    class Point3D {
        public $x;
        public $y;
        public $z;
        
        public function __construct($x=0,$y=0,$z=0) {
            $this->x = $x;
            $this->y = $y;
            $this->z = $z;
        }
        
        public function offset($other) {
            $this->x += $other->x;
            $this->y += $other->y;
            $this->z += $other->z;
            return $this;
        }
    }
    
?>
