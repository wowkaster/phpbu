<?php
namespace phpbu\App\Backup\Source;

use phpbu\App\Backup\CliTest;
use phpbu\App\Backup\Compressor;

/**
 * RedisTest
 *
 * @package    phpbu
 * @subpackage tests
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       https://www.phpbu.de/
 * @since      Class available since Release 2.1.12
 */
class RedisTest extends CliTest
{
    /**
     * Tests Redis::setUp
     *
     * @expectedException \phpbu\App\Exception
     */
    public function testSetupDataPathMissing()
    {
        $redis = new Redis();
        $redis->setup([]);
    }

    /**
     * Tests Redis::getExecutable
     */
    public function testDefault()
    {
        $target  = $this->createTargetMock('/tmp/backup.redis');
        $rdbPath = PHPBU_TEST_FILES . '/misc/dump.rdb';
        $redis   = new Redis();
        $redis->setup(['pathToRedisData' => $rdbPath, 'pathToRedisCli' => PHPBU_TEST_BIN]);

        $exec = $redis->getExecutable($target);

        $this->assertEquals(PHPBU_TEST_BIN . '/redis-cli BGSAVE', $exec->getCommand());
    }

    /**
     * Tests Redis::backup
     */
    public function testBackupOk()
    {
        $cliResult1 = $this->getRunnerResultMock(0, 'redis', '(integer) 100000000');
        $cliResult2 = $this->getRunnerResultMock(0, 'redis', '(integer) 100000000');
        $cliResult3 = $this->getRunnerResultMock(0, 'redis', '(integer) 100000002');

        $runner = $this->getRunnerMock();
        $runner->method('run')
               ->will($this->onConsecutiveCalls($cliResult1, $cliResult2, $cliResult3));

        $target  = $this->createTargetMock('/tmp/dump.rdb');
        $rdbPath = PHPBU_TEST_FILES . '/misc/dump.rdb';
        $redis   = new Redis($runner);
        $redis->setup(['pathToRedisData' => $rdbPath, 'pathToRedisCli' => PHPBU_TEST_BIN]);

        $appResult = $this->getAppResultMock();
        $appResult->expects($this->once())->method('debug');

        $status = $redis->backup($target, $appResult);

        $this->assertEquals('/tmp/dump.rdb', $status->getDataPath());
        $this->assertEquals(false, $status->handledCompression());
    }

    /**
     * Tests Redis::backup
     *
     * @expectedException \phpbu\App\Exception
     */
    public function testBackupInvalidLastBackupTime()
    {
        $cliResult1 = $this->getRunnerResultMock(0, 'redis', 'invalid');
        $cliResult2 = $this->getRunnerResultMock(0, 'redis', 'invalid');
        $cliResult3 = $this->getRunnerResultMock(0, 'redis', 'invalid');

        $runner = $this->getRunnerMock();
        $runner->method('run')
               ->will($this->onConsecutiveCalls($cliResult1, $cliResult2, $cliResult3));

        $target  = $this->createTargetMock('/tmp/dump.rdb');
        $rdbPath = PHPBU_TEST_FILES . '/misc/dump.rdb';
        $redis   = new Redis($runner);
        $redis->setup(['pathToRedisData' => $rdbPath, 'pathToRedisCli' => PHPBU_TEST_BIN]);

        $appResult = $this->getAppResultMock();

        $redis->backup($target, $appResult);
    }

    /**
     * Tests Redis::backup
     *
     * @expectedException \phpbu\App\Exception
     */
    public function testBackupTimeoutFail()
    {
        $runner = $this->getRunnerMock();
        $runner->method('run')
               ->willReturn($this->getRunnerResultMock(0, 'redis', '(integer) 100000000'));

        $target  = $this->createTargetMock('/tmp/dump.rdb');
        $rdbPath = PHPBU_TEST_FILES . '/misc/dump.rdb';
        $redis   = new Redis($runner);
        $redis->setup(['pathToRedisData' => $rdbPath, 'timeout' => 2, 'pathToRedisCli' => PHPBU_TEST_BIN]);

        $appResult = $this->getAppResultMock();
        $appResult->expects($this->once())->method('debug');

        $redis->backup($target, $appResult);
    }

    /**
     * Tests Redis::backup
     *
     * @expectedException \phpbu\App\Exception
     */
    public function testBackupInvalidRDB()
    {
        $cliResult1 = $this->getRunnerResultMock(0, 'redis', '(integer) 100000000');
        $cliResult2 = $this->getRunnerResultMock(0, 'redis', '(integer) 100000000');
        $cliResult3 = $this->getRunnerResultMock(0, 'redis', '(integer) 100000002');

        $runner = $this->getRunnerMock();
        $runner->method('run')
               ->will($this->onConsecutiveCalls($cliResult1, $cliResult2, $cliResult3));

        $target  = $this->createTargetMock('/tmp/dump.rdb');
        $rdbPath = PHPBU_TEST_FILES . '/misc/dump.rdb.invalid';
        $redis   = new Redis($runner);
        $redis->setup(['pathToRedisData' => $rdbPath, 'pathToRedisCli' => PHPBU_TEST_BIN]);

        $appResult = $this->getAppResultMock();
        $appResult->expects($this->once())->method('debug');

        $redis->backup($target, $appResult);
    }

    /**
     * Tests Redis::backup
     *
     * @expectedException \phpbu\App\Exception
     */
    public function testBackupSaveFail()
    {
        $cliResult1 = $this->getRunnerResultMock(0, 'redis', '(integer) 100000000');
        $cliResult2 = $this->getRunnerResultMock(1, 'redis');

        $runner = $this->getRunnerMock();
        $runner->method('run')
               ->will($this->onConsecutiveCalls($cliResult1, $cliResult2));

        $target  = $this->createTargetMock('/tmp/dump.rdb');
        $rdbPath = PHPBU_TEST_FILES . '/misc/dump.rdb';
        $redis   = new Redis($runner);
        $redis->setup(['pathToRedisData' => $rdbPath, 'pathToRedisCli' => PHPBU_TEST_BIN]);

        $appResult = $this->getAppResultMock();
        $appResult->expects($this->once())->method('debug');

        $redis->backup($target, $appResult);
    }
}
