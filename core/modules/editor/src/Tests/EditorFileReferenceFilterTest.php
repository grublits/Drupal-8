<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\EditorFileReferenceFilterTest.
 */

namespace Drupal\editor\Tests;

use Drupal\simpletest\KernelTestBase;
use Drupal\filter\FilterBag;

/**
 * Tests for the EditorFileReference filter.
 */
class EditorFileReferenceFilterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'filter', 'editor', 'field', 'file', 'user');

  /**
   * @var \Drupal\filter\Plugin\FilterInterface[]
   */
  protected $filters;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Editor File Reference filter',
      'description' => "Tests Editor module's file reference filter.",
      'group' => 'Text Editor',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));
    $this->installEntitySchema('file');
    $this->installSchema('file', array('file_usage'));

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterBag($manager, array());
    $this->filters = $bag->getAll();
  }

  /**
   * Tests the editor file reference filter.
   */
  function testEditorFileReferenceFilter() {
    $filter = $this->filters['editor_file_reference'];

    $test = function($input) use ($filter) {
      return $filter->process($input, 'und');
    };

    file_put_contents('public://llama.jpg', $this->randomName());
    $image = entity_create('file', array('uri' => 'public://llama.jpg'));
    $image->save();
    $id = $image->id();
    $uuid = $image->uuid();

    file_put_contents('public://alpaca.jpg', $this->randomName());
    $image_2 = entity_create('file', array('uri' => 'public://alpaca.jpg'));
    $image_2->save();
    $id_2 = $image_2->id();
    $uuid_2 = $image_2->uuid();

    $this->pass('No data-editor-file-uuid attribute.');
    $input = '<img src="llama.jpg" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());

    $this->pass('One data-editor-file-uuid attribute.');
    $input = '<img src="llama.jpg" data-editor-file-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual(array('file' => array($id)), $output->getCacheTags());

    $this->pass('One data-editor-file-uuid attribute with odd capitalization.');
    $input = '<img src="llama.jpg" DATA-editor-file-UUID =   "' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual(array('file' => array($id)), $output->getCacheTags());

    $this->pass('One data-editor-file-uuid attribute on a non-image tag.');
    $input = '<video src="llama.jpg" data-editor-file-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual(array('file' => array($id)), $output->getCacheTags());

    $this->pass('One data-editor-file-uuid attribute with an invalid value.');
    $input = '<img src="llama.jpg" data-editor-file-uuid="invalid-' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual(array(), $output->getCacheTags());

    $this->pass('Two different data-editor-file-uuid attributes.');
    $input = '<img src="llama.jpg" data-editor-file-uuid="' . $uuid . '" />';
    $input .= '<img src="alpaca.jpg" data-editor-file-uuid="' . $uuid_2 . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual(array('file' => array($id, $id_2)), $output->getCacheTags());

    $this->pass('Two identical  data-editor-file-uuid attributes.');
    $input = '<img src="llama.jpg" data-editor-file-uuid="' . $uuid . '" />';
    $input .= '<img src="llama.jpg" data-editor-file-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertIdentical($input, $output->getProcessedText());
    $this->assertEqual(array('file' => array($id)), $output->getCacheTags());
  }

}
