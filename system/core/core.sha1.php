<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010 EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: core.sha1.php
-----------------------------------------------------
 Purpose: Provides 160 bit password encryption using 
 The Secure Hash Algorithm developed at the
 National Institute of Standards and Technology
 
 The 40 character SHA1 message hash is computationally 
 infeasible to crack.
 
 This class is a fallback for servers that are not running 
 PHP version 4.3, or do not have the MHASH library.
 
 This class is based on two scripts:
  
 Marcus Campbell's PHP implementation (GNU license) 
 http://www.tecknik.net/sha-1/
 
 ...which is based on Paul Johnston's JavaScript version 
 (BSD license). http://pajhome.org.uk/
 
 I encapsulated the functions and wrote one 
 additional method to fix a hex conversion bug. 
 - Rick Ellis
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class SHA {


    /** -------------------------------------
    /**  Constructor
    /** -------------------------------------*/

    function SHA() 
    {
    }
    
    /** -------------------------------------
    /**  Generate the hash
    /** -------------------------------------*/

    function encode_hash($str) 
    {
        $n = ((strlen($str) + 8) >> 6) + 1;
        
        for ($i = 0; $i < $n * 16; $i++)
        {
            $x[$i] = 0;
        }
        
        for ($i = 0; $i < strlen($str); $i++)
        {
            $x[$i >> 2] |= ord(substr($str, $i, 1)) << (24 - ($i % 4) * 8);
        }
        
        $x[$i >> 2] |= 0x80 << (24 - ($i % 4) * 8);
        
        $x[$n * 16 - 1] = strlen($str) * 8;
        
        $a =  1732584193;
        $b = -271733879;
        $c = -1732584194;
        $d =  271733878;
        $e = -1009589776;
        
        for ($i = 0; $i < sizeof($x); $i += 16) 
        {
            $olda = $a;
            $oldb = $b;
            $oldc = $c;
            $oldd = $d;
            $olde = $e;
            
            for($j = 0; $j < 80; $j++) 
            {
                if ($j < 16)
                {
                    $w[$j] = $x[$i + $j];
                }
                else
                {
                    $w[$j] = $this->rol($w[$j - 3] ^ $w[$j - 8] ^ $w[$j - 14] ^ $w[$j - 16], 1);
                }
                
                $t = $this->safe_add($this->safe_add($this->rol($a, 5), $this->ft($j, $b, $c, $d)), $this->safe_add($this->safe_add($e, $w[$j]), $this->kt($j)));
                
                $e = $d;
                $d = $c;
                $c = $this->rol($b, 30);
                $b = $a;
                $a = $t;
            }

            $a = $this->safe_add($a, $olda);
            $b = $this->safe_add($b, $oldb);
            $c = $this->safe_add($c, $oldc);
            $d = $this->safe_add($d, $oldd);
            $e = $this->safe_add($e, $olde);
        }
        
        return $this->hex($a).$this->hex($b).$this->hex($c).$this->hex($d).$this->hex($e);
    }
    /* END */
    
    
    /** -------------------------------------
    /**  Convert a decimal to hex
    /** -------------------------------------*/
    
    // Sometimes dechex returns a seven character string
    // rather than 8, truncating the hash length, so we'll 
    // pad the output with a zero when this happens
    
    function hex($str)
    {
        $str = dechex($str);
        
        if (strlen($str) == 7)
        {
            $str = '0'.$str;
        }
            
        return $str;
    }
    /* END */
    
    
    /** -------------------------------------
    /**  Return result based on the iteration
    /** -------------------------------------*/

    function ft($t, $b, $c, $d) 
    {
        if ($t < 20) 
            return ($b & $c) | ((~$b) & $d);
        if ($t < 40) 
            return $b ^ $c ^ $d;
        if ($t < 60) 
            return ($b & $c) | ($b & $d) | ($c & $d);
        
        return $b ^ $c ^ $d;
    }
    /* END */
    

    /** -------------------------------------
    /**  Determine the additive constant
    /** -------------------------------------*/

    function kt($t) 
    {
        if ($t < 20) 
        {
            return 1518500249;
        } 
        else if ($t < 40) 
        {
            return 1859775393;
        } 
        else if ($t < 60) 
        {
            return -1894007588;
        } 
        else 
        {
            return -899497514;
        }
    }
    /* END */


    /** -------------------------------------
    /**  Add integers, wrapping at 2^32
    /** -------------------------------------*/

    function safe_add($x, $y)
    {
        $lsw = ($x & 0xFFFF) + ($y & 0xFFFF);
        $msw = ($x >> 16) + ($y >> 16) + ($lsw >> 16);
    
        return ($msw << 16) | ($lsw & 0xFFFF);
    }
    /* END */


    /** -------------------------------------
    /**  Bitwise rotate a 32-bit number
    /** -------------------------------------*/

    function rol($num, $cnt)
    {
        return ($num << $cnt) | $this->zero_fill($num, 32 - $cnt);
    }
    /* END */


    /** -------------------------------------
    /**  Pad string with zero
    /** -------------------------------------*/

    function zero_fill($a, $b) 
    {
        $bin = decbin($a);
        
        if (strlen($bin) < $b)
        {
            $bin = 0;
        }
        else
        {
            $bin = substr($bin, 0, strlen($bin) - $b);
        }
        
        for ($i=0; $i < $b; $i++) 
        {
            $bin = "0".$bin;
        }
        
        return bindec($bin);
    }
    /* END */
}
// END CLASS    
?>