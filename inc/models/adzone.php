<?php

namespace Mondiad;

class AdZone {

  const TYPE_CLASSIC_PUSH = 'CLASSIC_PUSH';
  const TYPE_IN_PAGE_PUSH = 'IN_PAGE_PUSH';
  const TYPE_NATIVE = 'NATIVE';
  const TYPE_BANNER = 'BANNER';

  const CAT_MAINSTREAM = 'MAINSTREAM';
  const CAT_MAINSTREAM_AGGRESSIVE = 'MAINSTREAM_AGGRESSIVE';
  const CAT_ADULT = 'ADULT';
  const CAT_ADULT_EXPLICIT = 'ADULT_EXPLICIT';

  const WIDGET_TOP = 'TOP';
  const WIDGET_BOTTOM = 'BOTTOM';
  const WIDGET_LEFT = 'LEFT';
  const WIDGET_CENTER = 'CENTER';
  const WIDGET_RIGHT = 'RIGHT';

  /** @var int */
  public $id;
  /** @var string */
  public $adZoneUuidId;
  /** @var string */
  public $name;
  /** @var string */
  public $status;
  /** @var string */
  public $type;
  /** @var int */
  public $websiteId;
  /** @var string */
  public $createDate;
  /** @var InPagePushConfig */
  public $inPagePushConfig;
  /** @var ClassicPushConfig */
  public $classicPushConfig;
  /** @var NativeConfig */
  public $nativeConfig;
  /** @var array */
  public $adCategories;
}

class InPagePushConfig {
  /** @var int */
  public $displayDelay;
  /** @var string */
  public $widgetPositionHorizontal;
  /** @var string */
  public $widgetPositionVertical;
  /** @var int */
  public $timeoutAfterClick;
  /** @var int */
  public $timeoutAfterClose;
  /** @var int */
  public $numberOfNotificationsOnPage;
  /** @var int */
  public $delayBetweenNotifications = 1;
}

class ClassicPushConfig {

}

class NativeConfig {

}