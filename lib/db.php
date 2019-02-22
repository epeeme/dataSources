<?php

/**
 * SQL functions to handle database interaction for the script to extract all event
 * the BF source code and find any changes.
 *
 * @author  Dan Kew <dan@epee.me>
 * @license http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */

class DbBF
{
    /**
     * Basic extension of the PHP Data Object
     *
     * @var object $PDO
     */
    protected $PDO;


    /**
     * Establish connection to server
     */
    public function __construct()
    {
        try {
            $this->PDO = new PDO('mysql:host=mysql.hostinger.co.uk;dbname=u534143343_epee;charset=utf8mb4', 'u534143343_epee', '%grd5wms');
        } catch (PDOException $exception) {
            printf("Failed to connect to the  database. Error: %s",  $exception->getMessage());
        }

    }//end __construct()


    /**
     * Prepare Statement: Returns PDOStatement
     *
     * @param string $q contains passed SQL statement
     *
     * @return PDOStatement
     */
    public function prepare($q)
    {
        return $this->PDO -> prepare($q);

    }//end prepare()


    /**
     * Binds a variable to a corresponding question mark placeholder
     *
     * @param PDOStatement $stmt  created by prepare
     * @param mixed        $param placeholder to bind variable to
     * @param mixed        $query value to substitute
     *
     * @return void
     */
    public function bind($stmt, $param, $query)
    {
        $stmt -> bindParam($param, $query, PDO::PARAM_STR);

    }//end bind()


    /**
     * Execute the SQL
     *
     * @param PDOStatement $stmt created by prepare & bind
     *
     * @return void
     */
    public function execute($stmt)
    {
        return $stmt->execute();

    }//end execute()


    /**
     * Get the number of rows affected by the query
     *
     * @param PDOStatement $stmt created by prepare & bind
     *
     * @return void
     */
    public function rowCount($stmt)
    {
        return $stmt->rowCount();

    }//end exec()

    /**
     * Specific function to return every value from the calendar table we are using
     *
     * @param PDOStatement $stmt created by prepare, bind & execute
     *
     * @return array
     */
    public function getAllResults($stmt)
    {
        return $stmt->fetchAll(PDO::FETCH_COLUMN);

    }//end getAllResults()


    /**
     * Specific function to return a single value from the table we are using
     *
     * @param PDOStatement $stmt created by prepare, bind & execute
     *
     * @return array
     */
    public function getResult($stmt)
    {
        return $stmt->fetch(PDO::FETCH_COLUMN);

    }//end getResult()


    /**
     * Graceful exit
     */
    public function __destruct()
    {
        $this->PDO = null;

    }//end __destruct()


}//end class
