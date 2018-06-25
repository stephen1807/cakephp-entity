<?php
App::uses('AppModel', 'Model');
App::uses('Entity', 'Entity.ORM');
App::uses('Hash', 'Utility');

class Table extends AppModel
{

    public $entity;

    protected $_entityClass = null;

    protected $_savedEntityStates = [];

    /**
     * Class constructor
     *
     * @param bool|int|string|array $id Set this ID for this model on startup.
     * @param string $table Name of database table to use.
     * @param string $ds DataSource connection name.
     */
    public function __construct($id = false, $table = null, $ds = null)
    {
        if (is_array($id)) {
            $alias = Hash::get($id, 'alias') ?: (Hash::get($id, 'table') ?: $this->alias);
            $id['alias'] = Inflector::singularize(preg_replace('/Table$/', '', $alias));
            $this->alias = $this->alias($id['alias']);
            $schema = Hash::get($id, 'schema');

            if ($schema !== null) {
                $this->_schema = $schema;
            }
        }

        if ($table === null) {
            if ($this->name === null) {
                $this->name = (isset($name) ? $name : get_class($this));
            }

            if ($this->alias === null) {
                $this->alias = (isset($alias) ? $alias : $this->name);
            }
            $table = Inflector::tableize(preg_replace('/Table$/', '', $this->alias));
        }

        parent::__construct($id, $table, $ds);
        $this->entityClass(Inflector::singularize($this->name) . 'Entity');
        $this->initialize([]);
        $this->entity(false);
    }

    /**
     * Get the default connection name.
     *
     * This method is used to get the fallback connection name if an
     * instance is created through the TableRegistry without a connection.
     *
     * @return string
     * @see Cake\ORM\TableRegistry::get()
     */
    public static function defaultConnectionName()
    {
        return 'default';
    }

    /**
     * Initialize a table instance. Called after the constructor.
     *
     * You can use this method to define associations, attach behaviors
     * define validation and do any other initialization logic you need.
     *
     * {{{
     *        public function initialize(array $config) {
     *                $this->belongsTo('Users');
     *                $this->belongsToMany('Tagging.Tags');
     *                $this->primaryKey('something_else');
     *        }
     * }}}
     *
     * @param array $config Configuration options passed to the constructor
     * @return void
     */
    public function initialize(array $config)
    {
    }

    /**
     * Returns the database table name or sets a new one
     *
     * @param string $table the new table name
     * @return string
     */
    public function table($table = null)
    {
        if ($table !== null) {
            $this->table = $this->useTable = $table;
        }

        if ($this->table === null) {
            $table = get_class($this);
            $table = substr(end($table), 0, -5);
            if (empty($table)) {
                $table = $this->alias();
            }
            $this->table = Inflector::underscore($table);
        }

        return $this->table;
    }

    /**
     * Returns the table alias or sets a new one
     *
     * @param string $alias the new table alias
     * @return string
     */
    public function alias($alias = null)
    {
        if ($alias !== null) {
            $this->_alias = $alias;
        }
        if ($this->_alias === null) {
            $alias = get_class($this);
            $this->_alias = Inflector::singularize(preg_replace('/Table$/', '', $alias));
        }
        return $this->_alias;
    }

    /**
     * Override this function in order to alter the schema used by this table.
     * This function is only called after fetching the schema out of the database.
     * If you wish to provide your own schema to this table without touching the
     * database, you can override schema() or inject the definitions though that
     * method.
     *
     * ### Example:
     *
     * {{{
     * protected function _initializeSchema(\Cake\Database\Schema\Table $table) {
     *  $table->columnType('preferences', 'json');
     *  return $table;
     * }
     * }}}
     *
     * @param \Cake\Database\Schema\Table $table The table definition fetched from database.
     * @return \Cake\Database\Schema\Table the altered schema
     * @throws Exception Method '_initializeSchema' not implemented
     */
    protected function _initializeSchema(Schema $table)
    {
        throw new Exception("Method '_initializeSchema' not implemented");
    }

