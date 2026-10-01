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
namespace Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Logger;

use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Deprecation;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\StackTrace\Trace;
use Creationell\WpTheme\Vendor\SourceSpan\FileSpan;
use Creationell\WpTheme\Vendor\SourceSpan\SourceSpan;
/**
 * A logger that silently ignores all messages.
 */
final class QuietLogger implements LoggerInterface
{
    public function warn(string $message, ?Deprecation $deprecation = null, ?FileSpan $span = null, ?Trace $trace = null): void
    {
    }
    public function debug(string $message, SourceSpan $span): void
    {
    }
}
