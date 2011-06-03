<?php

/**
 * QueryTest
 *
 * @package    Orient
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Orient\Test;

use Orient\Test\PHPUnit\TestCase;
use Orient\Query;
use Orient\Query\Command\Select;

class StubQuery extends Query
{
    public function aMethodCallingABadCommand()
    {
        return $this->getCommandClass('OMN NO NO ON ON ');
    }
}

class QueryTest extends TestCase
{
    public function setup()
    {
        $this->query = new Query();
    }

    public function testQueryImplementsAGenericInterface()
    {
        $this->assertInstanceOf("\Orient\Contract\Query", $this->query);
    }

    public function testDataFiltering()
    {
        $this->query->where('username = ?', "\"admin\"", false);
        $sql =
                'SELECT FROM WHERE username = "\"admin\""'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->where('username = ?', "'admin'", false);
        $sql =
                "SELECT FROM WHERE username = \"\'admin\'\"";
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->insert(array('field'))->values(array('value'))->into('class');
        $sql =
                'INSERT INTO class () VALUES ("value")'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query = new Query();
        $this->query->where('any() traverse ( any() like "%danger%" )', null, false);
        $sql =
                'SELECT FROM WHERE any() traverse ( any() like "%danger%" )'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->where('1 = ?', '1; DELETE FROM class', false);
        $sql =
                'SELECT FROM WHERE 1 = "1; DELETE FROM class"'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->where('1 = ?', '1"; DELETE FROM class', false);
        $sql =
                'SELECT FROM WHERE 1 = "1\"; DELETE FROM class"'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->from(array('users; DELETE FROM class'), false)->resetWhere();
        $sql =
                'SELECT FROM usersDELETEFROMclass'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->from(array('users-- DELETE FROM class'), false)->resetWhere();
        $sql =
                'SELECT FROM usersDELETEFROMclass'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->from(array('class'), false)->where('class = ?', ";");
        $sql =
                'SELECT FROM class WHERE class = ";"'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->from(array('class'), false)->where('class = ?', "--");
        $sql =
                'SELECT FROM class WHERE class = "--"'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());
    }

    public function testSelect()
    {
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Select', $this->query->select(array()));
    }

    public function testYouCanResetAllTheWheresOfAQuery()
    {
        $this->query = new Query(array('myClass'));
        $this->query->where('the sky = ?', 'blue');
        $this->query->resetWhere();
        $sql =
                'SELECT FROM myClass'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());
    }

    public function testInsert()
    {
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Insert', $this->query->insert());
    }

    public function testFields()
    {
        $this->query->insert();
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Insert', $this->query->fields(array()));
    }

    public function testInto()
    {
        $this->query->insert();
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Insert', $this->query->into('class'));
    }

    public function testRange()
    {
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Select', $this->query->range('12', '14'));
    }

    public function testValues()
    {
        $this->query->insert();
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Insert', $this->query->values(array()));
    }

    public function testTo()
    {
        $this->query->grant('a');
        $this->assertInstanceOf('\Orient\Query\Command\Credential\Grant', $this->query->to('c'));
    }

    public function testGrant()
    {
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Credential', $this->query->grant('p'));
        $this->assertInstanceOf('\Orient\Query\Command\Credential\Grant', $this->query->grant('p'));
    }

    public function testYouCanCreateARevoke()
    {
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Credential', $this->query->revoke('p'));
        $this->assertInstanceOf('\Orient\Query\Command\Credential\Revoke', $this->query->revoke('p'));
    }

    public function testCreationOfAClass()
    {
        $this->assertInstanceOf('\Orient\Query\Command\OClass\Create', $this->query->create('p'));
        $this->assertInstanceOf('\Orient\Contract\Query\Command\OClass', $this->query->create('p'));
    }

    public function testRemovalOfAClass()
    {
        $this->assertInstanceOf('\Orient\Query\Command\OClass\Drop', $this->query->drop('p'));
        $this->assertInstanceOf('\Orient\Contract\Query\Command\OClass', $this->query->drop('p'));
    }

    public function testRemovalOfAProperty()
    {
        $this->assertInstanceOf('\Orient\Query\Command\Property\Drop', $this->query->drop('p', 'h'));
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Property', $this->query->drop('p', 'h'));


        $this->query = new Query();
        $this->query->drop("read", "hallo");
        $sql =
                'DROP PROPERTY read.hallo'
        ;

        $this->assertEquals($sql, $this->query->getRaw());

        $this->query->drop("run", "forrest")->on('c');
        $sql =
                'DROP PROPERTY c.forrest'
        ;

        $this->assertEquals($sql, $this->query->getRaw());
    }

    public function testCreationOfAProperty()
    {
        $this->assertInstanceOf('\Orient\Query\Command\Property\Create', $this->query->create('p', 'h'));
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Property', $this->query->create('p', 'h'));

        $this->query->create("read", "hallo", "type");
        $sql =
                'CREATE PROPERTY read.hallo type'
        ;

        $this->assertEquals($sql, $this->query->getRaw());

        $this->query->create("run", "forrest", "type", "Friend");
        $sql =
                'CREATE PROPERTY run.forrest type Friend'
        ;

        $this->assertEquals($sql, $this->query->getRaw());
    }

    public function testFindReferences()
    {
        $this->assertInstanceOf('\Orient\Query\Command\Reference\Find', $this->query->findReferences("1:1"));
        //$this->assertInstanceOf('\Orient\Contract\Query\Command\Reference\Find', $this->query->findReferences("1:1"));

        $this->query->in(array('class2', 'cluster:class3'));
        $sql =
                'FIND REFERENCES 1:1 [class2, cluster:class3]'
        ;

        $this->assertEquals($sql, $this->query->getRaw());
    }

    public function testDroppingAnIndex()
    {
        $this->assertInstanceOf('\Orient\Query\Command\Index\Drop', $this->query->unindex("class", "property"));
        //$this->assertInstanceOf('\Orient\Contract\Query\Command\Index', $this->query->unindex("class", "property"));

        $this->query->unindex("property", "class");
        $sql =
                'DROP INDEX class.property'
        ;

        $this->assertEquals($sql, $this->query->getRaw());

        $this->query->unindex("property");
        $sql =
                'DROP INDEX property'
        ;

        $this->assertEquals($sql, $this->query->getRaw());
    }

    public function testCreatingAnIndex()
    {
        $this->assertInstanceOf('\Orient\Query\Command\Index\Create', $this->query->index("class", "property"));
        //$this->assertInstanceOf('\Orient\Contract\Query\Command\Index', $this->query->index("class", "property"));

        $this->query->index("property", "class");
        $sql =
                'CREATE INDEX class.property'
        ;

        $this->assertEquals($sql, $this->query->getRaw());

        $this->query->index("property")->type('string');
        $sql =
                'CREATE INDEX property string'
        ;

        $this->assertEquals($sql, $this->query->getRaw());

        $this->query->index("property");
        $this->query->type('string');
        $sql =
                'CREATE INDEX property string'
        ;

        $this->assertEquals($sql, $this->query->getRaw());

        $this->query->index("property", NULL, 'string');
        $sql =
                'CREATE INDEX property string'
        ;

        $this->assertEquals($sql, $this->query->getRaw());
    }

    public function testDeleteSQLQuery()
    {
        $this->query->delete("Profile")
                ->where("1 = ?", 1)
                ->andWhere("links = ?", 1);

        $sql =
                'DELETE FROM Profile WHERE 1 = "1" AND links = "1"'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());
    }

    public function testCreatingALink()
    {
        $this->query->link('class', "property", "Profile")->to("class2", "property2");

        $sql =
                'CREATE LINK Profile FROM class.property TO class2.property2'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->link('class', "property", "Profile", true)->to("class2", "property2");

        $sql =
                'CREATE LINK Profile FROM class.property TO class2.property2 INVERSE'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());
    }

    public function testUpdating()
    {
        $this->query->update('class')
                ->set(array('first' => 'uno', 'nano' => 'due'))
                ->where('prop = ?', 'val');

        $sql =
                'UPDATE class SET first = "uno", nano = "due" WHERE prop = "val"'
        ;

        $this->query->put(array('first' => '1', 'second' => '2'), 'puttedClass');

        $sql =
                'UPDATE puttedClass PUT first = "1", second = "2"'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->add(array('first' => '10:22', 'nano' => '10:1'), 'class')
                ->where('prop = ?', 'val');

        $sql =
                'UPDATE class ADD first = 10:22, nano = 10:1 WHERE prop = "val"'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());

        $this->query->remove(array('first' => 'uno', 'nano' => 'due'), 'class')
                ->where('prop = ?', 'val');

        $sql =
                'UPDATE class REMOVE first = "uno", nano = "due" WHERE prop = "val"'
        ;

        $this->assertCommandGives($sql, $this->query->getRaw());
    }

    public function testWheres()
    {
        $this->query->from(array('class'));
        $this->query->where('1 = ?', 1);
        $this->query->andWhere('1 = ?', 1);
        $this->query->orWhere("1 = ?", 1);
        $sql = 'SELECT FROM class WHERE 1 = "1" AND 1 = "1" OR 1 = "1"';

        $this->assertCommandGives($sql, $this->query->getRaw());
    }

    public function testLimit()
    {
        $this->assertInstanceOf('\Orient\Contract\Query\Command\Select', $this->query->limit(20));

        $this->query->from(array('class'))->limit(10);
        $sql = 'SELECT FROM class LIMIT 10';

        $this->assertCommandGives($sql, $this->query->getRaw());
    }

    public function testOn()
    {
        $this->assertInstanceOf('\Orient\Query\Command\Property\Create', $this->query->create('p', 'c'));
        $this->assertInstanceOf('\Orient\Query\Command\Property\Create', $this->query->on('p', 'f'));

        $this->assertInstanceOf('\Orient\Query\Command\Property\Drop', $this->query->drop('p', 'c'));
        $this->assertInstanceOf('\Orient\Query\Command\Property\Drop', $this->query->on('c', 'c'));

        $sql = 'DROP PROPERTY c.c';

        $this->assertCommandGives($sql, $this->query->getRaw());
    }

    public function testAlter()
    {
        $this->assertInstanceOf('\Orient\Query\Command\OClass\Alter', $this->query->alter('c', 'p', 'v'));

        $sql = 'ALTER CLASS c p v';

        $this->assertCommandGives($sql, $this->query->alter('c', 'p', 'v')->getRaw());
    }

    public function testAlterProperty()
    {
        $this->assertInstanceOf('\Orient\Query\Command\Property\Alter', $this->query->alterProperty('p', 'c', 'a', 'v'));

        $sql = 'ALTER PROPERTY c.p a v';

        $this->assertCommandGives($sql, $this->query->alterProperty('c', 'p', 'a', 'v')->getRaw());
    }

    public function testOrderBy()
    {
        $this->query->from(array('class'));
        $this->query->orderBy('A B');
        $sql = 'SELECT FROM class ORDER BY A B';

        $this->assertCommandGives($sql, $this->query->getRaw());
    }

    public function testPuttingANewIndex()
    {
        $this->assertInstanceOf('\Orient\Query\Command\Index\Put', $this->query->indexPut('i', 'k', 'v'));
    }

    /**
     * @expectedException Orient\Exception
     */
    public function testAnExceptionIsRaisedWhenTryingToAccessANonExistingMethod()
    {
        $query = new StubQuery();
        $query->aMethodCallingABadCommand();
    }

    public function testTokens()
    {
        $this->assertCommandGives(Select::getTokens(), $this->query->getTokens());
    }
}
