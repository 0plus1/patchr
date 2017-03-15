<?php

namespace Zeroplusone\Patchr\Helpers;

/**
 * Class CLI
 * @package Zeroplusone\Patchr\Helpers
 * @copyright 2014-2017 Davide Gaido
 * TODO: Deprecate in favor of symfony console
 */
class CLI
{
    /**
     * Log message to the command line
     *
     * @param type $type
     * @param type $message
     * @param type $method
     * @param type $state
     * @return string
     * @throws Exception
     */
    public static function writeMessageToConsole($type, $message = NULL, $method = NULL, $state = NULL) {

        if (php_sapi_name() != 'cli')
        {
            return $message;
        }

        switch ($type)
        {
            case 'SUCCESS':
                $color = "[42m"; //Green background
                break;
            case 'ERROR':
                $color = "[41m"; //Red background
                break;
            case 'WARNING':
                $color = "[43m"; //Yellow background
                break;
            case 'INFO':
                $color = "[44m"; //Blue background
                break;
            default:
                throw new Exception("Invalid status: " . $type);
        }
        $string = chr(27) . $color;

        if ( !is_null($method) )
        {
            $string .= $method . ':';
        }
        if ( !is_null($state) )
        {
            $string .= $state . '-';
        }
        if ( !is_null($message) )
        {
            $string .= $message. '.';
        }
        $string .= chr(27) . '[0m' . PHP_EOL;
        echo $string;
        //Flush buffers
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

        return $message;
    }

}