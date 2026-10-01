<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Parser;

use Creationell\WpTheme\Vendor\JiriPudil\SealedClasses\Sealed;
use Creationell\WpTheme\Vendor\SourceSpan\FileSpan;
/**
 * @internal
 */
#[Sealed([MultiSourceFormatException::class])]
class FormatException extends \Exception
{
    private readonly FileSpan $span;
    public function __construct(string $message, FileSpan $span, ?\Throwable $previous = null)
    {
        $this->span = $span;
        parent::__construct($message, 0, $previous);
    }
    public function getSpan(): FileSpan
    {
        return $this->span;
    }
}
