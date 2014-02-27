<?php

/*
 * (c) Alexandre Quercia <alquerci@email.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Instinct\BC\Tests;

use Instinct\BC\Converter;

/**
 * @author Alexandre Quercia <alquerci@email.com>
 */
class ConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $code = <<<'EOF'
<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * use Foo;
 * don't class Foo;
 * class Bar;
 * namespace Foo;
 * __DIR__
 */

namespace Baz\Booz;

use Foo\Bar;
use Foo\Baz;
use Bar\Baz;
use Foo\Bar\Booz as BaseBooz;

class FooBar extends Bar
{
    /**
     * use Foo;
     * don't class Foo;
     * don"t class Bar;
     * namespace Foo;
     * @covers Bar\Foo::setClass
     * @covers \Bar\Foo::BaseBooz
     * @covers \Bar\Foo::Foo
     * __DIR__
     *
     * @return Foo
     */
    public function Foo(Foo $Foo = "\'",Foo $Foo = '\'')
    {
        // use Foo;
        // class Foo;
        // class Bar;
        // namespace Foo;
        // __DIR__
        $Bar = new \Bar('Bar\Foo');
        $Bar = new Bar('Bar\Foo');
        $Bar = Bar::FOO;Bar::foo();
        $Bar = FooBar::FOO;FooBar::foo();
        echo (FooBar::FOO),FooBar::BAR;
        $interface = __DIR__;
        $class = __NAMESPACE__;
        $Bar->class = 'foo';
        $Bar->interface = 'foo';

        if ($Bar instanceof \Bar) {
            $Bar->Foo = $Foo->Bar;
        }

        return new Foo();
    }
}

interface FooInterface
{
}

class Foo extends BaseBooz implements FooInterface
{
    /**
     * @api
     */
    public function Bar()
    {
    }
}

\$b = <<<'TEXT'
use Bar\Foo;
namespace Bar\Foo;
Foo TEXT
Bar
TEXT;
EOF;

        $result = <<<'EOF'
<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * use Foo;
 * don't class Foo;
 * class Bar;
 * namespace Foo;
 * __DIR__
 */

class Baz_Booz_FooBar extends \Foo_Bar
{
    /**
     * use Foo;
     * don't class \Baz_Booz_Foo;
     * don"t class \Foo_Bar;
     * namespace Foo;
     * @covers \Foo_Bar_Foo::setClass
     * @covers \Bar\Foo::BaseBooz
     * @covers \Bar\Foo::Foo
     * __DIR__
     *
     * @return \Baz_Booz_Foo
     */
    public function Foo(\Baz_Booz_Foo $Foo = "\'",\Baz_Booz_Foo $Foo = '\'')
    {
        // use Foo;
        // class Foo;
        // class Bar;
        // namespace Foo;
        // __DIR__
        $Bar = new \Bar('Bar\Foo');
        $Bar = new \Foo_Bar('Bar\Foo');
        $Bar = \Foo_Bar::FOO;\Foo_Bar::foo();
        $Bar = \Baz_Booz_FooBar::FOO;\Baz_Booz_FooBar::foo();
        echo (\Baz_Booz_FooBar::FOO),\Baz_Booz_FooBar::BAR;
        $interface = dirname(__FILE__);
        $class = 'Baz_Booz';
        $Bar->class = 'foo';
        $Bar->interface = 'foo';

        if ($Bar instanceof \Bar) {
            $Bar->Foo = $Foo->Bar;
        }

        return new \Baz_Booz_Foo();
    }
}

interface Baz_Booz_FooInterface
{
}

class Baz_Booz_Foo extends \Foo_Bar_Booz implements \Baz_Booz_FooInterface
{
    /**
     * @api
     */
    public function Bar()
    {
    }
}

\$b = <<<'TEXT'
use Bar\Foo;
namespace Bar\Foo;
Foo TEXT
Bar
TEXT;
EOF;

        $converter = new Converter();

        $this->assertEquals($result, $converter->convert($code));
    }
}
