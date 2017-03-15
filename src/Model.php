<?php

namespace Zeroplusone\Patchr;

/**
 * Class Model
 * @package Zeroplusone\Patchr
 * @copyright 2014-2017 Davide Gaido
 */
class Model
{
    private $table = FALSE;
    private $db_params = FALSE;
    private $mysqli = NULL;

    /**
     * PatchrModel constructor.
     * @param $db_params
     */
    protected function __construct( $db_params )
    {
        $this->db_params = $db_params;
        $this->table = $db_params['table'];
        $this->readOrCreateTable();
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function readLastApplied()
    {
        $mysqli = $this->getMysqli();
        /* create a prepared statement */
        if ($stmt = $mysqli->prepare('SELECT patch_id, name, applied_at FROM '.$this->table.' ORDER BY applied_at DESC LIMIT 1 '))
        {
            $stmt->execute();
            $stmt->bind_result($patch_id, $name, $applied_at);
            $stmt->fetch();
            $stmt->close();
            $this->closeMysqli($mysqli);
            
            return( array('patch_id'=>$patch_id, 'name'=>$name, 'applied_at'=>$applied_at) );
        }
        else
        {
            throw new Exception($mysqli->error);
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws Exception
     */
    protected function readOneUsingName($name)
    {
        $mysqli = $this->getMysqli();
        /* create a prepared statement */
        if ($stmt = $mysqli->prepare('SELECT patch_id FROM '.$this->table.' WHERE name = ?'))
        {
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $stmt->bind_result($patch_id);
            $stmt->fetch();
            $stmt->close();
            $this->closeMysqli($mysqli);
            return($patch_id);
        }
        else
        {
            throw new Exception($mysqli->error);
        }
    }

    /**
     * @param $name
     * @param $sql
     * @return bool|string
     * @throws Exception
     */
    protected function createForAppliedPatch($name, $sql)
    {
        $mysqli = $this->getMysqli();
        //An interesting quirk of MySQL is the DDL automocommits, meaning that multiple create statements will always autocommit. ( http://dev.mysql.com/doc/refman/5.1/en/implicit-commit.html )
        $mysqli->autocommit(FALSE);
        $mysqli->set_charset("utf8");

        try
        {            
            $mysqli->multi_query($sql);
            //Store multi query results
            do
            { 
                $mysqli->use_result(); 
            }
            while ($mysqli->more_results() && $mysqli->next_result());
            //Manually checks for errors! Multi Query doesn't throw an execption when the first query succeeds!
            if($mysqli->errno == 0)
            {
                $mysqli->commit();
                //Prepared statement
                if ($stmt = $mysqli->prepare('INSERT INTO '.$this->table.' (name, applied_at) VALUES (?, NOW())'))
                {
                    $stmt->bind_param('s', $name);
                    $stmt->execute();
                    $stmt->close();
                    $mysqli->commit(); //Explictly commit since in transaction mode
                    $this->closeMysqli($mysqli);
                    return TRUE;
                }
                else
                {
                    throw new Exception($mysqli->error);
                }
            }
            else
            {
                throw new Exception($mysqli->error);
            }
        }
        catch (Exception $e)
        {
            $mysqli->rollBack();
            return $e->getMessage(); 
        }
    }

    /**
     * Currently used exclusively for the demo
     * TODO test extensively and refactor createForAppliedPatch
     *
     * @param $name
     * @return bool
     * @throws Exception
     */
    protected function createAppliedPatchRow($name)
    {
        $mysqli = $this->getMysqli();

        //Prepared statement
        if ($stmt = $mysqli->prepare('INSERT INTO '.$this->table.' (name, applied_at) VALUES (?, NOW())'))
        {
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $stmt->close();
            $mysqli->commit(); //Explictly commit since in transaction mode
            $this->closeMysqli($mysqli);
            return TRUE;
        }
        else
        {
            throw new Exception($mysqli->error);
        }
    }

    /**
     * Check if the main table exists, if it doesn't create it
     * 
     * @return boolean
     * @throws Exception
     */
    private function readOrCreateTable()
    {
        $mysqli = $this->getMysqli();
        if ($stmt = $mysqli->prepare('SHOW TABLES LIKE "'.$this->table.'";'))
        {
            $stmt->execute();
            $stmt->bind_result($row);
            $stmt->fetch();
            $stmt->close();
            //If row is null the table doesn't exists
            if(is_null($row))
            {
                //Create said table
                $createStatement = 
                    'CREATE TABLE `'.$this->table.'` (
                        `patch_id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
                        `applied_at` datetime NOT NULL,
                        PRIMARY KEY (`patch_id`)
                      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
                if ($stmt = $mysqli->prepare( $createStatement ))
                {
                    $stmt->execute();
                    $stmt->close();
                    $this->closeMysqli($mysqli);
                    return TRUE;
                }
                else
                {
                    throw new Exception($mysqli->error);
                }
            }
            else
            {
                $this->closeMysqli($mysqli);
                return TRUE;
            }
        }
        else
        {
            throw new Exception($mysqli->error);
        }
    }
    
    /**
     * Creates or returns existing connection
     * 
     * @return \mysqli
     * @throws Exception
     */
    private function getMysqli()
    {
        $db = $this->db_params;
        // We might want to spawn different connections for each patch.
        // making a connection from php to mysql is NOT resource intensive. 
        // Sometimes, it is a better idea to have a smaller timeout and make multiple connections. 
        // Doing so will free up the connections for other processes.
        if( is_null($this->mysqli) && $db['multiple'] === FALSE )
        {
            $mysqli = new \mysqli($db['host'], $db['user'], $db['password'], $db['database']);
            $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $db['timeout']);
            //Check Connection
            if (mysqli_connect_errno())
            {
                throw new Exception( 'Connect failed: '. mysqli_connect_error() );
            }
            return $mysqli;
        }
        else
        {
            return $this->mysqli;
        }
    }

    /**
     * @param $mysqli
     */
    private function closeMysqli($mysqli)
    {
        //Close the connection only if using multiple queries per transaction
        if ( $this->db_params['multiple'] === TRUE )
        {
            $mysqli->close();
            $this->mysqli->close();
            
            unset($mysqli);
            $this->mysqli = NULL;
        }
    }
}