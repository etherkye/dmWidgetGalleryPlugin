<?php

class dmWidgetContentGalleryView extends dmWidgetPluginView {

  public function configure() {
    parent::configure();

    $this->addRequiredVar(array('medias', 'method', 'animation'));

    $this->addStylesheet(array('dmWidgetGalleryPlugin.view'));

    $this->addJavascript(array('dmWidgetGalleryPlugin.view', 'dmWidgetGalleryPlugin.cycle', 'dmWidgetGalleryPlugin.scroll'));
  }

  protected function filterViewVars(array $vars = array()) {
    $vars = parent::filterViewVars($vars);

    // extract media ids
    $mediaIds = array();
    foreach ($vars['medias'] as $index => $mediaConfig) {
      $mediaIds[] = $mediaConfig['id'];
    }
    $vars['ids'] = implode('-', $mediaIds);

    // fetch media records
    $mediaRecords = empty($mediaIds) ? array() : $this->getMediaQuery($mediaIds)->fetchRecords()->getData();

    // sort records
    $this->mediaPositions = array_flip($mediaIds);
    usort($mediaRecords, array($this, 'sortRecordsCallback'));

    // build media tags
    $medias = array();
    foreach ($mediaRecords as $index => $mediaRecord) {
      $mediaTag = $this->getHelper()->media($mediaRecord);

      if (!empty($vars['width']) || !empty($vars['height'])) {
        $mediaTag->size(dmArray::get($vars, 'width'), dmArray::get($vars, 'height'));
      }

      if (!$mediaTag instanceof dmMediaTagFlowPlayerApplication) {
        $mediaTag->method($vars['method']);
      }

      if ($vars['method'] === 'fit') {
        $mediaTag->background($vars['background']);
      }

      if ($alt = $vars['medias'][$index]['alt']) {
        $mediaTag->alt($this->__($alt));
      }

      if ($quality = dmArray::get($vars, 'quality')) {
        $mediaTag->quality($quality);
      }

      $medias[] = array(
          'tag' => $mediaTag,
          'link' => $vars['medias'][$index]['link'],
          'src' => $mediaTag->getSrc()
      );
    }

    // replace media configuration by media tags
    $vars['medias'] = $medias;

    return $vars;
  }

  protected function sortRecordsCallback(DmMedia $a, DmMedia $b) {
    return $this->mediaPositions[$a->get('id')] > $this->mediaPositions[$b->get('id')];
  }

  protected function getMediaQuery($mediaIds) {
    return dmDb::query('DmMedia m')
            ->leftJoin('m.Folder f')
            ->leftJoin('m.Translation t WITH t.lang = ?', array('en'))
            ->whereIn('m.id', $mediaIds);
  }

  protected function doRender() {
    if ($this->isCachable() && $cache = $this->getCache()) {
      return $cache;
    }

    $vars = $this->getViewVars();
    $helper = $this->getHelper();

    $html = $helper->open('div.dm_widget_content_gallery_container');
    $html = $helper->open('div.dm_widget_content_gallery.list', array('json' => array(
                    'animation' => $vars['animation'],
                    'delay' => dmArray::get($vars, 'delay', 2),
                    'width' => $vars['width'],
                    'height' => $vars['height']
                    )));

    if (isset($vars['sprite']) && $vars['sprite']) {
      $id = '/sprites/' . md5($vars['ids']) . '.jpeg';
      if (is_file(sfConfig::get('sf_upload_dir') . $id) && (mt_rand(0, 1000) > 5)) {
        $html .= $this->createSprite($vars['medias'], $vars['height'], $vars['width'], $id);
      } else {
        $html .= $this->createSprite($vars['medias'], $vars['height'], $vars['width'], $id, true);
      }
    } else {
      foreach ($vars['medias'] as $media) {
        $html .= $media['link'] ? $helper->link($media['link'])->text($media['tag']) : $media['tag'];
      }
    }

    $html .= '</div></div>';

    if ($this->isCachable()) {
      $this->setCache($html);
    }

    return $html;
  }

  protected function doRenderForIndex() {
    $alts = array();
    foreach ($this->compiledVars['medias'] as $media) {
      if (!empty($media['alt'])) {
        $alts[] = $media['alt'];
      }
    }

    return implode(', ', $alts);
  }

  //Thank you diceattack.wordpress.com
  protected function createSprite($medias, $height, $width, $sprite, $recreate = false) {

    $helper = $this->getHelper();
    $html = "";

    $width = $width * count($medias);

    if ($recreate) {
      $image = imagecreatetruecolor($width, $height);
      $bgColor = imagecolorallocate($image, 255, 255, 255);
      imagefill($image, 0, 0, $bgColor);
    }

    $x = 0;

    foreach ($medias as $index => $media) {
      if ($recreate) {
        $tileImg = imagecreatefromjpeg(SF_ROOT_DIR . '/www' . $media['tag']->getSrc());
        imagecopy($image, $tileImg, $x, 0, 0, 0, $media['tag']->getWidth(), $media['tag']->getHeight());
        imagedestroy($tileImg);
      }
      $html .= $helper->tag('div.element', array("style" => "height:" . $height . "px; " .
                  "width:" . $media['tag']->getWidth() . "px; " .
                  "background: url('/uploads" . $sprite . "') no-repeat -" . $x . "px top;"
                      ), $media['link'] ? $helper->link($media['link'])->text("") : ""
      );
      $x+=$media['tag']->getWidth();
    }

    if ($recreate) {
      $thumbImage = imagecreatetruecolor($x, $height);
      imagecopy($thumbImage, $image, 0, 0, 0, 0, $x, $height);

      if (!is_dir(sfConfig::get('sf_upload_dir') . '/sprites')) {
        mkdir(sfConfig::get('sf_upload_dir') . '/sprites');
      }

      imagejpeg($thumbImage, sfConfig::get('sf_upload_dir') . $sprite);
    }
    return $html;
  }

}