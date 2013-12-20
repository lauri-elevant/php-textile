<?php

namespace Netcarver\Textile\Test;

use Symfony\Component\Yaml\Yaml;
use Netcarver\Textile\Parser as Textile;
use Netcarver\Textile\Tag;

class BasicTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     */

    public function testAdd($name, $test)
    {
        if (isset($test['doctype'])) {
            $textile = new Textile($test['doctype']);
        } else {
            $textile = new Textile();
        }

        if (isset($test['setup'][0])) {
            foreach ($test['setup'][0] as $method => $value) {
                $textile->$method($value);
            }
        }

        if (isset($test['method'])) {
            $method = trim($test['method']);
        } else {
            $method = 'textileThis';
        }

        if (isset($test['arguments'][0])) {
            $args = array_values($test['arguments'][0]);
        } else {
            $args = array();
        }

        $expect = rtrim($test['expect']);
        array_unshift($args, $test['input']);
        $input = rtrim(call_user_func_array(array($textile, $method), $args));

        foreach (array('expect', 'input') as $variable) {
            $$variable = preg_replace(
                array(
                    '/ id="(fn|note)[a-z0-9\-]*"/',
                    '/ href="#(fn|note)[a-z0-9\-]*"/',
                ),
                '',
                $$variable
            );
        }

        $this->assertEquals($expect, $input, 'In section: '.$name);
        $public = implode(', ', array_keys(get_object_vars($textile)));
        $this->assertEquals('', $public, 'Leaking public class properties.');
    }

    public function testGetVersion()
    {
        $textile = new Textile();
        $this->assertRegExp(
            '/^[0-9]+\.[0-9]+\.[0-9]+(:?-[A-Za-z0-9.]+)?(?:\+[A-Za-z0-9.]+)?$/',
            $textile->getVersion()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */

    public function testInvalidSymbol()
    {
        $textile = new Textile();
        $textile->getSymbol('invalidSymbolName');
    }

    public function testSetGetSymbol()
    {
        $textile = new Textile();
        $this->assertEquals('TestValue', $textile->setSymbol('test', 'TestValue')->getSymbol('test'));
        $this->assertArrayHasKey('test', $textile->getSymbol());
    }

    public function testSetRelativeImagePrefixChaining()
    {
        $textile = new Textile();
        $symbol = $textile->setRelativeImagePrefix('abc')->setSymbol('test', 'TestValue')->getSymbol('test');
        $this->assertEquals('TestValue', $symbol);
    }

    public function testSetGetDimensionlessImage()
    {
        $textile = new Textile();
        $this->assertFalse($textile->getDimensionlessImages());
        $this->assertTrue($textile->setDimensionlessImages(true)->getDimensionlessImages());
    }

    public function testEncode()
    {
        $textile = new Textile();
        $encoded = $textile->textileEncode('& &amp; &#124; &#x0022 &#x0022;');
        $this->assertEquals('&amp; &amp; &#124; &amp;#x0022 &#x0022;', $encoded);
    }

    public function provider()
    {
        chdir(dirname(dirname(dirname(__DIR__))));
        $out = array();

        if ($files = glob('*/*.yaml')) {
            foreach ($files as $file) {
                $yaml = Yaml::parse($file);

                foreach ($yaml as $name => $test) {
                    if (!isset($test['input']) || !isset($test['expect'])) {
                        continue;
                    }

                    if (isset($test['assert']) && $test['assert'] === 'skip') {
                        continue;
                    }

                    $out[] = array($name, $test);
                }
            }
        }

        return $out;
    }

    public function testTagAttributesGenerator()
    {
        $attributes = new Tag(null, array('name' => 'value'));
        $this->assertEquals(' name="value"', (string) $attributes);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */

    public function testDeprecatedEncodingArgument()
    {
        $parser = new Textile();
        $parser->textileThis('content', false, true);
    }
}
