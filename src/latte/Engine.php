<?php
namespace Crud\latte;

use Crud\latte\Compiler;

use Latte\Macros\CoreMacros;
use Latte\Macros\BlockMacros;

/**
 * Description of Engine
 *
 * @author pacif
 */
class Engine extends \Latte\Engine
{
    /** @var Compiler|null */
    protected $compiler;

    public function getCompiler(): \Latte\Compiler
    {
        if ($this->compiler) {
            return $this->compiler;
        }

        $this->compiler = new Compiler;

        CoreMacros::install($this->compiler);
        BlockMacros::install($this->compiler);

        return $this->compiler;
    }
}
