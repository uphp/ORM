<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

use Closure;

use ActiveRecord\exceptions\ConfigException;

/**
 * Manages configuration options for ActiveRecord.
 *
 * <code>
 * ActiveRecord::initialize(function($cfg) {
 *   $cfg->set_model_home('models');
 *   $cfg->set_connections(array(
 *     'development' => 'mysql://user:pass@development.com/awesome_development',
 *     'production' => 'mysql://user:pass@production.com/awesome_production'));
 * });
 * </code>
 *
 * @package ActiveRecord
 */
class Config extends Singleton
{
	/**
	 * Name of the connection to use by default.
	 *
	 * <code>
	 * ActiveRecord\Config::initialize(function($cfg) {
	 *   $cfg->set_model_directory('/your/app/models');
	 *   $cfg->set_connections(array(
	 *     'development' => 'mysql://user:pass@development.com/awesome_development',
	 *     'production' => 'mysql://user:pass@production.com/awesome_production'));
	 * });
	 * </code>
	 *
	 * This is a singleton class so you can retrieve the {@link Singleton} instance by doing:
	 *
	 * <code>
	 * $config = ActiveRecord\Config::instance();
	 * </code>
	 *
	 * @var string
	 */
	private $default_connection = []; //TODO doc
	private $default_enviroment = "development"; //TODO doc
	private $default_db = "sqlite"; //TODO doc

	/**
	 * Contains the list of database connection strings.
	 *
	 * @var array
	 */
	private $connections = [];

	/**
	 * Directory for the auto_loading of model classes.
	 *
	 * @see activerecord_autoload
	 * @var string
	 */
	private $model_directory;

	/**
	 * Switch for logging.
	 *
	 * @var bool
	 */
	private $logging = false;

	/**
	 * Contains a Logger object that must impelement a log() method.
	 *
	 * @var object
	 */
	private $logger;

	/**
	 * Contains the class name for the Date class to use. Must have a public format() method and a
	 * public static createFromFormat($format, $time) method
	 *
	 * @var string
	 */
	private $date_class = 'ActiveRecord\\DateTime';

	/**
	 * The format to serialize DateTime values into.
	 *
	 * @var string
	 */
	private $date_format = \DateTime::ISO8601;

	/**
	 * Allows config initialization using a closure.
	 *
	 * This method is just syntatic sugar.
	 *
	 * <code>
	 * ActiveRecord\Config::initialize(function($cfg) {
	 *   $cfg->set_model_directory('/path/to/your/model_directory');
	 *   $cfg->set_connections(array(
	 *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
	 * });
	 * </code>
	 *
	 * You can also initialize by grabbing the singleton object:
	 *
	 * <code>
	 * $cfg = ActiveRecord\Config::instance();
	 * $cfg->set_model_directory('/path/to/your/model_directory');
	 * $cfg->set_connections(array('development' =>
  	 *   'mysql://username:password@localhost/database_name'));
	 * </code>
	 *
	 * @param Closure $initializer A closure
	 * @return void
	 */
	public static function initialize(Closure $initializer)
	{
		$initializer(parent::instance());
	}

	/**
	 * Sets the list of database connection strings.
	 *
	 * <code>
	 * $config->set_connections(array(
	 *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
	 * </code>
	 *
	 * @param array $connections Array of connections
	 * @param string $default_connection Optionally specify the default_connection
	 * @return void
	 * @throws ActiveRecord\ConfigException
	 */
	public function set_connections($connections)
	{
		if (! is_array($connections) || ! isset($connections["connections"]))
			throw new ConfigException("Index connections not exists or not is array");

		$enviroment = (isset($connections["environment"])) ? $connections["environment"] : $this->get_default_enviroment();
		$dbDefault = (isset($connections["default"])) ? $connections["default"] : $this->get_default_db();

		if(! isset($connections["connections"][$enviroment][$dbDefault])) throw new ConfigException("Config for " . $dbDefault . " not exists");

		$this->set_default_connection($connections["connections"][$enviroment][$dbDefault]);

		$this->connections = $connections;
	}

	/**
	 * Returns the connection strings array.
	 *
	 * @return array
	 */
	public function get_connections()
	{
		return $this->connections;
	}

	/**
	 * Returns a connection string if found otherwise null.
	 *
	 * @param string $name Name of connection to retrieve
	 * @return string connection info for specified connection name
	 */
	public function get_connection($enviroment, $dbDefault)
	{
		if (! isset($this->connections["connections"])) throw new ConfigException("Index CONNECTIONS not found in database.php");
		if (! array_key_exists($enviroment, $this->connections["connections"])) return null;
		return $this->connections["connections"][$enviroment][$dbDefault];
	}

