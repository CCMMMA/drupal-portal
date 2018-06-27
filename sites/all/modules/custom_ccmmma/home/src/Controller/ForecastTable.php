<?php
namespace Drupal\home\Controller;
 
use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;

class ForecastTable extends ControllerBase {
  public function content() {
    $variables = [];




    $block = \Drupal\block\Entity\Block::load('bollettinometeoblock');

    $block_content = \Drupal::entityTypeManager()
      ->getViewBuilder('block')
      ->view($block);
    if ($block_content) {
      $variables['bollettino_block'] = $block_content;
    }


    return [
      '#theme' => 'forecast_table_template',
      '#variables_block' => $variables,
    ];
  }

  private function generate_table(){
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    dpm($_GET);





  }
}