    /**
     * Returns the connection instance or sets a new one
     *
     * @param \Cake\Database\Connection $conn the new connection instance
     * @return \Cake\Database\Connection
     */
    public function connection($conn = null)
    {
        if ($conn === null) {
            return $this->getDataSource();
        }

        $this->setDataSource($conn);
        return $this->getDataSource();
    }

    /**
     * Returns the current entity state or sets a new one
     *
     * @param string $boolean sets an entity state
     * @return string
     */
    public function entity($boolean = null)
    {
        if ($boolean !== null) {
            $this->entity = $boolean;
        }

        return $this->entity;
    }

    /**
     * Returns the primary key field name or sets a new one
     *
     * @param string $key sets a new name to be used as primary key
     * @return string
     */
    public function primaryKey($key = null)
    {
        if ($key !== null) {
            $this->primaryKey = $key;
        }

        return $this->primaryKey;
    }

    /**
     * Returns the display field or sets a new one
     *
     * @param string $key sets a new name to be used as display field
     * @return string
     */
    public function displayField($key = null)
    {
        if ($key !== null) {
            $this->displayField = $key;
        }

        return $this->displayField;
    }

    /**
     * Returns the class used to hydrate rows for this table or sets
     * a new one
     *
     * @param string $name the name of the class to use
     * @return string
     */
    public function entityClass($name = null)
    {
        if ($name === null && !$this->_entityClass) {
            $name = Inflector::classify($this->alias()) . 'Entity';
        }

        if ($name !== null) {
            App::uses($name, $this->_EntityClassLocation());
            $this->_entityClass = $name;
        }

        return $this->_entityClass;
    }

    /**
     * Add a behavior.
     *
     * Adds a behavior to this table's behavior collection. Behaviors
     * provide an easy way to create horizontally re-usable features
     * that can provide trait like functionality, and allow for events
     * to be listened to.
     *
     * Example:
     *
     * Load a behavior, with some settings.
     *
     * {{{
     * $this->addBehavior('Tree', ['parent' => 'parentId']);
     * }}}
     *
     * Behaviors are generally loaded during Table::initialize().
     *
     * @param string $name The name of the behavior. Can be a short class reference.
     * @param array $options The options for the behavior to use.
     * @return void
     */
    public function addBehavior($name, $options = [])
    {
        $this->Behaviors->load($name, $options);
    }

    /**
     * Removes a behavior from this table's behavior registry.
     *
     * Example:
     *
     * Remove a behavior from this table.
     *
     * {{{
     * $this->removeBehavior('Tree');
     * }}}
     *
     * @param string $name The alias that the behavior was added with.
     * @return void
     * @see \Cake\ORM\Behavior
     */
    public function removeBehavior($name)
    {
        $this->Behaviors->unload($name);
    }

    /**
     * Returns the behavior registry for this table.
     *
     * @return BehaviorCollection
     */
    public function behaviors()
    {
        $this->Behaviors;
    }

    /**
     * Check if a behavior with the given alias has been loaded.
     *
     * @param string $name The behavior alias to check.
     * @return array
     */
    public function hasBehavior($name)
    {
        $this->Behaviors->loaded($name);
    }

    /**
     * Returns a association objected configured for the specified alias if any
     *
     * @param string $name the alias used for the association
     * @return Cake\ORM\Association
     * @throws Exception Method 'association' not implemented
     */
    public function association($name)
    {
        throw new Exception("Method 'association' not implemented");
    }

