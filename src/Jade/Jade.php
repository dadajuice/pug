<?php

namespace Jade;

use Jade\Parser;
use Jade\Lexer;
use Jade\Compiler;

/**
 * Class Jade
 * @package Jade
 */
class Jade {
    /**
     * @var array
     */
    protected $options = [
        'cache'         => null,
        'stream'        => 'jade.stream',
        'extension'     => '.jade',
        'prettyprint'   => false
    ];

    /**
     * Built-in filters
     * @var array
     */
    protected $filters = array(
        'php'       => 'Jade\Filter\Php',
        'css'       => 'Jade\Filter\Css',
        'cdata'     => 'Jade\Filter\Cdata',
        'escaped'   => 'Jade\Filter\Escaped',
        'javascript'=> 'Jade\Filter\Javascript'
    );

    /**
     * Indicate if we registed the stream wrapper,
     * in order to not ask the stream registry each time
     * We need to render a template
     * @var bool
     */
    protected static $isWrapperRegistered = false;

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->options;
    }

    /**
     * register / override new filter
     * @param $name
     * @param $filter
     * @return $this
     */
    public function filter($name, $filter)
    {
       $this->filters[$name] = $filter;
       return $this;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasFilter($name)
    {
        return array_key_exists($name, $this->filters);
    }

    /**
     * @param $input
     * @return string
     */
    public function compile($input)
    {
        $parser     = new Parser($input, null, $this->options['extension']);
        $compiler   = new Compiler($this->options['prettyprint'], $this->filters);

        return $compiler->compile($parser->parse($input));
    }

    /**
     * @param $input
     * @param array $vars
     * @return mixed|string
     */
    public function render($input, array $vars = array())
    {
        $file = $this->options['cache'] ? $this->cache($input) : $this->stream($input);

        extract($vars);
        return include $file;
    }

    /**
     * Create a stream wrapper to allow
     * the possibility to add $scope variables
     * @param $input
     * @return string
     */
    public function stream($input)
    {
        if (false === static::$isWrapperRegistered)
        {
            static::$isWrapperRegistered = true;
            stream_wrapper_register($this->options['stream'], 'Jade\Stream\Template');
        }
        return $this->options['stream'].'://data;'.$this->compile($input);
    }


    /**
     * @param $input
     * @return mixed|string
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function cache($input)
    {
        if (! is_file($input))
        {
            throw new \InvalidArgumentException('Only files can be cached.');
        }

        $cacheFolder = $this->options['cache'];

        if (! is_dir($cacheFolder))
        {
            throw new \Exception('You must provide correct cache path to Jade for caching.');
        }

        $path = str_replace('//', '/', $cacheFolder . '/' . md5($input) . '.php');
        $cacheTime = ! file_exists($path) ? 0 : filemtime($path);

        // Do not re-parse file if original is older
        if ( $cacheTime && filemtime($input) < $cacheTime )
        {
            return $path;
        }
        if (! is_writable($cacheFolder))
        {
            throw new \Exception(sprintf('Cache directory must be writable. "%s" is not.', $cacheFolder));
        }

        $rendered = $this->compile($input);
        file_put_contents($path, $rendered);

        return $this->stream($rendered);
    }
}
