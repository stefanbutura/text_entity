<?php

namespace Drupal\text_entity\Plugin\Field\FieldFormatter;

use Drupal;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'Text Entity'.
 *
 * @FieldFormatter(
 *   id = "text_entity",
 *   label = @Translation("Text Entity"),
 *   field_types = {
 *     "text",
 *     "string"
 *   }
 * )
 */
class TextEntityFormatter extends FormatterBase
{

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSetting('field_option');
    list($contentType, $field) = explode("-", $settings);
    $field = "field_" . $field;
    foreach ($items as $delta => $item) {
      $nid = Drupal::entityQuery('node')
        ->condition('type', $contentType)
        ->condition($field, $item->value)
        ->execute();

      if (!empty($nid)) {
        $element[$delta] = [
          '#type' => 'link',
          '#title' => $item->value,
          '#url' => Url::fromRoute('entity.node.canonical', ['node' => reset($nid)]),
        ];
      }
      else {
        $element[$delta] = [
          '#type' => 'markup',
          '#markup' => $item->value,
        ];
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'field_option' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $fields = [];
    foreach ($contentTypes as $contentType) {
      $field_list = $this->getTextFields(strtolower($contentType->label()));
      $fields[$contentType->label()] = $field_list;
    }

    $element['field_option'] = [
      '#title' => 'Field',
      '#type' => 'select',
      '#options' => $fields,
      '#default_value' => $this->getSetting('field_option'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] =
      "Field: '" . $this->getSetting('field_option') . "'";
    return $summary;
  }

  /**
   * Get text fields names by content type.
   *
   * @param string $contentType
   *   Content type machine name.
   *
   * @return array
   *   List of fields labels indexed by field name.
   */
  function getTextFields($contentType) {
    $fields = [];
    $entityManager = \Drupal::service('entity.manager');
    $res = $entityManager->getFieldDefinitions('node', $contentType);
    foreach ($res as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && $field_definition->getType() == 'text') {
        $key = $contentType . '-' . $field_definition->getLabel();
        $fields[$key] = $field_definition->getLabel();
      }
    }
    return $fields;
  }

}
