<?php

class Debug
{
    public static function log ($message, $label = ""){
        //return if there is no "DEBUG" in the get parameters
        if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], 'RELOAD_ON_SAVE') === false){
            return;
        }


        if (!empty($label)){
            $label = "[$label] ";
        }
        $timestamp = date('Y-m-d H:i:s');
        $messageWithTimestamp = "<pre>[$timestamp]$label $message</pre>";
        echo $messageWithTimestamp . PHP_EOL;
    }

}