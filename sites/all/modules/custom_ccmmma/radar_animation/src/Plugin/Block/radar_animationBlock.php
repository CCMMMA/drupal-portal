<?php

/**
 * @file
 */
namespace Drupal\radar_animation\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Creates a 'RadarAnimation' Block
 * @Block(
 * id = "block_radar_animation",
 * admin_label = @Translation("Radar Animation block"),
 * )
 */
class radar_animationBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $markup = '<div><div id="animazione" class="animando"><img id="anim" src="/sites/all/modules/custom_ccmmma/radar_animation/images/avatar-black.gif" style="opacity: 1 !important" border="1"/><img id="segnaposto" src="" style="position: relative !important; opacity: 0 !important" /></div></div>';
	//$markup = '<div>prova</div>';
        return array (
		'#markup' => \Drupal\Core\Render\Markup::create($markup),
      		'#cache' => array(
        	     'max-age' => 0,
      		),
                '#attached' => array(
        	  'library' => array(
           	  'radar_animation/radar_animation-lib',
        	),
	  ),
        );
    }
}
