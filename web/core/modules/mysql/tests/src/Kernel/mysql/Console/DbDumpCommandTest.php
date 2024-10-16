<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql\Console;

use Drupal\Core\Command\DbDumpCommand;
use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test that the DbDumpCommand works correctly.
 *
 * @group console
 */
class DbDumpCommandTest extends DriverSpecificKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Rebuild the router to ensure a routing table.
    \Drupal::service('router.builder')->rebuild();

    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = $this->container->get('database');
    $connection->insert('router')->fields(['name', 'path', 'pattern_outline'])->values(['test', 'test', 'test'])->execute();

    // Create a table with a field type not defined in
    // \Drupal\Core\Database\Schema::getFieldTypeMap.
    $table_name = $connection->getPrefix() . 'foo';
    $sql = "create table if not exists `$table_name` (`test` datetime NOT NULL);";
    $connection->query($sql)->execute();
  }

  /**
   * Tests the command directly.
   */
  public function testDbDumpCommand(): void {
    $command = new DbDumpCommand();
    $command_tester = new CommandTester($command);
    $command_tester->execute([]);

    // Assert that insert exists and that some expected fields exist.
    $output = $command_tester->getDisplay();
    $this->assertStringContainsString("createTable('router", $output, 'Table router found');
    $this->assertStringContainsString("insert('router", $output, 'Insert found');
    $this->assertStringContainsString("'name' => 'test", $output, 'Insert name field found');
    $this->assertStringContainsString("'path' => 'test", $output, 'Insert path field found');
    $this->assertStringContainsString("'pattern_outline' => 'test", $output, 'Insert pattern_outline field found');
    $this->assertStringContainsString("// phpcs:ignoreFile", $output);
    $version = \Drupal::VERSION;
    $this->assertStringContainsString("This file was generated by the Drupal {$version} db-tools.php script.", $output);
  }

  /**
   * Tests schema only option.
   */
  public function testSchemaOnly(): void {
    $command = new DbDumpCommand();
    $command_tester = new CommandTester($command);
    $command_tester->execute(['--schema-only' => 'router']);

    // Assert that insert statement doesn't exist for schema only table.
    $output = $command_tester->getDisplay();
    $this->assertStringContainsString("createTable('router", $output, 'Table router found');
    $this->assertStringNotContainsString("insert('router", $output, 'Insert not found');
    $this->assertStringNotContainsString("'name' => 'test", $output, 'Insert name field not found');
    $this->assertStringNotContainsString("'path' => 'test", $output, 'Insert path field not found');
    $this->assertStringNotContainsString("'pattern_outline' => 'test", $output, 'Insert pattern_outline field not found');

    // Assert that insert statement doesn't exist for wildcard schema only match.
    $command_tester->execute(['--schema-only' => 'route.*']);
    $output = $command_tester->getDisplay();
    $this->assertStringContainsString("createTable('router", $output, 'Table router found');
    $this->assertStringNotContainsString("insert('router", $output, 'Insert not found');
    $this->assertStringNotContainsString("'name' => 'test", $output, 'Insert name field not found');
    $this->assertStringNotContainsString("'path' => 'test", $output, 'Insert path field not found');
    $this->assertStringNotContainsString("'pattern_outline' => 'test", $output, 'Insert pattern_outline field not found');
  }

  /**
   * Tests insert count option.
   */
  public function testInsertCount(): void {
    $command = new DbDumpCommand();
    $command_tester = new CommandTester($command);
    $command_tester->execute(['--insert-count' => '1']);

    $router_row_count = (int) $this->container->get('database')->select('router')->countQuery()->execute()->fetchField();

    $output = $command_tester->getDisplay();
    $this->assertSame($router_row_count, substr_count($output, "insert('router"));
    $this->assertGreaterThan(1, $router_row_count);
  }

}