    /**
     * Creates a new BelongsTo association between this table and a target
     * table. A "belongs to" association is a N-1 relationship where this table
     * is the N side, and where there is a single associated record in the target
     * table for each one in this table.
     *
     * Target table can be inferred by its name, which is provided in the
     * first argument, or you can either pass the to be instantiated or
     * an instance of it directly.
     *
     * The options array accept the following keys:
     *
     * - className: The class name of the target table object
     * - targetTable: An instance of a table object to be used as the target table
     * - foreignKey: The name of the field to use as foreign key, if false none
     *   will be used
     * - conditions: array with a list of conditions to filter the join with
     * - joinType: The type of join to be used (e.g. INNER)
     *
     * This method will return the association object that was built.
     *
     * @param string $associated the alias for the target table. This is used to
     * uniquely identify the association
     * @param array $options list of options to configure the association definition
     * @return Cake\ORM\Association\BelongsTo
     */
    public function belongsTo($associated, array $options = [])
    {
        $this->_bindModel('belongsTo', $associated, $options);
    }

    /**
     * Creates a new HasOne association between this table and a target
     * table. A "has one" association is a 1-1 relationship.
     *
     * Target table can be inferred by its name, which is provided in the
     * first argument, or you can either pass the class name to be instantiated or
     * an instance of it directly.
     *
     * The options array accept the following keys:
     *
     * - className: The class name of the target table object
     * - targetTable: An instance of a table object to be used as the target table
     * - foreignKey: The name of the field to use as foreign key, if false none
     *   will be used
     * - dependent: Set to true if you want CakePHP to cascade deletes to the
     *   associated table when an entity is removed on this table. Set to false
     *   if you don't want CakePHP to remove associated data, for when you are using
     *   database constraints.
     * - cascadeCallbacks: Set to true if you want CakePHP to fire callbacks on
     *   cascaded deletes. If false the ORM will use deleteAll() to remove data.
     *   When true records will be loaded and then deleted.
     * - conditions: array with a list of conditions to filter the join with
     * - joinType: The type of join to be used (e.g. LEFT)
     *
     * This method will return the association object that was built.
     *
     * @param string $associated the alias for the target table. This is used to
     * uniquely identify the association
     * @param array $options list of options to configure the association definition
     * @return Cake\ORM\Association\HasOne
     */
    public function hasOne($associated, array $options = [])
    {
        $this->_bindModel('hasOne', $associated, $options);
    }

    /**
     * Creates a new HasMany association between this table and a target
     * table. A "has many" association is a 1-N relationship.
     *
     * Target table can be inferred by its name, which is provided in the
     * first argument, or you can either pass the class name to be instantiated or
     * an instance of it directly.
     *
     * The options array accept the following keys:
     *
     * - className: The class name of the target table object
     * - targetTable: An instance of a table object to be used as the target table
     * - foreignKey: The name of the field to use as foreign key, if false none
     *   will be used
     * - dependent: Set to true if you want CakePHP to cascade deletes to the
     *   associated table when an entity is removed on this table. Set to false
     *   if you don't want CakePHP to remove associated data, for when you are using
     *   database constraints.
     * - cascadeCallbacks: Set to true if you want CakePHP to fire callbacks on
     *   cascaded deletes. If false the ORM will use deleteAll() to remove data.
     *   When true records will be loaded and then deleted.
     * - conditions: array with a list of conditions to filter the join with
     * - sort: The order in which results for this association should be returned
     * - strategy: The strategy to be used for selecting results Either 'select'
     *   or 'subquery'. If subquery is selected the query used to return results
     *   in the source table will be used as conditions for getting rows in the
     *   target table.
     *
     * This method will return the association object that was built.
     *
     * @param string $associated the alias for the target table. This is used to
     * uniquely identify the association
     * @param array $options list of options to configure the association definition
     * @return Cake\ORM\Association\HasMany
     */
    public function hasMany($associated, array $options = [])
    {
        $this->_bindModel('hasMany', $associated, $options);
    }

