<?php
namespace Muffin\Webservice;

use Cake\Core\InstanceConfigTrait;
use Muffin\Webservice\Exception\UnimplementedWebserviceMethodException;
use RuntimeException;

abstract class AbstractDriver
{
    use InstanceConfigTrait;

    protected $_client;

    protected $_defaultConfig = [];

    /**
     * @var bool
     */
    protected $_logQueries = false;

    /**
     * @var \DebugKit\Database\Log\DebugLog
     */
    protected $_logger;

    /**
     * @var string
     */
    protected $_name;

    /**
     * Constructor.
     *
     * @param array $config Custom configuration.
     */
    public function __construct($config)
    {
        $this->config($config);
        $this->initialize();
    }

    /**
     * Initialize is used to easily extend the constructor.
     *
     * @return void
     */
    abstract public function initialize();

    /**
     * @return string
     */
    public function configName()
    {
        if (!empty($this->_name)) {
            return $this->_name;
        }

        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Whether or not to log queries. This is used by `DebugKit`'s `SqlLogPanel`.
     *
     * @return bool
     */
    public function logQueries($log = null)
    {
        if (is_bool($log)) {
            $this->_logQueries = $log;
        }

        return $this->_logQueries;
    }

    /**
     * Inject's logger. Right now, it gets it from `DebugKit`'s `SqlLogPanel` but doesn't
     * do much with it, just wanted to circumvent the errors.
     *
     * @param $logger
     */
    public function logger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Proxies the client's methods.
     *
     * @param string $method Method name.
     * @param array $args Arguments to pass-through.
     * @return mixed
     * @throws \RuntimeException If the client object has not been initialized.
     * @throws \Muffin\Webservice\Exception\UnimplementedWebserviceMethodException If the method does not exist in the client.
     */
    public function __call($method, $args)
    {
        if (!is_object($this->_client)) {
            throw new RuntimeException(sprintf(
                'The `%s` client has not been initialized',
                $this->config('name')
            ));
        }

        if (!method_exists($this->_client, $method)) {
            throw new UnimplementedWebserviceMethodException([
                'name' => $this->config('name'),
                'method' => $method
            ]);
        }

        return call_user_func_array([$this->_client, $method], $args);
    }
}
