<?php

namespace Drupal\commerce_coupon_auto_apply\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CommerceCouponAutoApplySubscriber.
 */
class CommerceCouponAutoApplySubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Constructs a new CommerceCouponAutoApplySubscriber object.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, Time $time) {
    $this->entityManager = $entity_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['commerce_cart.entity.add'] = ['commerce_cart_entity_add'];

    return $events;
  }

  /**1
   * This method is called whenever the commerce_cart.entity.add event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function commerce_cart_entity_add(Event $event) {
    $coupons = $this->getAutoApplyCoupons();
    $potential_apply = [];
    /** @var Coupon $coupon */
    foreach ($coupons as $key => $coupon) {
      // if the coupon is available add it as a potential.
      if ($coupon->available($event->getCart())) {
        $potential_apply[] = $coupon;
      }
    }

    if (!empty($potential_apply)) {
      // Just apply the last one
      /** @var Coupon $to_apply */
      $to_apply = array_pop($potential_apply);
      /** @var OrderInterface $cart */
      $cart = $event->getCart();
      $cart->set('coupons', $to_apply);
      $cart->save();
    }
  }

  /**
   * Get coupons that are valid time-wise with auto-apply = 1
   *
   * @todo how about moving this to controller?
   */
  public function getAutoApplyCoupons() {
    $coupons = $this->entityManager->getStorage('commerce_promotion_coupon')
      ->getQuery()
      ->condition('auto_apply', 1)
      ->execute();

    if (!empty($coupons)) {
      return $this->entityManager->getStorage('commerce_promotion_coupon')
        ->loadMultiple($coupons);
    }

    return FALSE;
  }

}