    /**
     * Creates a new BelongsToMany association between this table and a target
     * table. A "belongs to many" association is a M-N relationship.
     *
     * Target table can be inferred by its name, which is provided in the
     * first argument, or you can either pass the class name to be instantiated or
     * an instance of it directly.
     *
     * The options array accept the following keys:
     *
     * - className: The class name of the target table object
     * - targetTable: An instance of a table object to be used as the target table
     * - foreignKey: The name of the field to use as foreign key
     * - joinTable: The name of the table representing the link between the two
     * - through: If you choose to use an already instantiated link table, set this
     *   key to a configured Table instance containing associations to both the source
     *   and target tables in this association.
     * - cascadeCallbacks: Set to true if you want CakePHP to fire callbacks on
     *   cascaded deletes. If false the ORM will use deleteAll() to remove data.
     *   When true join/junction table records will be loaded and then deleted.
     * - conditions: array with a list of conditions to filter the join with
     * - sort: The order in which results for this association should be returned
     * - strategy: The strategy to be used for selecting results Either 'select'
     *   or 'subquery'. If subquery is selected the query used to return results
     *   in the source table will be used as conditions for getting rows in the
     *   target table.
     * - saveStrategy: Either 'append' or 'replace'. Indicates the mode to be used
     *   for saving associated entities. The former will only create new links
     *   between both side of the relation and the latter will do a wipe and
     *   replace to create the links between the passed entities when saving.
     *
     * This method will return the association object that was built.
     *
     * @param string $associated the alias for the target table. This is used to
     * uniquely identify the association
     * @param array $options list of options to configure the association definition
     * @return Cake\ORM\Association\BelongsToMany
     */
    public function belongsToMany($associated, array $options = [])
    {
        $this->_bindModel('hasAndBelongsToMany', $associated, $options);
    }

    /**
     * Table::_bindModel()
     *
     * @param mixed $type type of relation to create
     * @param string $associated the alias for the target table. This is used to
     * uniquely identify the association
     * @param array $options list of options to configure the association definition
     * @return bool Success
     */
    protected function _bindModel($type, $associated, array $options = [])
    {
        $reset = isset($options['reset']) ? $options['reset'] : false;
        if (isset($options['reset'])) {
            unset($options['reset']);
        }

        return $this->bindModel([
            $type => [$associated => $options]
        ], $reset);
    }

    /**
     * Returns a single record after finding it by its primary key, if no record is
     * found this method throws an exception.
     *
     * ###Example:
     *
     * {{{
     * $id = 10;
     * $article = $articles->get($id);
     *
     * $article = $articles->get($id, ['contain' => ['Comments]]);
     * }}}
     *
     * @param mixed $primaryKey primary key value to find
     * @param array $options options accepted by `Table::find()`
     * @throws NotFoundException if the record with such id could not be found
     * @return \Cake\ORM\Entity
     * @see Table::find()
     */
    public function get($primaryKey, $options = [])
    {
        $key = (array)$this->primaryKey();
        $conditions = array_combine($key, (array)$primaryKey);
        if (!isset($options['conditions'])) {
            $options['conditions'] = [];
        }
        $options['conditions'] = array_merge($options['conditions'], $conditions);
        $entity = $this->find('first', $options);

        if (!$entity) {
            throw new NotFoundException(sprintf(
                'Record "%s" not found in table "%s"',
                implode(',', (array)$primaryKey),
                $this->table()
            ));
        }

        return $entity;
    }

    /**
     * Returns the default validator object. Subclasses can override this function
     * to add a default validation set to the validator object.
     *
     * @param \Cake\Validation\Validator $validator The validator that can be modified to
     * add some rules to it.
     * @return \Cake\Validation\Validator
     * @throws Exception Method 'validationDefault' not implemented
     */
    public function validationDefault(Validator $validator)
    {
        throw new Exception("Method 'validationDefault' not implemented");
    }

    /**
     * Returns true if there is any row in this table matching the specified
     * conditions.
     *
     * @param array $conditions list of conditions to pass to the query
     * @return bool
     */
    public function exists($conditions = null)
    {
        if (!is_array($conditions)) {
            return parent::exists($conditions);
        }

        return (bool)$this->find('count', [
            'conditions' => $conditions,
            'recursive' => -1,
            'callbacks' => false
        ]);
    }

