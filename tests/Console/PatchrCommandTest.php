<?php namespace Tests\Console;

use PHPUnit\Framework\TestCase;
use Zeroplusone\Patchr\Patchr;

class PatchrCommandTest extends TestCase
{

    protected $config;

    /**
     * Tests Setup
     */
    protected function setUp()
    {
        $this->config = [
            'db' => array(
                'host' => 'localhost',
                'user'  => 'patchr_tests',
                'password' => 'VnLK6vC5B3qzbuVQ',
                'database' => 'patchr_tests',
                'table'     => 'patchr_dbpatches',
                'timeout'     => 500,
                'multiple'     => false,
            ),
            'naming' => array(
                'patches_dir' => getcwd().'/tests/Console/patches',
                'prefix'  => 'patch-',
                'digits' => 5,
                'extension' => '.sql',
            ),
            'adv' => array(
                'skip_patches' => [],
            ),
        ];
    }

    /**
     * Generic test for patchr
     */
    public function testExecute()
    {
        $patchr = new Patchr($this->config);

        // Reserve one patch
        $output = $patchr->reservePatches(1);
        $this->assertTrue($output['success']);

        // Create patch with create table statement
        $patchr->addNewPatch('CREATE TABLE IF NOT EXISTS `tests` (
  `id` int(11) NOT NULL,
  `patchr` varchar(100) NOT NULL,
  `is` varchar(200) NOT NULL,
  `cool` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
        $this->assertTrue($output['success']);

        // Create patch with drop table statement
        $patchr->addNewPatch('DROP TABLE `tests`');
        $this->assertTrue($output['success']);

        // Apply above patches
        $output = $patchr->applyDatabasePatches();
        $this->assertTrue($output['success']);
    }
}