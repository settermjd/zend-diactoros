<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Diactoros\Response;

use Psr\Http\Message\StreamInterface;
use Traversable;
use Zend\Diactoros\Exception;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

use function get_class;
use function gettype;
use function is_object;
use function is_string;
use function sprintf;

/**
 * CSV response.
 *
 * Allows creating a CSV response by passing a string to the constructor;
 * by default, sets a status code of 200 and sets the Content-Type header to
 * text/csv.
 */
class CsvResponse extends DownloadResponse
{
    use InjectContentTypeTrait;

    const DEFAULT_LINE_ENDING = "\n";
    const DEFAULT_SEPARATOR = ',';

    /**
     * @var array
     */
    private $options;

    /**
     * Create a CSV response.
     *
     * Produces a CSV response with a Content-Type of text/csv and a default
     * status of 200.
     *
     * @param array|string|StreamInterface $text String or stream for the message body.
     * @param array $options
     * @param int $status Integer status code for the response; 200 by default.
     * @param string $filename
     * @param array $headers Array of headers to use at initialization.
     */
    public function __construct($text, array $options = [], int $status = 200, string $filename = '', array $headers = [])
    {
        if ($filename !== '') {
            $headers = $this->prepareDownloadHeaders($filename, $headers);
        }

        $this->options = $options;

        parent::__construct(
            $this->createBody($text),
            $status,
            $this->injectContentType('text/csv; charset=utf-8', $headers)
        );
    }

    /**
     * Create the body of the CSV response
     * @param string|StreamInterface $text
     * @return StreamInterface
     * @throws Exception\InvalidArgumentException if $text is neither a string or stream.
     */
    private function createBody($text) : StreamInterface
    {
        $body = null;

        if (is_string($text)) {
            $body = $this->createBodyFromString($text);
        }

        if ($text instanceof StreamInterface) {
            $body = $text;
        }

        if (is_array($text) | $text instanceof Traversable) {
            $body = $this->createBodyFromIterable($text);
        }

        return $body;
    }

    /**
     * Create the CSV message body from a CSV string.
     *
     * @param string $text
     * @return StreamInterface
     * @throws Exception\InvalidArgumentException if $text is neither a string or stream.
     */
    private function createBodyFromString(string $text) : StreamInterface
    {
        if (empty($text)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid CSV content (%s) provided to %s',
                (is_object($text) ? get_class($text) : gettype($text)),
                __CLASS__
            ));
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($text);
        $body->rewind();
        return $body;
    }

    /**
     * Create the CSV response body from the contents of an array
     * @param array|Traversable $text
     * @return StreamInterface
     */
    public function createBodyFromIterable($text) : StreamInterface
    {
        $body = new Stream('php://temp', 'wb+');
        $last = end($text);
        reset($text);

        foreach ($text as $row) {
            $body->write($this->getRecord($row, $last));
        }
        $body->rewind();

        return $body;
    }

    /**
     * Get a fully rendered CSV record
     * @param array $row
     * @param array $last
     * @return string
     */
    public function getRecord(array $row, array $last): string
    {
        $lineEnding = $this->getLineEnding($row, $last);
        $row = implode($this->options['field_separator'] ?? self::DEFAULT_SEPARATOR, $row);

        return $row . $lineEnding;
    }

    /**
     * Is the current row the last one
     * @param array $current
     * @param array $last
     * @return bool
     */
    public function isLastLine($current, $last)
    {
        return ($current == $last);
    }

    /**
     * @param array $row
     * @param array $last
     * @return string
     */
    public function getLineEnding(array $row, array $last): string
    {
        $lineEnding = ($this->isLastLine($row, $last))
            ? ''
            : $this->options['line_ending'] ?? self::DEFAULT_LINE_ENDING;

        return $lineEnding;
    }
}
