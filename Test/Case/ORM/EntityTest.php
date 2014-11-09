<?php
App::uses('Entity', 'Entity.ORM');

class TestStudioEntity extends Entity {

}

/**
 * Entity test case.
 */
class EntityTest extends CakeTestCase {

/**
 * Tests setting a single property in an entity without custom setters
 *
 * @return void
 */
	public function testSetOneParamNoSetters() {
		$entity = new Entity;
		$entity->set('foo', 'bar');
		$this->assertEquals('bar', $entity->foo);

		$entity->set('foo', 'baz');
		$this->assertEquals('baz', $entity->foo);

		$entity->set('id', 1);
		$this->assertSame(1, $entity->id);
	}

/**
 * Tests setting multiple properties without custom setters
 *
 * @return void
 */
	public function testSetMultiplePropertiesNoSetters() {
		$entity = new Entity;
		$entity->accessible('*', true);

		$entity->set(['foo' => 'bar', 'id' => 1]);
		$this->assertEquals('bar', $entity->foo);
		$this->assertSame(1, $entity->id);

		$entity->set(['foo' => 'baz', 'id' => 2, 'thing' => 3]);
		$this->assertEquals('baz', $entity->foo);
		$this->assertSame(2, $entity->id);
		$this->assertSame(3, $entity->thing);
	}

/**
 * Tests setting a single property using a setter function
 *
 * @return void
 */
	public function testSetOneParamWithSetter() {
		$entity = $this->getMock('Entity', ['setName']);
		$entity->expects($this->once())->method('setName')
			->with('Jones')
			->will($this->returnCallback(function ($name) {
				$this->assertEquals('Jones', $name);
				return 'Dr. ' . $name;
			}));
		$entity->set('name', 'Jones');
		$this->assertEquals('Dr. Jones', $entity->name);
	}

/**
 * Tests setting multiple properties using a setter function
 *
 * @return void
 */
	public function testMultipleWithSetter() {
		$entity = $this->getMock('Entity', ['setName', 'setStuff']);
		$entity->accessible('*', true);
		$entity->expects($this->once())->method('setName')
			->with('Jones')
			->will($this->returnCallback(function($name) {
				$this->assertEquals('Jones', $name);
				return 'Dr. ' . $name;
			}));
		$entity->expects($this->once())->method('setStuff')
			->with(['a', 'b'])
			->will($this->returnCallback(function ($stuff) {
				$this->assertEquals(['a', 'b'], $stuff);
				return ['c', 'd'];
			}));
		$entity->set(['name' => 'Jones', 'stuff' => ['a', 'b']]);
		$this->assertEquals('Dr. Jones', $entity->name);
		$this->assertEquals(['c', 'd'], $entity->stuff);
	}

/**
 * Tests that it is possible to bypass the setters
 *
 * @return void
 */
	public function testBypassSetters() {
		$entity = $this->getMock('Entity', ['setName', 'setStuff']);
		$entity->accessible('*', true);

		$entity->expects($this->never())->method('setName');
		$entity->expects($this->never())->method('setStuff');

		$entity->set('name', 'Jones', ['setter' => false]);
		$this->assertEquals('Jones', $entity->name);

		$entity->set('stuff', 'Thing', ['setter' => false]);
		$this->assertEquals('Thing', $entity->stuff);

		$entity->set(['name' => 'foo', 'stuff' => 'bar'], ['setter' => false]);
		$this->assertEquals('bar', $entity->stuff);
	}

/**
 * Tests that the constructor will set initial properties
 *
 * @return void
 */
	public function testConstructor() {
		$entity = $this->getMockBuilder('Entity')
			->setMethods(['set'])
			->disableOriginalConstructor()
			->getMock();
		$entity->expects($this->at(0))
			->method('set')
			->with(['a' => 'b', 'c' => 'd'], ['setter' => true, 'guard' => false]);

		$entity->expects($this->at(1))
			->method('set')
			->with(['foo' => 'bar'], ['setter' => false, 'guard' => false]);

		$entity->__construct(['a' => 'b', 'c' => 'd']);
		$entity->__construct(['foo' => 'bar'], ['useSetters' => false]);
	}

/**
 * Tests that the constructor will set initial properties and pass the guard
 * option along
 *
 * @return void
 */
	public function testConstructorWithGuard() {
		$entity = $this->getMockBuilder('Entity')
			->setMethods(['set'])
			->disableOriginalConstructor()
			->getMock();
		$entity->expects($this->once())
			->method('set')
			->with(['foo' => 'bar'], ['setter' => true, 'guard' => true]);
		$entity->__construct(['foo' => 'bar'], ['guard' => true]);
	}

/**
 * Tests getting properties with no custom getters
 *
 * @return void
 */
	public function testGetNoGetters() {
		$entity = new Entity(['id' => 1, 'foo' => 'bar']);
		$this->assertSame(1, $entity->get('id'));
		$this->assertSame('bar', $entity->get('foo'));
	}

/**
 * Tests get with custom getter
 *
 * @return void
 */
	public function testGetCustomGetters() {
		$entity = $this->getMock('Entity', ['getName']);
		$entity->expects($this->once())->method('getName')
			->with('Jones')
			->will($this->returnCallback(function ($name) {
				$this->assertSame('Jones', $name);
				return 'Dr. ' . $name;
			}));
		$entity->set('name', 'Jones');
		$this->assertEquals('Dr. Jones', $entity->get('name'));
	}

/**
 * Test magic property setting with no custom setter
 *
 * @return void
 */
	public function testMagicSet() {
		$entity = new Entity;
		$entity->name = 'Jones';
		$this->assertEquals('Jones', $entity->name);
		$entity->name = 'George';
		$this->assertEquals('George', $entity->name);
	}

/**
 * Tests magic set with custom setter function
 *
 * @return void
 */
	public function testMagicSetWithSetter() {
		$entity = $this->getMock('Entity', ['setName']);
		$entity->expects($this->once())->method('setName')
			->with('Jones')
			->will($this->returnCallback(function ($name) {
				$this->assertEquals('Jones', $name);
				return 'Dr. ' . $name;
			}));
		$entity->name = 'Jones';
		$this->assertEquals('Dr. Jones', $entity->name);
	}

/**
 * Tests the magic getter with a custom getter function
 *
 * @return void
 */
	public function testMagicGetWithGetter() {
		$entity = $this->getMock('Entity', ['getName']);
		$entity->expects($this->once())->method('getName')
			->with('Jones')
			->will($this->returnCallback(function ($name) {
				$this->assertSame('Jones', $name);
				return 'Dr. ' . $name;
			}));
		$entity->set('name', 'Jones');
		$this->assertEquals('Dr. Jones', $entity->name);
	}

/**
 * Test indirectly modifying internal properties
 *
 * @return void
 */
	public function testIndirectModification() {
		$entity = new Entity;
		$entity->things = ['a', 'b'];
		$entity->things[] = 'c';
		$this->assertEquals(['a', 'b', 'c'], $entity->things);
	}

/**
 * Test indirectly modifying internal properties with a getter
 *
 * @return void
 */
	public function testIndirectModificationWithGetter() {
		$entity = $this->getMock('Entity', ['getThings']);
		$entity->expects($this->atLeastOnce())->method('getThings')
			->will($this->returnArgument(0));
		$entity->things = ['a', 'b'];
		$entity->things[] = 'c';
		$this->assertEquals(['a', 'b', 'c'], $entity->things);
	}

/**
 * Tests has() method
 *
 * @return void
 */
	public function testHas() {
		$entity = new Entity(['id' => 1, 'name' => 'Juan', 'foo' => null]);
		$this->assertTrue($entity->has('id'));
		$this->assertTrue($entity->has('name'));
		$this->assertFalse($entity->has('foo'));
		$this->assertFalse($entity->has('last_name'));

		$entity = $this->getMock('Entity', ['getThings']);
		$entity->expects($this->once())->method('getThings')
			->will($this->returnValue(0));
		$this->assertTrue($entity->has('things'));
	}

/**
 * Tests unsetProperty one property at a time
 *
 * @return void
 */
	public function testUnset() {
		$entity = new Entity(['id' => 1, 'name' => 'bar']);
		$entity->unsetProperty('id');
		$this->assertFalse($entity->has('id'));
		$this->assertTrue($entity->has('name'));
		$entity->unsetProperty('name');
		$this->assertFalse($entity->has('id'));
	}

/**
 * Tests unsetProperty whith multiple properties
 *
 * @return void
 */
	public function testUnsetMultiple() {
		$entity = new Entity(['id' => 1, 'name' => 'bar', 'thing' => 2]);
		$entity->unsetProperty(['id', 'thing']);
		$this->assertFalse($entity->has('id'));
		$this->assertTrue($entity->has('name'));
		$this->assertFalse($entity->has('thing'));
	}

/**
 * Tests the magic __isset() method
 *
 * @return void
 */
	public function testMagicIsset() {
		$entity = new Entity(['id' => 1, 'name' => 'Juan', 'foo' => null]);
		$this->assertTrue(isset($entity->id));
		$this->assertTrue(isset($entity->name));
		$this->assertFalse(isset($entity->foo));
		$this->assertFalse(isset($entity->thing));
	}

/**
 * Tests the magic __unset() method
 *
 * @return void
 */
	public function testMagicUnset() {
		$entity = $this->getMock('Entity', ['unsetProperty']);
		$entity->expects($this->at(0))
			->method('unsetProperty')
			->with('foo');
		unset($entity->foo);
	}

/**
 * Tests isset with array access
 *
 * @return void
 */
	public function testIssetArrayAccess() {
		$entity = new Entity(['id' => 1, 'name' => 'Juan', 'foo' => null]);
		$this->assertTrue(isset($entity['id']));
		$this->assertTrue(isset($entity['name']));
		$this->assertFalse(isset($entity['foo']));
		$this->assertFalse(isset($entity['thing']));
	}

/**
 * Tests get property with array access
 *
 * @return void
 */
	public function testGetArrayAccess() {
		$entity = $this->getMock('Entity', ['get']);
		$entity->expects($this->at(0))
			->method('get')
			->with('foo')
			->will($this->returnValue('worked'));

		$entity->expects($this->at(1))
			->method('get')
			->with('bar')
			->will($this->returnValue('worked too'));

		$this->assertEquals('worked', $entity['foo']);
		$this->assertEquals('worked too', $entity['bar']);
	}

/**
 * Tests set with array access
 *
 * @return void
 */
	public function testSetArrayAccess() {
		$entity = $this->getMock('Entity', ['set']);
		$entity->accessible('*', true);

		$entity->expects($this->at(0))
			->method('set')
			->with('foo', 1)
			->will($this->returnSelf());

		$entity->expects($this->at(1))
			->method('set')
			->with('bar', 2)
			->will($this->returnSelf());

		$entity['foo'] = 1;
		$entity['bar'] = 2;
	}

/**
 * Tests unset with array access
 *
 * @return void
 */
	public function testUnsetArrayAccess() {
		$entity = $this->getMock('Entity', ['unsetProperty']);
		$entity->expects($this->at(0))
			->method('unsetProperty')
			->with('foo');
		unset($entity['foo']);
	}

/**
 * Tests that the method cache will only report the methods for the called class,
 * this is, calling methods defined in another entity will not cause a fatal error
 * when trying to call directly an inexistent method in another class
 *
 * @return void
 */
	public function testMethodCache() {
		$entity = $this->getMock('Entity', ['setFoo', 'getBar']);
		$entity2 = $this->getMock('Entity', ['setBar']);
		$entity->expects($this->once())->method('setFoo');
		$entity->expects($this->once())->method('getBar');
		$entity2->expects($this->once())->method('setBar');

		$entity->set('foo', 1);
		$entity->get('bar');
		$entity2->set('bar', 1);
	}

/**
 * Tests that long properties in the entity are inflected correctly
 *
 * @return void
 */
	public function testSetGetLongProperyNames() {
		$entity = $this->getMock('Entity', ['getVeryLongProperty', 'setVeryLongProperty']);
		$entity->expects($this->once())->method('getVeryLongProperty');
		$entity->expects($this->once())->method('setVeryLongProperty');
		$entity->get('very_long_property');
		$entity->set('very_long_property', 1);
	}

/**
 * Tests serializing an entity as json
 *
 * @return void
 */
	public function testJsonSerialize() {
		$data = ['TestStudio' => ['name' => 'James', 'age' => 20, 'phones' => ['123', '457']]];
		$entity = new TestStudioEntity($data);
		$this->assertEquals(json_encode($data), json_encode($entity));
	}

/**
 * Tests the extract method
 *
 * @return void
 */
	public function testExtract() {
		$entity = new Entity([
			'id' => 1,
			'title' => 'Foo',
			'author_id' => 3
		]);
		$expected = ['author_id' => 3, 'title' => 'Foo', ];
		$this->assertEquals($expected, $entity->extract(['author_id', 'title']));

		$expected = ['id' => 1];
		$this->assertEquals($expected, $entity->extract(['id']));

		$expected = [];
		$this->assertEquals($expected, $entity->extract([]));

		$expected = ['id' => 1, 'crazyness' => null];
		$this->assertEquals($expected, $entity->extract(['id', 'crazyness']));
	}

/**
 * Tests dirty() method on a newly created object
 *
 * @return void
 */
	public function testDirty() {
		$entity = new Entity([
			'id' => 1,
			'title' => 'Foo',
			'author_id' => 3
		]);
		$this->assertTrue($entity->dirty('id'));
		$this->assertTrue($entity->dirty('title'));
		$this->assertTrue($entity->dirty('author_id'));

		$this->assertTrue($entity->dirty());

		$entity->dirty('id', false);
		$this->assertFalse($entity->dirty('id'));
		$this->assertTrue($entity->dirty('title'));
		$entity->dirty('title', false);
		$this->assertFalse($entity->dirty('title'));
		$this->assertTrue($entity->dirty());
		$entity->dirty('author_id', false);
		$this->assertFalse($entity->dirty());
	}

/**
 * Tests dirty() when altering properties values and adding new ones
 *
 * @return void
 */
	public function testDirtyChangingProperties() {
		$entity = new Entity([
			'title' => 'Foo',
		]);
		$entity->dirty('title', false);
		$this->assertFalse($entity->dirty('title'));
		$entity->set('title', 'Foo');
		$this->assertFalse($entity->dirty('title'));
		$entity->set('title', 'Foo');
		$this->assertFalse($entity->dirty('title'));
		$entity->set('title', 'Something Else');
		$this->assertTrue($entity->dirty('title'));

		$entity->set('something', 'else');
		$this->assertTrue($entity->dirty('something'));
	}

/**
 * Tests extract only dirty properties
 *
 * @return void
 */
	public function testExtractDirty() {
		$entity = new Entity([
			'id' => 1,
			'title' => 'Foo',
			'author_id' => 3
		]);
		$entity->dirty('id', false);
		$entity->dirty('title', false);
		$expected = ['author_id' => 3];
		$result = $entity->extract(['id', 'title', 'author_id'], true);
		$this->assertEquals($expected, $result);
	}

/**
 * Tests the clean method
 *
 * @return void
 */
	public function testClean() {
		$entity = new Entity([
			'id' => 1,
			'title' => 'Foo',
			'author_id' => 3
		]);
		$this->assertTrue($entity->dirty('id'));
		$this->assertTrue($entity->dirty('title'));
		$this->assertTrue($entity->dirty('author_id'));

		$entity->clean();
		$this->assertFalse($entity->dirty('id'));
		$this->assertFalse($entity->dirty('title'));
		$this->assertFalse($entity->dirty('author_id'));
	}

/**
 * Tests the isNew method
 *
 * @return void
 */
	public function testIsNew() {
		$entity = new Entity([
			'id' => 1,
			'title' => 'Foo',
			'author_id' => 3
		]);
		$this->assertNull($entity->isNew());
		$entity->isNew(true);
		$this->assertTrue($entity->isNew());
		$entity->isNew(false);
		$this->assertFalse($entity->isNew());
	}

/**
 * Tests the constructor when passing the markClean option
 *
 * @return void
 */
	public function testConstructorWithClean() {
		$entity = $this->getMockBuilder('Entity')
			->setMethods(['clean'])
			->disableOriginalConstructor()
			->getMock();
		$entity->expects($this->never())->method('clean');
		$entity->__construct(['a' => 'b', 'c' => 'd']);

		$entity = $this->getMockBuilder('Entity')
			->setMethods(['clean'])
			->disableOriginalConstructor()
			->getMock();
		$entity->expects($this->once())->method('clean');
		$entity->__construct(['a' => 'b', 'c' => 'd'], ['markClean' => true]);
	}

/**
 * Tests the constructor when passing the markClean option
 *
 * @return void
 */
	public function testConstructorWithMarkNew() {
		$entity = $this->getMockBuilder('Entity')
			->setMethods(['isNew'])
			->disableOriginalConstructor()
			->getMock();
		$entity->expects($this->never())->method('clean');
		$entity->__construct(['a' => 'b', 'c' => 'd']);

		$entity = $this->getMockBuilder('Entity')
			->setMethods(['isNew'])
			->disableOriginalConstructor()
			->getMock();
		$entity->expects($this->once())->method('isNew');
		$entity->__construct(['a' => 'b', 'c' => 'd'], ['markNew' => true]);
	}

/**
 * Test toArray method.
 *
 * @return void
 */
	public function testToArray() {
		$data = ['TestStudio' => ['name' => 'James', 'age' => 20, 'phones' => ['123', '457']]];
		$entity = new TestStudioEntity($data);

		$this->assertEquals($data, $entity->toArray());
	}

/**
 * Test toArray recursive.
 *
 * @return void
 */
	public function testToArrayRecursive() {
		$data = ['id' => 1, 'name' => 'James', 'age' => 20, 'phones' => ['123', '457']];
		$user = new TestStudioEntity($data);
		$comments = [
			new Entity(['user_id' => 1, 'body' => 'Comment 1']),
			new Entity(['user_id' => 1, 'body' => 'Comment 2']),
		];
		$user->comments = $comments;
		$user->profile = new Entity(['email' => 'mark@example.com']);

		$expected = [
			'TestStudio' => [
				'id' => 1,
				'name' => 'James',
				'age' => 20,
				'phones' => ['123', '457'],
			],
			'profile' => ['email' => 'mark@example.com'],
			'comments' => [
				['user_id' => 1, 'body' => 'Comment 1'],
				['user_id' => 1, 'body' => 'Comment 2'],
			]
		];
		$this->assertEquals($expected, $user->toArray());
	}

/**
 * Test that get accessors are called when converting to arrays.
 *
 * @return void
 */
	public function testToArrayWithAccessor() {
		$entity = $this->getMock('Entity', ['getName'], [], 'MockEntity');
		$entity->accessible('*', true);
		$entity->set(['name' => 'Mark', 'email' => 'mark@example.com']);
		$entity->expects($this->any())
			->method('getName')
			->will($this->returnValue('Jose'));

		$expected = ['Mock' => ['name' => 'Jose', 'email' => 'mark@example.com']];
		$this->assertEquals($expected, $entity->toArray());
	}

/**
 * Test that toArray respects hidden properties.
 *
 * @return void
 */
	public function testToArrayHiddenProperties() {
		$data = ['secret' => 'sauce', 'name' => 'mark', 'id' => 1];
		$entity = new TestStudioEntity($data);
		$entity->hiddenProperties(['secret']);
		$this->assertEquals(['TestStudio' => ['name' => 'mark', 'id' => 1]], $entity->toArray());
	}

/**
 * Test toArray includes 'virtual' properties.
 *
 * @return void
 */
	public function testToArrayVirtualProperties() {
		$entity = $this->getMock('Entity', ['getName'], [], 'MockEntity');
		$entity->accessible('*', true);

		$entity->expects($this->any())
			->method('getName')
			->will($this->returnValue('Jose'));
		$entity->set(['email' => 'mark@example.com']);

		$entity->virtualProperties(['name']);
		$expected = ['Mock' => ['name' => 'Jose', 'email' => 'mark@example.com']];
		$this->assertEquals($expected, $entity->toArray());

		$this->assertEquals(['name'], $entity->virtualProperties());

		$entity->hiddenProperties(['name']);
		$expected = ['Mock' => ['email' => 'mark@example.com']];
		$this->assertEquals($expected, $entity->toArray());
		$this->assertEquals(['name'], $entity->hiddenProperties());
	}

/**
 * Tests that missing fields will not be passed as null to the validator
 *
 * @return void
 */
	public function testValidateMissingFields() {
		$this->markTestIncomplete('Entity Validation not implemented');
		$entity = $this->getMockBuilder('Entity')
			->setMethods(['getSomething'])
			->disableOriginalConstructor()
			->getMock();
		$entity->accessible('*', true);

		$validator = $this->getMock('ModelValidator');
		$entity->set('a', 'b');

		$validator->expects($this->once())
			->method('provider')
			->with('entity', $entity);
		$validator->expects($this->once())->method('errors')
			->with(['a' => 'b'], true)
			->will($this->returnValue(['a' => ['not valid']]));
		$this->assertFalse($entity->validate($validator));
		$this->assertEquals(['a' => ['not valid']], $entity->errors());
	}

/**
 * Tests validate when the validator returns no errors
 *
 * @return void
 */
	public function testValidateSuccess() {
		$this->markTestIncomplete('Entity Validation not implemented');
		$validator = $this->getMock('ModelValidator');
		$data = [
			'a' => 'b',
			'cool' => false,
			'something' => true
		];
		$entity = new Entity($data);
		$entity->isNew(true);

		$validator->expects($this->once())
			->method('provider')
			->with('entity', $entity);
		$validator->expects($this->once())->method('errors')
			->with($data, true)
			->will($this->returnValue([]));
		$this->assertTrue($entity->validate($validator));
		$this->assertEquals([], $entity->errors());
	}

/**
 * Tests the errors method
 *
 * @return void
 */
	public function testErrors() {
		$entity = new Entity;
		$this->assertEmpty($entity->errors());
		$this->assertSame($entity, $entity->errors('foo', 'bar'));
		$this->assertEquals(['bar'], $entity->errors('foo'));

		$entity->errors('foo', 'other error');
		$this->assertEquals(['other error'], $entity->errors('foo'));

		$entity->errors('bar', ['something', 'bad']);
		$this->assertEquals(['something', 'bad'], $entity->errors('bar'));

		$expected = ['foo' => ['other error'], 'bar' => ['something', 'bad']];
		$this->assertEquals($expected, $entity->errors());

		$errors = ['foo' => ['something'], 'bar' => 'else', 'baz' => ['error']];
		$this->assertSame($entity, $entity->errors($errors));
		$errors['bar'] = ['else'];
		$this->assertEquals($errors, $entity->errors());
	}

/**
 * Tests that it is possible to get errors for nested entities
 *
 * @return void
 */
	public function testErrorsDeep() {
		$entity2 = new Entity;
		$entity3 = new Entity;
		$entity = new Entity([
			'foo' => 'bar',
			'thing' => 'baz',
			'user' => $entity2,
			'owner' => $entity3
		]);
		$entity->errors('thing', ['this is a mistake']);
		$entity2->errors(['a' => ['error1'], 'b' => ['error2']]);
		$entity3->errors(['c' => ['error3'], 'd' => ['error4']]);

		$expected = ['a' => ['error1'], 'b' => ['error2']];
		$this->assertEquals($expected, $entity->errors('user'));

		$expected = ['c' => ['error3'], 'd' => ['error4']];
		$this->assertEquals($expected, $entity->errors('owner'));

		$entity->set('multiple', [$entity2, $entity3]);
		$expected = [
			['a' => ['error1'], 'b' => ['error2']],
			['c' => ['error3'], 'd' => ['error4']]
		];
		$this->assertEquals($expected, $entity->errors('multiple'));
	}

/**
 * Tests that changing the value of a property will remove errors
 * stored for it
 *
 * @return void
 */
	public function testDirtyRemovesError() {
		$entity = new Entity(['a' => 'b']);
		$entity->errors('a', 'is not good');
		$entity->set('a', 'c');
		$this->assertEmpty($entity->errors('a'));

		$entity->errors('a', 'is not good');
		$entity->dirty('a', true);
		$this->assertEmpty($entity->errors('a'));
	}

/**
 * Tests that marking an entity as clean will remove errors too
 *
 * @return void
 */
	public function testCleanRemovesErrors() {
		$entity = new Entity(['a' => 'b']);
		$entity->errors('a', 'is not good');
		$entity->clean();
		$this->assertEmpty($entity->errors());
	}

/**
 * Tests accessible() method as a getter and setter
 *
 * @return void
 */
	public function testAccessible() {
		$entity = new Entity;
		$this->assertFalse($entity->accessible('foo'));
		$this->assertFalse($entity->accessible('bar'));

		$this->assertSame($entity, $entity->accessible('foo', true));
		$this->assertTrue($entity->accessible('foo'));
		$this->assertFalse($entity->accessible('bar'));

		$this->assertSame($entity, $entity->accessible('bar', true));
		$this->assertTrue($entity->accessible('foo'));
		$this->assertTrue($entity->accessible('bar'));

		$this->assertSame($entity, $entity->accessible('foo', false));
		$this->assertFalse($entity->accessible('foo'));
		$this->assertTrue($entity->accessible('bar'));

		$this->assertSame($entity, $entity->accessible('bar', false));
		$this->assertFalse($entity->accessible('foo'));
		$this->assertFalse($entity->accessible('bar'));
	}

/**
 * Tests that an array can be used to set
 *
 * @return void
 */
	public function testAccessibleAsArray() {
		$entity = new Entity;
		$entity->accessible(['foo', 'bar', 'baz'], true);
		$this->assertTrue($entity->accessible('foo'));
		$this->assertTrue($entity->accessible('bar'));
		$this->assertTrue($entity->accessible('baz'));

		$entity->accessible('foo', false);
		$this->assertFalse($entity->accessible('foo'));
		$this->assertTrue($entity->accessible('bar'));
		$this->assertTrue($entity->accessible('baz'));

		$entity->accessible(['foo', 'bar', 'baz'], false);
		$this->assertFalse($entity->accessible('foo'));
		$this->assertFalse($entity->accessible('bar'));
		$this->assertFalse($entity->accessible('baz'));
	}

/**
 * Tests that a wildcard can be used for setting accesible properties
 *
 * @return void
 */
	public function testAccessibleWildcard() {
		$entity = new Entity;
		$entity->accessible(['foo', 'bar', 'baz'], true);
		$this->assertTrue($entity->accessible('foo'));
		$this->assertTrue($entity->accessible('bar'));
		$this->assertTrue($entity->accessible('baz'));

		$entity->accessible('*', false);
		$this->assertFalse($entity->accessible('foo'));
		$this->assertFalse($entity->accessible('bar'));
		$this->assertFalse($entity->accessible('baz'));
		$this->assertFalse($entity->accessible('newOne'));

		$entity->accessible('*', true);
		$this->assertTrue($entity->accessible('foo'));
		$this->assertTrue($entity->accessible('bar'));
		$this->assertTrue($entity->accessible('baz'));
		$this->assertTrue($entity->accessible('newOne2'));
	}

/**
 * Tests that only accessible properties can be set
 *
 * @return void
 */
	public function testSetWithAccessible() {
		$entity = new Entity(['foo' => 1, 'bar' => 2]);
		$options = ['guard' => true];
		$entity->accessible('foo', true);
		$entity->set('bar', 3, $options);
		$entity->set('foo', 4, $options);
		$this->assertEquals(2, $entity->get('bar'));
		$this->assertEquals(4, $entity->get('foo'));

		$entity->accessible('bar', true);
		$entity->set('bar', 3, $options);
		$this->assertEquals(3, $entity->get('bar'));
	}

/**
 * Tests that only accessible properties can be set
 *
 * @return void
 */
	public function testSetWithAccessibleWithArray() {
		$entity = new Entity(['foo' => 1, 'bar' => 2]);
		$options = ['guard' => true];
		$entity->accessible('foo', true);
		$entity->set(['bar' => 3, 'foo' => 4], $options);
		$this->assertEquals(2, $entity->get('bar'));
		$this->assertEquals(4, $entity->get('foo'));

		$entity->accessible('bar', true);
		$entity->set(['bar' => 3, 'foo' => 5], $options);
		$this->assertEquals(3, $entity->get('bar'));
		$this->assertEquals(5, $entity->get('foo'));
	}

/**
 * Test that accessible() and single property setting works.
 *
 * @return
 */
	public function testSetWithAccessibleSingleProperty() {
		$entity = new Entity(['foo' => 1, 'bar' => 2]);
		$entity->accessible('title', true);

		$entity->set(['title' => 'test', 'body' => 'Nope']);
		$this->assertEquals('test', $entity->title);
		$this->assertNull($entity->body);

		$entity->body = 'Yep';
		$this->assertEquals('Yep', $entity->body, 'Single set should bypass guards.');

		$entity->set('body', 'Yes');
		$this->assertEquals('Yes', $entity->body, 'Single set should bypass guards.');
	}

}
