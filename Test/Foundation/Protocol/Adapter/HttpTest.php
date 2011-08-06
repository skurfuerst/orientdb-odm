<?php

/**
 * HttpTest
 *
 * @package    Orient
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Orient\Test\Foundation\Protocol\Adapter;

use Orient\Test\PHPUnit\TestCase;
use Orient\Http\Client\Curl;
use Orient\Foundation\Protocol\Adapter\Http;

class HttpTest extends TestCase
{
    public function setUp()
    {
        $this->adapter = new Http(new Curl(), '127.0.0.1', 2480, 'admin', 'admin', 'demo');
    }
    
    public function testYouRetrieveAValidResponse()
    {
        $this->assertTrue($this->adapter->execute('SELECT FROM Address'));
    }
    
    /**
     * @todo verify the message of the exception
     * @expectedException \Exception
     */
    public function testAnExceptionIsRaisedWhenExecutingAMalformedQuery()
    {
        $this->adapter->execute('r gsdg ste gbt ');
    }
    
    /**
     * @todo verify the message of the exception
     * @expectedException \Exception
     */
    public function testAnExceptionIsRaisedWhenLookingForNonExistingStuff()
    {
        $this->adapter->execute('TRUNCATE CLASS OMNOMNOMN');
    }
}