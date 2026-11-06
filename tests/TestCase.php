<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // -------------------------------------------------------------------------
    // Database-safety guards
    //
    // Two independent checks. Both throw RuntimeException (not markTestSkipped)
    // so a misconfigured run fails loudly and is never confused with a skip.
    //
    // Guard 1 — cached config file (setUp, before any booting)
    //   Fires before parent::setUp(), so the Laravel app is never created at
    //   all when a stale bootstrap/cache/config.php exists.  A cached config
    //   silently overrides every <env> entry in phpunit.xml, which means even
    //   a correct phpunit.xml cannot protect you.
    //
    // Guard 2 — post-boot database config (createApplication)
    //   createApplication() is called by refreshApplication() which is called
    //   by setUpTheTestEnvironment() at the very start of setUp(), BEFORE
    //   setUpTraits() runs RefreshDatabase / DatabaseMigrations.  Overriding
    //   createApplication() lets us inspect config() while the app is alive
    //   but before any migration command can touch a real database.
    //   Covers the case where DB_* shell variables (not the cache file) cause
    //   the wrong driver to be resolved.
    // -------------------------------------------------------------------------

    /** Message reused in both guards so test output is consistent. */
    private const GUARD_MESSAGE =
        'Tests must run on SQLite in-memory. '
        . 'Check for a cached config (bootstrap/cache/config.php) or DB_* environment variables. '
        . 'Run `php artisan config:clear` to remove the cache.';

    /**
     * Guard 1: reject a stale config cache before the app is even created.
     *
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $cacheFile = dirname(__DIR__) . '/bootstrap/cache/config.php';

        if (file_exists($cacheFile)) {
            throw new \RuntimeException(
                self::GUARD_MESSAGE
                . ' (cached config file detected: bootstrap/cache/config.php)'
            );
        }

        parent::setUp();
    }

    /**
     * Guard 2: inspect the resolved database config right after the application
     * is created, before any database trait (RefreshDatabase, etc.) can run.
     *
     * We wrap the CreatesApplication trait's createApplication() instead of
     * adding a second check inside setUp() after parent::setUp(), because
     * parent::setUp() calls setUpTheTestEnvironment() which calls both
     * refreshApplication() [→ createApplication()] AND setUpTraits()
     * [→ refreshDatabase() → migrate:fresh] in a single call.  Overriding
     * createApplication() is the only hook point between those two steps.
     *
     * {@inheritdoc}
     */
    public function createApplication()
    {
        // Delegate to the CreatesApplication trait (bootstrap the real app).
        /** @var \Illuminate\Foundation\Application $app */
        $app = parent::createApplication();

        // The app is now fully booted; config() is authoritative.
        $connection = config('database.default');
        $database   = config('database.connections.sqlite.database');

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(
                self::GUARD_MESSAGE
                . sprintf(
                    ' (resolved: database.default="%s", sqlite.database="%s")',
                    (string) $connection,
                    (string) $database
                )
            );
        }

        return $app;
    }
}
