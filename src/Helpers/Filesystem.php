<?php

namespace Zeroplusone\Patchr\Helpers;

/**
 * Class Filesystem
 * @package Zeroplusone\Patchr\Helpers
 * @copyright 2014-2017 Davide Gaido
 */
class Filesystem
{

  /**
   * Create the next available patch
   *
   * @param type $latestPatchName
   * @param type $sql
   * @return string
   */
  public static function createNextAvailablePatch($patchr_config, $latestPatchName, $sql = NULL)
  {
      //Generates the next free patch name
      $generateNextPatchName = function($number, $naming)
      {
          $name = '';
          $name .= $naming['prefix'];
          $name .= str_pad( ((int)$number+1) , (int)$naming['digits'], "0", STR_PAD_LEFT);
          $name .= $naming['extension'];
          return $name;
      };
      //We are requesting the first possible patch
      if( is_null($latestPatchName) )
      {
          $generatedPatchName = $generateNextPatchName(0,$patchr_config['naming']);
      }
      else
      {
          //Extract the raw patch number from the patch name
          $patchnumber_withextension = str_replace($patchr_config['naming']['prefix'],'',$latestPatchName);
          $patchnumber_padded = str_replace($patchr_config['naming']['extension'],'',$patchnumber_withextension);
          $patchnumber = ltrim($patchnumber_padded, '0');
          //Request the next available patchname
          $generatedPatchName = $generateNextPatchName($patchnumber,$patchr_config['naming']);
      }
      //Write file to disk die('dasdacc');
      return self::createFileOnDisk($patchr_config['naming']['patches_dir'], $generatedPatchName, $sql);
  }

    /**
     * Create File
     *
     * @param type $directory
     * @param type $filename
     * @param type $contents
     * @return string
     * @throws \Exception
     */
    private static function createFileOnDisk($directory, $filename, $contents){

        if ( ! is_dir($directory))
        {
            throw new \Exception('Invalid directory ['.$directory.']');
        }
        $fileToWrite = $directory.'/'.$filename;
        //Check if there isn't already a file with the same name
        if (is_file($fileToWrite))
        {
            throw new \Exception('File already exists ['.$fileToWrite.']');
        }
        //Try to open file for writing
        $fh = fopen($directory.'/'.$filename, 'w');
        if($fh === FALSE)
        {
            throw new \Exception('Unable to create file ['.$fileToWrite.']');
        }
        fwrite($fh, $contents);
        fclose($fh);

        return $filename;
    }
}