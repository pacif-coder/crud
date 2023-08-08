<?php
namespace Crud\latte;

/**
 *
 */
class Compiler extends \Latte\Compiler
{
    public $globalUseClass = [];

    /**
     * Compiles tokens to PHP file
     * @param  Token[]  $tokens
     */
    public function compile(array $tokens, string $className, ?string $comment = null, bool $strictMode = false): string
    {
        $code = parent::compile($tokens, $className, $comment, $strictMode);

        $find = "use Latte\\Runtime as LR;\n";
        $replace = "";
        foreach ($this->globalUseClass as $key => $value) {
            if (is_int($key)) {
                $replace .= "use {$value};\n";
            } else {
                $replace .= "use {$key} as {$value};\n";
            }
        }

        return str_replace($find, $find . $replace, $code);
    }
}