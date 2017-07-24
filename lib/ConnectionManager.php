<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Singleton to manage any and all database connections.
 *
 * @package ActiveRecord
 */
class ConnectionManager extends Singleton
{
	/**
	 * Array of {@link Connection} objects.
	 * @var array
	 */
	static private $connections = array();

	/**
	 * If $name is null then the default connection will be returned.
	 *
	 * @see Config
	 * @param string $name Optional name of a connection
	 * @return Connection
	 */
	public static function get_connection($enviroment=null, $dbDefault=null)
	{
		$config = Config::instance();

		if (! $enviroment) {
			if (isset($config->get_connections()["enviroment"])) {
				$enviroment = $config->get_connections()["enviroment"];
			} else {
				$enviroment = $config->get_default_enviroment();
			}
		}

		if (! $dbDefault) {
			if (isset($config->get_connections()["default"])) {
				$dbDefault = $config->get_connections()["default"];
			} else {
				$dbDefault = $config->get_default_db();
			}
		}

		if (!isset(self::$connections[$enviroment][$dbDefault]) || !self::$connections[$enviroment][$dbDefault])
			self::$connections[$enviroment][$dbDefault] = Connection::instance($config->get_connection($enviroment, $dbDefault));

		return self::$connections[$enviroment][$dbDefault];
	}

	/**
	 * Drops the connection from the connection manager. Does not actually close it since there
	 * is no close method in PDO.
	 *
	 * If $name is null then the default connection will be returned.
	 *
	 * @param string $name Name of the connection to forget about
	 */
	public static function drop_connection($name=null)
	{
		$config = Config::instance();
		$name = $name ? $name : $config->get_default_connection();

		if (isset(self::$connections[$name]))
			unset(self::$connections[$name]);
	}
}