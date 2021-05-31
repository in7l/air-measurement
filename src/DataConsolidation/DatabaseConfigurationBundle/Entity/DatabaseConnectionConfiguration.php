<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/13/16
 * Time: 5:26 PM
 */

namespace DataConsolidation\DatabaseConfigurationBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use DataConsolidation\DatabaseConfigurationBundle\Utils\DatabaseConfigurator;


class DatabaseConnectionConfiguration
{
    /**
     * @Assert\NotBlank(groups={"edit"})
     */
    protected $connectionAlias;

    /**
     * @Assert\Choice(
     *     choices = { "pdo_mysql", "pdo_pgsql" },
     *     message = "Choose a valid driver."
     * )
     */
    protected $driver;

    /**
     * @Assert\NotBlank()
     */
    protected $host;

    /**
     * @Assert\Range(
     *     min = 0,
     *     max = 65535,
     *     minMessage = "The port number must be equal to or greater than {{ limit }}",
     *     maxMessage = "The port number must be equal to or less than {{ limit }}"
     * )
     */
    protected $port;

    /**
     * @Assert\NotBlank()
     */
    protected $dbName;

    /**
     * @Assert\NotBlank()
     */
    protected $user;
    protected $password;

    /**
     * @return mixed
     */
    public function getConnectionAlias()
    {
        return $this->connectionAlias;
    }

    /**
     * @param mixed $connectionAlias
     */
    public function setConnectionAlias($connectionAlias)
    {
        $this->connectionAlias = $connectionAlias;
    }

    /**
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param string $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int|null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int|null $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @param string $dbName
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

}