<?php
namespace Zeroplusone\Patchr;

use Zeroplusone\Patchr\Helpers\Filesystem;
use Zeroplusone\Patchr\Model;
use Zeroplusone\Patchr\Helpers\CLI;
use \Exception;

/**
 * Class Patchr
 * @package Zeroplusone\Patchr
 * @copyright 2014-2017 Davide Gaido
 */
class Patchr extends Model
{
    protected $files = [];
    protected $config = FALSE;

    /**
     * Patchr constructor.
     * @param $config
     */
    public function __construct($config)
    {
        parent::__construct($config['db']);
        $this->config = $config;
    }
    
    /**
     * Reserve Patches
     * 
     * @param type $num
     * @return type
     * @throws Exception
     */
    public function reservePatches($num)
    {
        try
        {
            //Initialize empty output string
            $output = '';
            //Check if the requested reserved patches is indeed a number
            if(!is_numeric($num))
            {
                throw new Exception('Not a number');
            }
            $output .= 'Attempting to reserve '.$num.' patch/es';
            //Retrieve the list of files
            $output .= $this->searchDirectory($this->config['naming']['patches_dir']).PHP_EOL;
            // Assign result to variable for strict standards
            $files_list = $this->getFilesList();
            //Get latest file on disk
            $latestPatchFile = basename( end($files_list) );
            //Cycle the requested patches
            for($i = 1;$i<=$num;$i++)
            {
                $latestPatchFile = Filesystem::createNextAvailablePatch($this->config, $latestPatchFile);
                $output .= 'Created file: '.$latestPatchFile.PHP_EOL;
            }
            
            return array('output'=>$output, 'success'=>TRUE);
        }
        catch (Exception $e)
        {
           return array('output'=> NULL, 'success'=> FALSE, 'error'=> 'Aborting: '.$e->getMessage().PHP_EOL);
        }
    }
    
    /**
     * Add a new patch in the next available file
     * 
     * @param type $sql
     * @return array
     */
    public function addNewPatch($sql)
    {
        try
        {
            $output = '';
            $output .= 'Attempting to add new patch';
            $output .= $this->searchDirectory($this->config['naming']['patches_dir']).PHP_EOL;
            //Get latest file on disk
            $fileList = $this->getFilesList();
            $latestPatchFile = basename( end($fileList) );
            //Create latest 
            $output .= 'Created file: '.Filesystem::createNextAvailablePatch($this->config, $latestPatchFile, $sql).PHP_EOL;
                    
            return array('output'=>$output, 'success'=>TRUE);
        }
        catch (Exception $e)
        {
            return array('output'=> NULL, 'success'=> FALSE, 'error'=> 'Aborting: '.$e->getMessage().PHP_EOL);
        }
    }

    /**
     * Get all the unapplied patches list
     * 
     * @return array
     */
    public function getUnappliedPatches()
    {
        try
        {
            $output = '';
            $file_list = array();

            $output .= 'Getting list of unapplied patches';
            $output .= $this->searchDirectory($this->config['naming']['patches_dir']).PHP_EOL;
            
            foreach ($this->files as $file)
            {
                $result = $this->testRunFile($file);
                if( !empty($result) )
                {
                    $file_list[] = $result;
                }
                
            }

            $file_string = implode(PHP_EOL,$file_list);
            $output .= $file_string.PHP_EOL;
            $output .= PHP_EOL;
            
            return array('output'=>$output, 'success'=> TRUE, 'file_string'=>$file_string);
        }
        catch (Exception $e)
        {
            return array('output'=> NULL, 'success'=> FALSE, 'error'=> 'Aborting: '.$e->getMessage().PHP_EOL);
        }
    }

    /**
     * Return last applied patch
     * 
     * @return array
     */
    public function getLatestAppliedPatch()
    {
        try
        {
            $output = '';
            $output .= 'Reading Last Applied Patch';
            $lastApplied = $this->readLastApplied();
            if( !is_null($lastApplied['name']) )
            {
                $output .= 'Last applied patch '.$lastApplied['name']. ' @'.$lastApplied['applied_at'].PHP_EOL;
            }
            else
            {
                $output .= 'None'.PHP_EOL;
            }
            $output .= PHP_EOL;
            
            return array('output'=> $output, 'success'=> TRUE, 'last_applied' => $lastApplied);
        }
        catch (Exception $e)
        {
            return array('output'=> NULL, 'success'=> FALSE, 'error'=> 'Aborting: '.$e->getMessage().PHP_EOL);
        }
        
    }
    
