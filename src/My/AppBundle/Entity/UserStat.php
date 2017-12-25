<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\UserStat as UserStatModel;

class UserStat extends UserStatModel
{
    // accepted values

    // reg_by:
    const REG_BY_API            = 'api';            // \My\AppBundle\Controller\ApiController::addUserAction
    const REG_BY_OFFLINE        = 'offline';        // \My\AppBundle\Controller\AdminController::addUserAction
    const REG_BY_OFFLINE_SIMPLE = 'offline_simple'; // \My\AppBundle\Controller\AdminController::addSimpleUserAction
    const REG_BY_OFFLINE_OLD    = 'offline_old';    // \My\AppBundle\Controller\Admin\UserController::addOldUserAction
    const REG_BY_REGULAR        = 'regular';

    // reg_type:
    const REG_TYPE_UNPAID = 'unpaid';
    const REG_TYPE_PAID_1 = 'paid_1';
    const REG_TYPE_PAID_2 = 'paid_2';

    // pay_1_type:
    const PAY_1_TYPE_REGULAR     = 'regular';
    const PAY_1_TYPE_OFFLINE     = 'offline';
    const PAY_1_TYPE_BY_API      = 'by_api';
    const PAY_1_TYPE_PROMO       = 'promo';
    const PAY_1_TYPE_PROMO_MIXED = 'promo_mixed';  // Regular payment with promo subtracted from amount

    // discount_1_type:
    const DISCOUNT_1_TYPE_FIRST = 'first';

    // pay_2_type:
    const PAY_2_TYPE_REGULAR     = 'regular';
    const PAY_2_TYPE_OFFLINE     = 'offline';
    const PAY_2_TYPE_BY_API      = 'by_api';
    const PAY_2_TYPE_PROMO       = 'promo';
    const PAY_2_TYPE_PROMO_MIXED = 'promo_mixed';

    // discount_2_type:
    const DISCOUNT_2_TYPE_FIRST                = 'first';
    const DISCOUNT_2_TYPE_BETWEEN_FIRST_SECOND = 'between_first_second';
    const DISCOUNT_2_TYPE_SECOND               = 'second';
    const DISCOUNT_2_TYPE_AFTER_SECOND         = 'after_second';
}
