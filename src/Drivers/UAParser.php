<?php

namespace Shetabit\Visitor\Drivers;

use Illuminate\Http\Request;
use Shetabit\Visitor\Contracts\UserAgentParser;
use UAParser\Exception\FileNotFoundException;
use UAParser\Parser;
use UAParser\Result\Client;

class UAParser implements UserAgentParser
{
    /**
     * Request container.
     */
    protected Request $request;

    /**
     * Agent parser.
     */
    protected Client $parser;

    /**
     * UAParser constructor.
     *
     *
     * @throws FileNotFoundException
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->parser = $this->initParser();
    }

    /**
     * Retrieve device's name.
     */
    public function device(): string
    {
        return $this->parser->device->family;
    }

    /**
     * Retrieve platform's name.
     */
    public function platform(): string
    {
        return $this->parser->os->family;
    }

    /**
     * Retrieve browser's name.
     */
    public function browser(): string
    {
        return $this->parser->ua->family;
    }

    /**
     * Retrieve languages.
     */
    public function languages(): array
    {
        $languages = [];

        if (! empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $languages[] = $lang;
        }

        return $languages;
    }

    /**
     * Initialize userAgent parser.
     *
     * @throws FileNotFoundException
     */
    protected function initParser(): Client
    {
        return Parser::create()->parse($this->request->userAgent());
    }
}