	/**
	 * Returns the default connection string or null if there is none.
	 *
	 * @return string
	 */
	public function get_default_connection_string()
	{
		return array_key_exists($this->default_connection,$this->connections) ?
			$this->connections[$this->default_connection] : null;
	}

	
	//TODO doc
	public function get_default_connection()
	{
		return $this->default_connection;
	}

	//TODO doc
	public function set_default_connection($configDB)
	{
		$this->default_connection = $configDB;
	}
	
	/**
	 * Returns the name of the default connection.
	 *
	 * @return string
	 */
	public function get_default_enviroment()
	{
		return $this->default_enviroment;
	}

	/**
	 * Set the name of the default connection.
	 *
	 * @param string $name Name of a connection in the connections array
	 * @return void
	 */
	public function set_default_enviroment($name)
	{
		$this->default_enviroment = $name;
	}

	//TODO doc
	public function set_default_db($name)
	{
		$this->default_db = $name;
	}

	//TODO doc
	public function get_default_db()
	{
		return $this->default_db;
	}

	/**
	 * Sets the directory where models are located.
	 *
	 * @param string $dir Directory path containing your models
	 * @return void
	 */
	public function set_model_directory($dir)
	{
		$this->model_directory = $dir;
	}

	/**
	 * Returns the model directory.
	 *
	 * @return string
	 * @throws ConfigException if specified directory was not found
	 */
	public function get_model_directory()
	{
		if ($this->model_directory && !file_exists($this->model_directory))
			throw new ConfigException('Invalid or non-existent directory: '.$this->model_directory);

		return $this->model_directory;
	}

	/**
	 * Turn on/off logging
	 *
	 * @param boolean $bool
	 * @return void
	 */
	public function set_logging($bool)
	{
		$this->logging = (bool)$bool;
	}

	/**
	 * Sets the logger object for future SQL logging
	 *
	 * @param object $logger
	 * @return void
	 * @throws ConfigException if Logger objecct does not implement public log()
	 */
	public function set_logger($logger)
	{
		$klass = Reflections::instance()->add($logger)->get($logger);

		if (!$klass->getMethod('log') || !$klass->getMethod('log')->isPublic())
			throw new ConfigException("Logger object must implement a public log method");

		$this->logger = $logger;
	}

	/**
	 * Return whether or not logging is on
	 *
	 * @return boolean
	 */
	public function get_logging()
	{
		return $this->logging;
	}

	/**
	 * Returns the logger
	 *
	 * @return object
	 */
	public function get_logger()
	{
		return $this->logger;
	}

	public function set_date_class($date_class)
	{
		try {
			$klass = Reflections::instance()->add($date_class)->get($date_class);
		} catch (\ReflectionException $e) {
			throw new ConfigException("Cannot find date class");
		}

		if (!$klass->hasMethod('format') || !$klass->getMethod('format')->isPublic())
			throw new ConfigException('Given date class must have a "public format($format = null)" method');

		if (!$klass->hasMethod('createFromFormat') || !$klass->getMethod('createFromFormat')->isPublic())
			throw new ConfigException('Given date class must have a "public static createFromFormat($format, $time)" method');

		$this->date_class = $date_class;
	}

	public function get_date_class()
	{
		return $this->date_class;
	}

	/**
	 * @deprecated
	 */
	public function get_date_format()
	{
		trigger_error('Use ActiveRecord\Serialization::$DATETIME_FORMAT. Config::get_date_format() has been deprecated.', E_USER_DEPRECATED);
		return Serialization::$DATETIME_FORMAT;
	}

	/**
	 * @deprecated
	 */
	public function set_date_format($format)
	{
		trigger_error('Use ActiveRecord\Serialization::$DATETIME_FORMAT. Config::set_date_format() has been deprecated.', E_USER_DEPRECATED);
		Serialization::$DATETIME_FORMAT = $format;
	}

	/**
	 * Sets the url for the cache server to enable query caching.
	 *
	 * Only table schema queries are cached at the moment. A general query cache
	 * will follow.
	 *
	 * Example:
	 *
	 * <code>
	 * $config->set_cache("memcached://localhost");
	 * $config->set_cache("memcached://localhost",array("expire" => 60));
	 * </code>
	 *
	 * @param string $url Url to your cache server.
	 * @param array $options Array of options
	 */
	public function set_cache($url, $options=array())
	{
		Cache::initialize($url,$options);
	}
}