    /**
     * Table::save()
     *
     * @param array $entity Data to save, as either entity or array
     * @param bool|array $validate Either a boolean, or an array.
     *   If a boolean, indicates whether or not to validate before saving.
     *   If an array, can have following keys:
     *
     *   - validate: Set to true/false to enable or disable validation.
     *   - fieldList: An array of fields you want to allow for saving.
     *   - callbacks: Set to false to disable callbacks. Using 'before' or 'after'
     *      will enable only those callbacks.
     *   - `counterCache`: Boolean to control updating of counter caches (if any)
     *
     * @param array $fieldList List of fields to allow to be saved
     * @return mixed On success Model::$data if its not empty or true, false on failure
     */
    public function save($entity = null, $validate = true, $fieldList = [])
    {
        if (!is_object($entity) || !($entity instanceof $entity)) {
            $success = parent::save($entity, $validate, $fieldList);
            if (!$success) {
                return false;
            }

            $entity = $this->newEntity($success);
            $entity->isNew(false);
            return $entity;
        }

        if ($entity->isNew() === false && !$entity->dirty()) {
            return $entity;
        }

        if ($this->id != $entity->get($this->primaryKey)) {
            $this->create();
        }

        if (!$entity->dirty()) {
            return false;
        }

        if ($entity->isNew() === false) {
            $entity->unsetProperty(['modified', 'updated']);
        } else {
            $entity->unsetProperty(['created', 'modified', 'updated']);
        }

        $success = parent::save($entity, $validate, $fieldList);
        $insertedId = $this->getInsertID();

        if (!$success) {
            return false;
        }

        if (is_array($success)) {
            $alias = $this->alias();
            $dateFields = ['created', 'modified', 'updated'];
            foreach ($dateFields as $field) {
                $value = Hash::get($success, "{$alias}.{$field}", null);
                if ($value !== null) {
                    $entity->set($field, $value);
                }
            }
        }

        $entity->isNew(false);

        if (!empty($insertedId)) {
            $entity->set($this->primaryKey(), $this->getInsertID());
        }

        return $entity;
    }

    /**
     * Get the object used to marshal/convert array data into objects.
     *
     * Override this method if you want a table object to use custom
     * marshalling logic.
     *
     * @param bool $safe Whether or not this marshaller
     *   should be in safe mode.
     * @return Cake\ORM\Marhsaller;
     * @see Cake\ORM\Marshaller
     * @throws Exception Method 'marshaller' not implemented
     */
    public function marshaller($safe = false)
    {
        throw new Exception("Method 'marshaller' not implemented");
    }

    /**
     * Returns a new instance of an EntityValidator that is configured to be used
     * for entities generated by this table. An EntityValidator can be used to
     * process validation rules on a single or multiple entities and any of its
     * associated values.
     *
     * @return EntityValidator
     * @throws Exception Method 'entityValidator' not implemented
     */
    public function entityValidator()
    {
        throw new Exception("Method 'entityValidator' not implemented");
    }

    /**
     * Create a new entity + associated entities from an array.
     *
     * This is most useful when hydrating request data back into entities.
     * For example, in your controller code:
     *
     * {{{
     * $article = $this->Articles->newEntity($this->request->data());
     * }}}
     *
     * The hydrated entity will correctly do an insert/update based
     * on the primary key data existing in the database when the entity
     * is saved. Until the entity is saved, it will be a detached record.
     *
     * By default all the associations on this table will be hydrated. You can
     * limit which associations are built, or include deeper associations
     * using the associations parameter:
     *
     * {{{
     * $articles = $this->Articles->newEntity(
     *   $this->request->data(),
     *   ['Tags', 'Comments' => ['associated' => ['Users']]]
     * );
     * }}}
     *
     * @param array $data The data to build an entity with.
     * @param array $associations A whitelist of associations
     *   to hydrate. Defaults to all associations
     * @param bool $useSetters Whether or not to use entity
     *   set methods. Defaults to true
     * @return void
     * @throws Exception Method 'newEntity' not fully implemented
     */
    public function newEntity(array $data, $associations = null, $useSetters = true)
    {
        if ($associations !== null) {
            throw new Exception("Method 'newEntity' not fully implemented");
        }

        $class = $className = $this->entityClass();

        if (!class_exists($class)) {
            App::uses($class, $this->_EntityClassLocation());
            if (!class_exists($class)) {
                $class = 'Entity';
            }
        }

        $alias = $this->alias();
        if (!empty($data[$alias]) && !isset($data[$alias][0])) {
            $entityData = $data[$alias];
            foreach ($data as $key => $value) {
                if ($key != $alias) {
                    $entityData[$key] = $value;
                }
            }
            $data = $entityData;
        }

        $entity = new $class($data, [
            'className' => $className,
            'useSetters' => $useSetters,
        ]);
        return $entity;
    }

