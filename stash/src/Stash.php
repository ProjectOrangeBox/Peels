<?php

declare(strict_types=1);

namespace peels\stash;

use peels\stash\StashInterface;
use peels\session\SessionInterface;
use orange\framework\base\Singleton;
use orange\framework\interfaces\InputInterface;

class Stash extends Singleton implements StashInterface
{
    protected InputInterface $input;
    protected SessionInterface $session;

    protected string $stashKey = '__#stash#__';

    protected function __construct(SessionInterface $session, InputInterface $input)
    {
        $this->session = $session;
        $this->input = $input;
    }

    /**
     * Store the current request data in the session
     * under a specific key
     * return $this for chaining
     * If a name is provided, use that as part of the key
     * otherwise use a default key
     * The stashed data includes:
     * - request data (POST)
     * - query data (GET)
     * - cookie data
     * - file data (FILES)
     * - server data
     * - header data
     * This allows for restoring the request later
     * with all its components
     * Example usage:
     * $stash->push('my_form');
     * Later, to retrieve:
     * $data = $stash->apply('my_form');
     * if ($data !== false) {
     *     // restore the request data
     *     $requestData = $data['request'];
     *     $queryData = $data['query'];
     *     // etc.
     * }
     * If no data was stashed under that name, apply returns false
     * otherwise it returns an array with the stashed data
     * The stashed data is removed from the session after being applied
     * to prevent reuse
     * This is useful for one-time redirects after form submissions
     * where you want to preserve the input data across the redirect
     * but not keep it around indefinitely
     * Note: This implementation assumes that the session is already started
     * and that the InputInterface provides methods to get the various input data
     * like request(), query(), cookie(), file(), server(), header()
     * Adjust the implementation as needed based on your actual InputInterface
     * and SessionInterface methods and behavior
     *
     * @param null|string $name Optional name to differentiate stashes
     * @return Stash
     */
    public function push(?string $name = null): self
    {
        $this->session->set($this->stashKey($name), [
            'request' => $this->input->request(),
            'query' => $this->input->query(),
            'cookie' => $this->input->cookie(),
            'file' => $this->input->file(),
            'server' => $this->input->server(),
            'header' => $this->input->header(),
        ]);

        return $this;
    }

    /**
     * Store the current request data in the session
     * under a specific key
     * return $this for chaining
     * If a name is provided, use that as part of the key
     * otherwise use a default key
     * The stashed data includes:
     * - request data (POST)
     * - query data (GET)
     * - cookie data
     * - file data (FILES)
     * - server data
     * - header data
     * This allows for restoring the request later
     * with all its components
     * Example usage:
     * $stash->push('my_form');
     * Later, to retrieve:
     * $data = $stash->apply('my_form');
     * if ($data !== false) {
     *     // restore the request data
     *     $requestData = $data['request'];
     *     $queryData = $data['query'];
     *     // etc.
     * }
     * If no data was stashed under that name, apply returns false
     * otherwise it returns an array with the stashed data
     * The stashed data is removed from the session after being applied
     * to prevent reuse
     * This is useful for one-time redirects after form submissions
     * where you want to preserve the input data across the redirect
     * but not keep it around indefinitely
     * Note: This implementation assumes that the session is already started
     * and that the InputInterface provides methods to get the various input data
     * like request(), query(), cookie(), file(), server(), header()
     * Adjust the implementation as needed based on your actual InputInterface
     * and SessionInterface methods and behavior
     *
     * @param null|string $name Optional name to differentiate stashes
     * @return false|array
     */
    public function apply(?string $name = null): false|array
    {
        $key = $this->stashKey($name);

        $stashed = false;

        if ($this->session->has($key)) {
            $stashed = $this->session->get($key);
            $this->session->remove($key);
        }

        // return false on no stashed data
        // or an array if something was stashed
        return $stashed;
    }

    /**
     * determine the stash key to use
     *
     * @param null|string $name
     * @return string
     */
    protected function stashKey(?string $name = null): string
    {
        return $name ? $this->stashKey . $name : $this->stashKey;
    }
}
