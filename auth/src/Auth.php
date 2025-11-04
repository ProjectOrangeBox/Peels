<?php

declare(strict_types=1);

namespace peels\auth;

use PDO;
use orange\framework\traits\ConfigurationTrait;

/**
 * Authentication service handling credential validation and session lifecycle.
 */
class Auth implements AuthInterface
{
    use ConfigurationTrait;

    /**
     * Auth configuration merged with defaults from {@see ConfigurationTrait}.
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Singleton instance reference returned by {@see Auth::getInstance()}.
     */
    private AuthInterface $instance;

    /**
     * Latest error message describing why authentication failed.
     */
    protected string $error = '';
    /**
     * Authenticated user identifier, zero when no user is logged in.
     */
    protected int $userId = 0;

    /* database configuration */
    /** @var PDO Active database connection used for credential lookups. */
    protected PDO $db;
    /** @var string Name of the table storing user credential data. */
    protected string $table;
    /** @var string Column containing user login identifiers. */
    protected string $usernameColumn;
    /** @var string Column containing user password hashes. */
    protected string $passwordColumn;
    /** @var string Column indicating whether a user account is active. */
    protected string $isActiveColumn;

    /**
     * Create a new authentication service instance.
     *
     * @param array<string, mixed> $config Configuration overrides.
     * @param PDO                  $pdo    Database connection to query user data.
     */
    public function __construct(array $config, PDO $pdo)
    {
        $this->config = $this->mergeConfigWith($config);

        $this->db = $pdo;

        $this->table = $this->config['table'];
        $this->usernameColumn = $this->config['username column'];
        $this->passwordColumn = $this->config['password column'];
        $this->isActiveColumn = $this->config['is active column'];

        /* let make sure the required are present! */


        $this->logout();
    }

    /**
     * Retrieve (or create) a shared instance of the authentication service.
     *
     * @param array<string, mixed> $config Configuration overrides.
     * @param PDO                  $pdo    Database connection to query user data.
     *
     * @return self
     */
    public static function getInstance(array $config, PDO $pdo): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $pdo);
        }

        return self::$instance;
    }

    /**
     * Return the most recent authentication error.
     *
     * @return string
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * Determine if an error message has been recorded.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return !empty($this->error);
    }

    /**
     * Attempt to authenticate a user by login and password.
     *
     * @param string $login    User supplied identifier.
     * @param string $password User supplied plaintext password.
     *
     * @return bool True when authentication succeeds, false otherwise.
     */
    public function login(string $login, string $password): bool
    {
        $this->logout();

        /* Does login and password contain anything empty values are NOT permitted for any reason */
        if ((strlen(trim($login)) == 0) || (strlen(trim($password)) == 0)) {
            $this->error = $this->config['empty fields error'];

            /* fail */
            return false;
        }

        /* try to load the user */
        $user = $this->getUser($login);

        if (!is_array($user)) {
            $this->error = $this->config['general error'];

            /* fail */
            return false;
        }

        /* Verify the Password entered with what's in the database */
        if (password_verify($password, $user[$this->passwordColumn]) !== true) {
            $this->error = $this->config['incorrect password error'];

            /* fail */
            return false;
        }

        /* Is this user activated? */
        if ((int) $user[$this->isActiveColumn] !== 1) {
            $this->error = $this->config['not activated error'];

            /* fail */
            return false;
        }

        /* save our user id */
        $this->userId = (int) $user['id'];

        /* successful */
        return true;
    }

    /**
     * Clear authentication state and the last error.
     *
     * @return bool Always true to indicate the state reset completed.
     */
    public function logout(): bool
    {
        $this->error = '';
        $this->userId = 0;

        return true;
    }

    /**
     * Retrieve the identifier for the current authenticated user.
     *
     * @return int
     */
    public function userId(): int
    {
        return $this->userId;
    }

    /**
     * Look up a user record by login credential.
     *
     * @param string $login Login identifier to search for.
     *
     * @return array<string, mixed>|false Database record or false if not found.
     */
    protected function getUser(string $login)
    {
        $pdoStatement = $this->db->prepare('select `id`,`' . $this->passwordColumn . '`,`' . $this->isActiveColumn . '` from `' . $this->table . '` where `' . $this->usernameColumn . '` = :login limit 1');

        $pdoStatement->execute([':login' => $login]);

        // https://docstore.mik.ua/orelly/java-ent/jenut/ch08_06.htm
        $error = $pdoStatement->errorInfo();

        if (!empty($error[2])) {
            logMsg('info', __METHOD__ . ' ' . $error[2]);
        }

        return $pdoStatement->fetch(PDO::FETCH_ASSOC);
    }
} /* end class */