    /**
     * Create a list of entities + associated entities from an array.
     *
     * This is most useful when hydrating request data back into entities.
     * For example, in your controller code:
     *
     * {{{
     * $articles = $this->Articles->newEntities($this->request->data());
     * }}}
     *
     * The hydrated entities can then be iterated and saved. By default
     * all the associations on this table will be hydrated. You can
     * limit which associations are built, or include deeper associations
     * using the associations parameter:
     *
     * {{{
     * $articles = $this->Articles->newEntities(
     *   $this->request->data(),
     *   ['Tags', 'Comments' => ['associated' => ['Users']]]
     * );
     * }}}
     *
     * @param array $data The data to build an entity with.
     * @param array $associations A whitelist of associations
     *   to hydrate. Defaults to all associations
     * @return void
     * @throws Exception Method 'newEntities' not implemented
     */
    public function newEntities(array $data, $associations = null)
    {
        throw new Exception("Method 'newEntities' not implemented");
    }

    /**
     * Convert passed $data structure into coresponding entity object.
     *
     * @param array $data Hash to be converted. If omitted, $this->data will be converted.
     * @return Entity object
     */
    public function convertToEntity($data)
    {
        if (is_null($data) || empty($data[$this->alias][$this->primaryKey])) {
            return null;
        }

        $entity = $this->newEntity($data, null, false);
        $entity->isNew(false);
        $entity->clean();
        return $entity;
    }

    /**
     * Table::convertToEntities()
     *
     * @param mixed $list an array or list of data to convert into entities
     * @return array
     */
    public function convertToEntities($list)
    {
        if ($list && !Hash::numeric(array_keys($list))) {
            return $this->convertToEntity($list);
        }

        $result = [];
        foreach ($list as $data) {
            $result[] = $this->convertToEntity($data);
        }
        return $result;
    }

    /**
     * Table::beforeFind()
     *
     * @param array $query Data used to execute this query, i.e. conditions, order, etc.
     * @return mixed true if the operation should continue, false if it should abort; or, modified
     *  $query to continue with new $query
     * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforefind
     */
    public function beforeFind($query)
    {
        $this->_saveEntityState();

        if (isset($query['entity'])) {
            $this->entity = $query['entity'];
        }

        return parent::beforeFind($query);
    }

    /**
     * Table::afterFind()
     *
     * @param mixed $results The results of the find operation
     * @param bool $primary Whether this model is being queried directly (vs. being queried as an association)
     * @return mixed Result of the find operation
     */
    public function afterFind($results, $primary = false)
    {
        $results = parent::afterFind($results, $primary);

        if ($this->entity && $primary && is_array($results)) {
            $results = $this->convertToEntities($results);
        }

        $this->_restoreEntityState();
        return $results;
    }

    /**
     * Table::_saveEntityState()
     *
     * @return void
     */
    protected function _saveEntityState()
    {
        $this->_savedEntityStates[] = $this->entity;
    }

    /**
     * Table::_restoreEntityState()
     *
     * @return void
     */
    protected function _restoreEntityState()
    {
        $this->entity = array_pop($this->_savedEntityStates);
    }

