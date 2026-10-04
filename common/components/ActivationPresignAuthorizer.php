<?php

namespace common\components;

use common\models\Company;

/**
 * Authorizes one activation logo or licence presign.
 *
 * CompanyRequest stores a contact_auth_key from generateAuthKey(), whose
 * default length is four characters. actionActivate does not expire or
 * consume that key. This check therefore requires the pending company
 * relationship and limits guesses. It does not save the contact or clear
 * the key, because activation needs both uploads.
 *
 * The failed-attempt budget is per normalized email. The requester address
 * is only a second limit. Counters use the application cache. prod-railway
 * configures yii\caching\FileCache. Check and update run under an exclusive
 * flock in a sibling of that cache directory, so FileCache garbage
 * collection cannot unlink the lock. The counters themselves stay in
 * FileCache. They belong to one container: replacing the container or
 * clearing the cache resets them. The company API must run as one replica
 * so every worker shares that directory.
 */
class ActivationPresignAuthorizer
{
    const FAILURE_LIMIT = 5;
    const IP_FAILURE_LIMIT = 20;
    const FAILURE_TTL = 900;
    const ISSUE_LIMIT = 8;
    const ISSUE_TTL = 3600;
    const LOCK_DIRECTORY_NAME = 'ce37-activation-presign';
    const LOCK_FILE_NAME = 'ce37-activation-presign.lock';

    /** @var object|null */
    private $cache;

    /** @var callable */
    private $contactsForEmail;

    /** @var string|null */
    private $lockDirectory;

    /**
     * @param object|null $cache Yii cache, or a test double with get() and set().
     * @param callable $contactsForEmail function (string $email): iterable
     * @param string|null $lockDirectory Required unless $cache is FileCache.
     */
    public function __construct($cache, callable $contactsForEmail, $lockDirectory = null)
    {
        $this->cache = $cache;
        $this->contactsForEmail = $contactsForEmail;
        $this->lockDirectory = $lockDirectory;
    }

    /**
     * @param mixed $email
     * @param mixed $key
     * @param mixed $companyId
     * @param mixed $ip
     * @return object
     * @throws ActivationPresignInputException
     * @throws ActivationPresignDeniedException
     * @throws ActivationPresignLimitedException
     * @throws ActivationPresignUnavailableException
     */
    public function authorize($email, $key, $companyId, $ip)
    {
        $email = $this->scalarString($email);
        $key = $this->scalarString($key);
        $companyId = $this->positiveInt($companyId);
        if ($email === null || $key === null || $companyId === null) {
            throw new ActivationPresignInputException('Invalid activation credentials.');
        }

        if (!is_string($ip) || trim($ip) === '') {
            throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
        }

        $ip = trim($ip);

        return $this->withLock(function () use ($email, $key, $companyId, $ip) {
            $emailKey = $this->emailFailureKey($email);
            $ipKey = $this->ipFailureKey($ip);
            if ($this->limited($emailKey, self::FAILURE_LIMIT) || $this->limited($ipKey, self::IP_FAILURE_LIMIT)) {
                throw new ActivationPresignLimitedException('Temporary upload is unavailable.');
            }

            $contact = $this->matchContact($email, $key);
            $company = $contact ? $this->pendingCompany($contact, $companyId) : null;
            if (!$contact || !$company) {
                $this->increment($emailKey, self::FAILURE_TTL);
                $this->increment($ipKey, self::FAILURE_TTL);
                throw new ActivationPresignDeniedException('The requested page does not exist.');
            }

            $issueKey = $this->issueKey($contact, $companyId);
            if ($this->limited($issueKey, self::ISSUE_LIMIT)) {
                throw new ActivationPresignLimitedException('Temporary upload is unavailable.');
            }

            $this->increment($issueKey, self::ISSUE_TTL);

            return $company;
        });
    }

    /**
     * @param callable $callback
     * @return mixed
     * @throws ActivationPresignUnavailableException
     */
    private function withLock(callable $callback)
    {
        $this->assertCache();
        $directory = $this->lockDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
        }

        $handle = @fopen($directory . DIRECTORY_SEPARATOR . self::LOCK_FILE_NAME, 'c');
        if ($handle === false || !@flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
        }

        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return string
     * @throws ActivationPresignUnavailableException
     */
    private function lockDirectory()
    {
        if (is_object($this->cache) && $this->cache instanceof \yii\caching\FileCache) {
            $path = $this->cache->cachePath;
            if (is_string($path) && $path !== '') {
                $parent = dirname($path);
                if ($parent !== '' && $parent !== $path) {
                    return $parent . DIRECTORY_SEPARATOR . self::LOCK_DIRECTORY_NAME;
                }
            }
        }

        if (is_string($this->lockDirectory) && $this->lockDirectory !== '') {
            return $this->lockDirectory;
        }

        throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function scalarString($value)
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    private function positiveInt($value)
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (!is_string($value) || !preg_match('/^[1-9][0-9]*$/', $value)) {
            return null;
        }

        $number = (int) $value;
        if ((string) $number !== $value) {
            return null;
        }

        return $number;
    }

