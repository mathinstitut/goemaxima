<?php
/**
 * Copyright (c) 2019 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

require_once (__DIR__ . '/class.assStackQuestionConfig.php');
/**
 * STACK Question server
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @ingroup    ModulesTestQuestionPool
 */
class assStackQuestionServer
{
    const PURPOSE_RUN = 'run';
    const PURPOSE_EDIT = 'edit';
    const PURPOSE_ANY = 'any';


    /** @var self[] | null  (indexed by server_id) */
    protected static $servers;


    /** @var int $server_id */
    protected $server_id;

    /** @var string $urpose */
    protected $purpose;

    /** @var string $address */
    protected $address;

    /** @var bool */
    protected $active;


    /**
     * Load the servers configuration
     */
    public static function loadServers()
    {
        if (!isset(self::$servers))
        {
            $config = assStackQuestionConfig::_getStoredSettings('connection');
            self::readServersFromConfig($config);
        }
    }

    /**
     * Save the server configuration
     */
    public static function saveServers()
    {
        self::loadServers();

        $data = [];
        foreach (self::$servers as $server)
        {
            // stored json array is not indexed by server_id
            $data[] = $server->toArray();
        }

        $configObj = new assStackQuestionConfig();
        $configObj->saveToDB('maxima_servers', json_encode($data), 'connection');
    }

    /**
     * Delete servers with given ids
     * @param int[]
     */
    public static function deleteServers($server_ids = [])
    {
        self::loadServers();
        foreach ($server_ids as $server_id)
        {
            unset(self::$servers[$server_id]);
        }
        self::saveServers();
    }

    /**
     * Get the servers configuration from an already loaded configuration array
     * @param array $config
     */
    public static function readServersFromConfig($config)
    {
        self::$servers = array();

        if (isset($config['maxima_servers']))
        {
            $servers = (array) json_decode($config['maxima_servers']);
            foreach ($servers as $properties)
            {
                $server = new self;
                $server->fromArray((array) $properties);
                self::$servers[$server->getServerId()] = $server;
            }
        }
        elseif (isset($config['maxima_command']) && substr($config['maxima_command'], 0, 4) == 'http')
        {
            // migrate maxima command to a server setting and store it
            $server = self::getDefaultServer($config['maxima_command']);
            $server->save();

            // delete the server url from the maxima command
            $configObj = new assStackQuestionConfig();
            $configObj->saveToDB('maxima_command', null, 'connection');
        }
    }


    /**
     * Get the list of all servers, indexed by server_id
     * @return assStackQuestionServer[]
     */
    public static function getServers()
    {
        self::loadServers();
        return self::$servers;
    }

    /**
     * Get the list of defined purposes
     * @return string[]
     */
    public static function getPurposes()
    {
        return [self::PURPOSE_ANY, self::PURPOSE_EDIT, self::PURPOSE_RUN];
    }

    /**
     * Wrote the properties to an array
     * @return array
     */
    public function toArray()
    {
        return [
            'server_id' => $this->server_id,
            'active' => $this->active,
            'purpose' => $this->purpose,
            'address' => $this->address
        ];
    }

    /**
     * Get the properties from an array
     * @param array
     */
    public function fromArray($array)
    {
        $this->server_id = (int) $array['server_id'];
        $this->active = (bool) $array['active'];
        $this->purpose = (string) $array['purpose'];
        $this->address = (string) $array['address'];
    }

    /**
     *  Get a server by id
     *  @param int $server_id
     *  @return self
     */
    public static function getServerById($server_id)
    {
        self::loadServers();

        if (isset(self::$servers[$server_id]))
        {
            return self::$servers[$server_id];
        }
        else
        {
            $server = self::getDefaultServer();
            if (!empty($server_id))
            {
                $server->server_id = (int) $server_id;
            }
            return $server;
        }
    }


    /**
     * Get a default server
     * @param string $address
     * @return self
     */
    public static function getDefaultServer($address = 'http://localhost:8080/MaximaPool/MaximaPool')
    {
        $server = new self;
        $server->setServerId(0);
        $server->setAddress($address);
        $server->setPurpose(self::PURPOSE_ANY);
        $server->setActive(true);

        return $server;
    }


    /**
     * Get an available server for the intended purpose randomly
     * @param string $purpose
     * @return self
     */
    public static function getServerForPurpose($purpose)
    {
        $available = [];

        self::loadServers();
        foreach (self::$servers as $server)
        {
            if ($server->isAvailable($purpose))
            {
                $available[$server->getServerId()] = $server;
            }
        }

        if (empty($available))
        {
            return self::getDefaultServer();
        }
        else
        {
            return $available[array_rand($available)];
        }
    }


    /**
     * @return int
     */
    public function getServerId()
    {
        return $this->server_id;
    }

    /**
     * @param int $server_id
     */
    protected function setServerId($server_id)
    {
        $this->server_id = $server_id;
    }

    /**
     * @return string
     */
    public function getPurpose()
    {
        return $this->purpose;
    }

    /**
     * @param string $purpose
     */
    public function setPurpose($purpose)
    {
        $this->purpose = (string) $purpose;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = (string) $address;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;
    }


    /**
     * Check if the server is available for the purpose
     * @param string $purpose
     * @return bool
     */
    public function isAvailable($purpose)
    {
        if ($this->purpose == $purpose || $this->purpose == self::PURPOSE_ANY || $purpose == self::PURPOSE_ANY)
        {
            return $this->isActive();
        }

        return false;
    }


    /**
     * Save a server definition
     */
    public function save()
    {
        self::loadServers();

        if (empty($this->server_id))
        {
            if (empty(self::$servers))
            {
                $this->server_id = 1;
            }
            else
            {
                $this->server_id = max(array_keys(self::$servers)) + 1;
            }
        }

        self::$servers[$this->server_id] = $this;
        self::saveServers();
    }
}