    /**
     * Table::_entityClassForData()
     *
     * @param mixed $data array of data
     * @return string
     */
    protected function _entityClassForData($data)
    {
        return $this->entityClass();
    }

    /**
     * Table::allEntities()
     *
     * @param mixed $params Parameters for the method.
     * @return array
     */
    public function allEntities($params = [])
    {
        $params['entity'] = true;
        return $this->find('all', $params);
    }

    /**
     * Table::entities()
     *
     * @param mixed $params Parameters for the method.
     * @return array
     */
    public function entities($params = [])
    {
        return $this->allEntities($params);
    }

    /**
     * Table::__call()
     *
     * @param string $method Name of method to call.
     * @param array $params Parameters for the method.
     * @return mixed Whatever is returned by called method
     */
    public function __call($method, $params)
    {
        list($entity, $method) = $this->_analyzeMethodName($method);

        $return = parent::__call($method, $params);

        if ($entity && !is_null($return)) {
            $return = $this->convertToEntities($return);
        }

        return $return;
    }

    /**
     * Table::_analyzeMethodName()
     *
     * @param mixed $method name of method to analyze
     * @return array
     */
    protected function _analyzeMethodName($method)
    {
        $entity = false;

        if (preg_match('/^(entity|(?:all)?entities)by(.+)$/i', $method, $matches)) {
            $entity = true;
            $all = (strtolower($matches[1]) != 'entity');
            $method = ($all ? 'findAllBy' : 'findBy') . $matches[2];
        }

        return [$entity, $method];
    }

    /**
     * Override. To support passing entity to set() directly.
     * Because save() will pass its data to set(), you can now
     * call save() with entity like this:
     *
     *    $Model->save($entity);
     *
     * @param string|array|SimpleXmlElement|DomNode $one Array or string of data
     * @param string $two Value string for the alternative indata method
     * @return array Data with all of $one's keys and values
     * @link http://book.cakephp.org/2.0/en/models/saving-your-data.html
     */
    public function set($one, $two = null)
    {
        if ($one instanceof Entity) {
            $one = $one->toArray();
        }
        return parent::set($one, $two);
    }

    /**
     * Table::_EntityClassLocation()
     *
     * @return string
     */
    protected function _entityClassLocation()
    {
        $location = 'Model/Entity';
        if ($this->plugin !== null) {
            $location = $this->plugin . '.' . $location;
        }
        return $location;
    }

    /**
     * Ensures a _findCount call does not return entities
     *
     * @param string $state Either "before" or "after"
     * @param array $query Query.
     * @param array $results Results.
     * @return array
     * @see Model::find()
     */
    protected function _findCount($state, $query, $results = [])
    {
        if ($state == 'before') {
            $this->_saveEntityState();
            $this->entity = false;
            return parent::_findCount($state, $query, $results);
        }

        $return = parent::_findCount($state, $query, $results);
        $this->_restoreEntityState();
        return $return;
    }

    /**
     * Ensures a _findThreaded call does not return entities
     *
     * @param string $state Either "before" or "after"
     * @param array $query Query.
     * @param array $results Results.
     * @return array
     * @see Model::find()
     */
    protected function _findThreaded($state, $query, $results = [])
    {
        if ($state == 'before') {
            $this->_saveEntityState();
            $this->entity = false;
            return parent::_findThreaded($state, $query, $results);
        }

        $return = parent::_findThreaded($state, $query, $results);
        $this->_restoreEntityState();
        return $return;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        $conn = $this->connection();
        return [
            'table' => $this->table(),
            'alias' => $this->alias(),
            'entityClass' => $this->entityClass(),
            'associations' => $this->_associations->keys(),
            'behaviors' => $this->behaviors()->loaded(),
            'defaultConnection' => $this->defaultConnectionName(),
            'connectionName' => $conn ? $conn->configName() : null
        ];
    }

}