    /**
     * Apply database patches to the database.
     * 
     * @return array
     */
    public function applyDatabasePatches()
    {
        $output = '';
        $file_list = array();
        $start = time();
        $output .= 'Attempting to apply patches';
        
        try
        {
            $output .= $this->searchDirectory($this->config['naming']['patches_dir']);
            
            foreach ($this->files as $file)
            {
//                $output .= '@'.basename($file); // Too verbose, investigate
                $result = $this->runFile($file);
                
                if( !empty($result) )
                {
                    $file_list[] = $result;
//                  $output .= '$result'; //Uncomment this for extreme logging
                }
                
            }

            $output .= implode('',$file_list);
            $output .= 'Complete: '. (time()-$start).'secs '.(memory_get_usage(true)/1024/1024).'Mb'.PHP_EOL;
            return array('output'=>$output, 'success'=> TRUE);
        }
        catch (Exception $e)
        {
            return array('output'=> NULL, 'success'=> FALSE, 'error'=> 'Aborting: '.$e->getMessage().PHP_EOL);
        }
    }

    /**
     * Search through the given directory recursively and look for files
     * to process. It is assumed that the files contain SQL
     * 
     * @param  string $directory
     * @return string
     * @throws \Exception
     */
    protected function searchDirectory($directory)
    {
        $output = '';
        $output .= 'Searching for patches in directory: '. $directory .'.'.PHP_EOL;

        if ( ! is_dir($directory))
        {
          throw new \Exception('Invalid directory ['.$directory.']');
        }

        $dh = opendir($directory);
        if ($dh)
        {
          closedir($dh);
          //Read with scandir
          $directory_contents = scandir($directory, 0);
          foreach($directory_contents as $file)
          {
            if (in_array($file, array('.', '..', 'index.html')))
            {
              continue;
            }

            if (is_dir($directory.DIRECTORY_SEPARATOR.$file))
            {
              $this->searchDirectory($directory.DIRECTORY_SEPARATOR.$file);
            }
            else
            {
              $this->addFile($directory.DIRECTORY_SEPARATOR.$file);
            }
          }
          
        }
        
        // ugly but consistent
        sort($this->files);
        
        $output .= 'Succesfully created patches index.'.PHP_EOL;
        return $output;
    }

    /**
     * Add filename
     * 
     * @param type $filename
     */
    protected function addFile($filename)
    {
      $this->files[] = $filename;
    }
    
    /**
     * 
     * @return type
     */
    private function getFilesList()
    {
        return $this->files;
    }
    
    /**
     * Test for unrun files in the directory
     * 
     * @param type $filename
     * @return string
     */
    private function testRunFile($filename)
    {
        //Check if we want to skip the file
        if ( in_array(basename($filename), $this->config['adv']['skip_patches']) )
        {
            return basename($filename).' [SKIP]';
        }
        //Check if file has content
        $sql = trim(file_get_contents($filename));
        if ( ! $sql)
        {
            return basename($filename).' [EMPTY]';
        }
        else
        {
            //Check if this patch has been previously applied
            $doesPatchExists = $this->readOneUsingName(basename($filename));
            if ( ! $doesPatchExists)
            {
                return basename($filename);
            }
            else
            {
                return FALSE;
            }
        }
    }

    /**
     * Run the given SQL file
     *
     * @param $filename
     * @return string
     * @throws Exception
     */
    protected function runFile($filename)
    {
        //Check if we need to skip this phase
        if ( in_array(basename($filename), $this->config['adv']['skip_patches']) )
        {
            return 'Skipping '.basename($filename). ' [SKIP]'.PHP_EOL;
        }
        // clear file statistics cache
        clearstatcache(true, $filename);
        
        //Check if file has content
        $sql = trim(file_get_contents($filename));
        
        if ( ! $sql)
        {
            return 'Skipping '.basename($filename). ' [EMPTY]'.PHP_EOL;
        }
        else
        {
            //Check if this patch has been previously applied
            $doesPatchExists = $this->readOneUsingName(basename($filename));
                
            if ( ! $doesPatchExists)
            {              
                $start = microtime(true);
                $hasBeenApplied = $this->createForAppliedPatch(basename($filename),$sql);

                if ( $hasBeenApplied !== TRUE )
                {
                    // do not proceed as patch order implies sequence
                    throw new Exception( $hasBeenApplied );
                }
                else
                {
                    return 'Applied '.basename($filename).' in '.round((microtime(true)-$start), 4).'secs'.PHP_EOL;
                }
            }
            else
            {
                return '';
                // return 'Skipping '.basename($filename). ' [APPLIED]'.PHP_EOL; //Superfluosly verbose
            }
        }
    }
    
}
