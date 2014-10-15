<?php

    // Use require_once when including this on pages. It may be re-used in
    // various other packages in global scope and does not benefit from
    // being redefined.

    // Lazy3DArray class by Desmond Gee
    //
    // A simple class that lets you use (x,y,z) as keys for data.
    //
    // All field default as null.
    class Lazy3DArray {

        public $data = array();
        private $_count = 0;
        
        public function count() {
            return $this->_count;
        }

        // Gets the value that was set at the given $x and $y coordinates.
        // Returns null if that value does not exist(or if its null).
        public function get($x,$y,$z) {
            if (array_key_exists($x, $this->data)) {
                if (array_key_exists($y, $this->data[$x])) {
                    return $this->data[$x][$y][$z];
                }
            }
            return null;
        }

        // Sets the value at given $x, $y and $z coordinates.
        public function set($x,$y,$z,$value=true) {
            if ($value === null) {
                $this->delete($x,$y,$z);
                return;
            }
            
            if (!array_key_exists($x, $this->data)) {
                $this->data[$x] = array();
            }
            if (!array_key_exists($y, $this->data[$x])) {
                $this->data[$x][$y] = array();
            }
          
            $this->data[$x][$y][$z] = $value;
            $this->_count++;
            // trace("x=$x, y=$y, z=$z, value=$value, result=" . $this->data[$x][$y][$z]);
        }
        
        // Check if coord is set and is not null.
        public function has($x,$y,$z) {
            return ($this->get($x,$y,$z) !== null);
        }

        // Deletes the value at the given $x, $y and $z coordinates.
        public function delete($x,$y,$z) {
            if (array_key_exists($x, $this->data)) {
                if (array_key_exists($y, $this->data[$x])) {
                    if (array_key_exists($z, $this->data[$x][$y])) {
                        unset($this->data[$x][$y][$z]);
                        $this->_count--;
                    }
                }
            }
        }
        
        // Returns an array($x,$y,$z) of a random set point.
        public function random_key() {
            $target = rand(1,$this->count());
            $tally = 0;
          
            foreach ($this->data as $x => $ys) {
                if (!$ys) 
                    continue;
                foreach ($this->data[$x] as $y => $zs) {
                    if (!$zs) 
                        continue;
                    foreach ($this->data[$x][$y] as $z => $value) {
                        if ($value !== null) {
                            ++$tally;
                            if ($tally == $target) 
                                return array($x,$y,$z);
                        }
                    }
                }
            }
        }
        
        public function keys() {
            
            $keys = array();
            
            foreach ($this->data as $x => $ys) {
                if (!$ys) 
                    continue;
                foreach ($this->data[$x] as $y => $zs) {
                    if (!$zs) 
                        continue;
                    foreach ($this->data[$x][$y] as $z => $value) {
                        if ($value !== null) {
                            array_push($keys, array($x,$y,$z));
                        }
                    }
                }
            }
            
            return $keys;
            
        }
    }
    
?>