    /**
     * @throws ActivationPresignUnavailableException
     */
    private function assertCache()
    {
        if (!is_object($this->cache) || !method_exists($this->cache, 'get') || !method_exists($this->cache, 'set')) {
            throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
        }
    }

    /**
     * @param string $email
     * @param string $key
     * @return object|null
     * @throws ActivationPresignUnavailableException
     */
    private function matchContact($email, $key)
    {
        $contacts = call_user_func($this->contactsForEmail, $email);
        if (!is_array($contacts) && !$contacts instanceof \Traversable) {
            throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
        }

        foreach ($contacts as $contact) {
            $storedEmail = $this->field($contact, 'contact_email');
            $storedKey = $this->field($contact, 'contact_auth_key');
            if (is_int($storedKey)) {
                $storedKey = (string) $storedKey;
            }
            if (!is_string($storedEmail) || !is_string($storedKey) || $storedKey === '') {
                continue;
            }

            if (hash_equals($this->normalizedEmail($storedEmail), $this->normalizedEmail($email))
                && hash_equals($storedKey, $key)
            ) {
                return $contact;
            }
        }

        return null;
    }

    /**
     * @param object $contact
     * @param int $companyId
     * @return object|null
     * @throws ActivationPresignUnavailableException
     */
    private function pendingCompany($contact, $companyId)
    {
        foreach ($this->companies($contact, $companyId) as $company) {
            $id = $this->field($company, 'company_id');
            if ((string) $id !== (string) $companyId) {
                continue;
            }

            return $this->isPending($company) ? $company : null;
        }

        return null;
    }

    /**
     * @param object $contact
     * @param int $companyId
     * @return iterable
     * @throws ActivationPresignUnavailableException
     */
    private function companies($contact, $companyId)
    {
        if (is_object($contact) && method_exists($contact, 'getCompanies')) {
            $query = $contact->getCompanies();
            if (!is_object($query) || !method_exists($query, 'andWhere') || !method_exists($query, 'all')) {
                throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
            }

            $companies = $query->andWhere(['company_id' => $companyId])->all();
            if (!is_array($companies) && !$companies instanceof \Traversable) {
                throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
            }

            return $companies;
        }

        if (is_object($contact) && isset($contact->companies)) {
            return $contact->companies;
        }

        throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
    }

    /**
     * A freshly approved company has no status override and no hiring activity.
     * An override of inactive (0) is the same as no override in Company::getCompany_status().
     *
     * @param object $company
     * @return bool
     */
    private function isPending($company)
    {
        $override = $this->field($company, 'company_status_override');
        if ($override !== null && $override !== '' && (int) $override !== Company::STATUS_INACTIVE) {
            return false;
        }

        if (is_object($company) && method_exists($company, 'getCompany_status')) {
            return (int) $company->getCompany_status() !== Company::STATUS_ACTIVE;
        }

        return true;
    }

    /**
     * @param string $key
     * @param int $limit
     * @return bool
     * @throws ActivationPresignUnavailableException
     */
    private function limited($key, $limit)
    {
        return $this->countOf($key) >= $limit;
    }

    /**
     * @param string $key
     * @return int
     * @throws ActivationPresignUnavailableException
     */
    private function countOf($key)
    {
        $value = $this->cache->get($key);
        if ($value === false || $value === null) {
            return 0;
        }

        if (!is_int($value)) {
            throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
        }

        return $value;
    }

    /**
     * @param string $key
     * @param int $ttl
     * @throws ActivationPresignUnavailableException
     */
    private function increment($key, $ttl)
    {
        $count = $this->countOf($key) + 1;
        if ($this->cache->set($key, $count, $ttl) === false) {
            throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
        }
    }

    /**
     * @param string $email
     * @return string
     */
    private function normalizedEmail($email)
    {
        return strtolower($email);
    }

    /**
     * @param string $email
     * @return string
     */
    private function emailFailureKey($email)
    {
        return 'ce37:co:act:email:' . hash('sha256', $this->normalizedEmail($email));
    }

    /**
     * @param string $ip
     * @return string
     */
    private function ipFailureKey($ip)
    {
        return 'ce37:co:act:ip:' . hash('sha256', $ip);
    }

    /**
     * @param object $contact
     * @param int $companyId
     * @return string
     * @throws ActivationPresignUnavailableException
     */
    private function issueKey($contact, $companyId)
    {
        $uuid = $this->field($contact, 'contact_uuid');
        if (!is_string($uuid) || $uuid === '') {
            throw new ActivationPresignUnavailableException('Temporary upload is unavailable.');
        }

        return 'ce37:co:act:issue:' . hash('sha256', $uuid . "\n" . $companyId);
    }

    /**
     * @param object $model
     * @param string $name
     * @return mixed
     */
    private function field($model, $name)
    {
        if (is_object($model) && isset($model->{$name})) {
            return $model->{$name};
        }

        return null;
    }
}

class ActivationPresignInputException extends \InvalidArgumentException
{
}

class ActivationPresignDeniedException extends \RuntimeException
{
}

class ActivationPresignLimitedException extends \RuntimeException
{
}

class ActivationPresignUnavailableException extends \RuntimeException
{
}
