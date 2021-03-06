<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ConnectionManager', 'Model');
App::uses('Inflector', 'Utility');
App::uses('Table', 'Entity.ORM');

/**
 * Provides a registry/factory for Table objects.
 *
 * This registry allows you to centralize the configuration for tables
 * their connections and other meta-data.
 *
 * ## Configuring instances
 *
 * You may need to configure your table objects, using TableRegistry you can
 * centralize configuration. Any configuration set before instances are created
 * will be used when creating instances. If you modify configuration after
 * an instance is made, the instances *will not* be updated.
 *
 * {{{
 * TableRegistry::config('Users', ['table' => 'my_users']);
 * }}}
 *
 * Configuration data is stored *per alias* if you use the same table with
 * multiple aliases you will need to set configuration multiple times.
 *
 * ## Getting instances
 *
 * You can fetch instances out of the registry using get(). One instance is stored
 * per alias. Once an alias is populated the same instance will always be returned.
 * This is used to make the ORM use less memory and help make cyclic references easier
 * to solve.
 *
 * {{{
 * $table = TableRegistry::get('Users', $config);
 * }}}
 *
 */
class TableRegistry
{

    /**
     * Configuration for aliases.
     *
     * @var array
     */
    protected static $_config = [];

    /**
     * Instances that belong to the registry.
     *
     * @var array
     */
    protected static $_instances = [];

    /**
     * Stores a list of options to be used when instantiating an object
     * with a matching alias.
     *
     * The options that can be stored are those that are recognized by `get()`
     * If second argument is omitted, it will return the current settings
     * for $alias.
     *
     * If no arguments are passed it will return the full configuration array for
     * all aliases
     *
     * @param string $alias Name of the alias
     * @param null|array $options list of options for the alias
     * @return array The config data.
     * @throws RuntimeException When you attempt to configure an existing table instance.
     */
    public static function config($alias = null, $options = null)
    {
        if ($alias === null) {
            return static::$_config;
        }
        if (!is_string($alias)) {
            return static::$_config = $alias;
        }
        if ($options === null) {
            return isset(static::$_config[$alias]) ? static::$_config[$alias] : [];
        }
        if (isset(static::$_instances[$alias])) {
            throw new RuntimeException(sprintf(
                'You cannot configure "%s", it has already been constructed.',
                $alias
            ));
        }
        return static::$_config[$alias] = $options;
    }

    /**
     * Get a table instance from the registry.
     *
     * Tables are only created once until the registry is flushed.
     * This means that aliases must be unique across your application.
     * This is important because table associations are resolved at runtime
     * and cyclic references need to be handled correctly.
     *
     * The options that can be passed are the same as in `Table::__construct()`, but the
     * key `className` is also recognized.
     *
     * If $options does not contain `className` CakePHP will attempt to construct the
     * class name based on the alias. For example 'Users' would result in
     * `App\Model\Table\UsersTable` being attempted. If this class does not exist,
     * then the default `Cake\ORM\Table` class will be used. By setting the `className`
     * option you can define the specific class to use. This className can
     * use a short class reference.
     *
     * If no `table` option is passed, the table name will be the underscored version
     * of the provided $alias.
     *
     * If no `connection` option is passed the table's defaultConnectionName() method
     * will be called to get the default connection name to use.
     *
     * @param string $alias The alias you want to get.
     * @param array $options The options you want to build the table with.
     *   If a table has already been loaded the options will be ignored.
     * @return Cake\Database\Table
     * @throws RuntimeException When you try to configure an alias that already exists.
     */
    public static function get($alias, $options = [])
    {
        $exists = isset(static::$_instances[$alias]);
        if ($exists && !empty($options)) {
            throw new RuntimeException(sprintf(
                'You cannot configure "%s", it already exists in the registry.',
                $alias
            ));
        }
        if ($exists) {
            return static::$_instances[$alias];
        }

        list($plugin, $baseClass) = pluginSplit($alias);
        $options = ['alias' => $baseClass] + $options;

        if (empty($options['className'])) {
            $class = Inflector::camelize($alias);
            // Hack
            if (!class_exists($class . 'Table')) {
                App::uses($class . 'Table', 'Model/Table');
            }
            if (class_exists($class . 'Table')) {
                $options['className'] = $options['class'] = $class . 'Table';
            } else {
                $options['className'] = $options['class'] = 'Table';
            }
            // ENDHACK
        }

        if (isset(static::$_config[$alias])) {
            $options = array_merge(static::$_config[$alias], $options);
        }
        if (empty($options['connection'])) {
            $connectionName = $options['className']::defaultConnectionName();
            // HACK
            $options['ds'] = $options['connection'] = ConnectionManager::getDataSource($connectionName);
            // ENDHACK
        }
        return static::$_instances[$alias] = ClassRegistry::init($options['className'], $options);
    }

    /**
     * Check to see if an instance exists in the registry.
     *
     * @param string $alias The alias to check for.
     * @return bool
     */
    public static function exists($alias)
    {
        return isset(static::$_instances[$alias]);
    }

    /**
     * Set an instance.
     *
     * @param string $alias The alias to set.
     * @param Cake\ORM\Table $object The table to set.
     * @return void
     */
    public static function set($alias, Table $object)
    {
        return static::$_instances[$alias] = $object;
    }

    /**
     * Clears the registry of configuration and instances.
     *
     * @return void
     */
    public static function clear()
    {
        static::$_instances = [];
        static::$_config = [];
    }

}
