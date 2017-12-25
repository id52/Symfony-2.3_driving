<?php

namespace My\AppBundle\Entity;

use Imagine\Imagick\Imagine;
use Imagine\Image\Box;
use My\AppBundle\Model\User as UserModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Yaml\Yaml;

class User extends UserModel implements EquatableInterface
{
    /**
     * @var $photo_file \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected $photo_file;
    protected $notifies_cnt = 0;
    protected $payment_1_paid_not_notify = false;
    protected $payment_2_paid_not_notify = false;
    protected $white_ips = array();
    protected $moderated = false;
    protected $not_registration = false;
    protected $foreign_passport = false;
    protected $discount_2_notify_first = false;
    protected $discount_2_notify_second = false;
    protected $mailing = true;
    protected $offline = false;
    protected $overdue_unsubscribed = false;
    protected $payment_2_paid_goal = false;
    protected $unsubscribed_x = false;
    protected $by_api = false;
    protected $by_api_comb = false;
    protected $by_api_expr = false;
    protected $hurry_is_send = false;
    protected $exam_attempts = 0;
    protected $terms_and_conditions = false;
    protected $treaty_on_non_disclosure = false;
    protected $confirm_docs_is_send = false;
    protected $paid_primary_boosting_notify = false;
    protected $not_paid_primary_boosting_is_send = false;
    protected $not_paid_primary_boosting_notify = false;

    public function __construct()
    {
        if (!$this->paid_notified_at) {
            $this->paid_notified_at = new \DateTime();
        }

        parent::__construct();
    }

    public function getPhotoFile()
    {
        return $this->photo_file;
    }

    public function setPhotoFile(UploadedFile $file)
    {
        $this->photo_file = $file;
    }

    public function photoPreUpload()
    {
        if ($this->photo_file) {
            if ($this->photo) {
                $this->photoRemove();
            }
            $this->photo = sha1(uniqid()).'.'.$this->photo_file->guessExtension();
        }
    }

    public function photoUpload()
    {
        if ($this->photo_file) {
            $this->photo_file->move($this->getPhotoUploadRootDir(), $this->photo);
            $this->photo_file = null;

            $image = new Imagine();
            $image
                ->open($this->getPhotoAbsolutePath())
                ->thumbnail(new Box(900, 1200))
                ->save($this->getPhotoAbsolutePath())
            ;
        }
    }

    public function photoRemoveUpload()
    {
        if ($file = $this->getPhotoAbsolutePath()) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function photoRemoveUploadCache()
    {
        if ($web_path = $this->getPhotoWebPath()) {
            $config = Yaml::parse(__DIR__.'/../../../../app/config/config.yml');
            if (isset($config['liip_imagine'])) {
                $filter_sets = array_keys($config['liip_imagine']['filter_sets']);
                $imagine_cache_dir = __DIR__.'/../../../../web'.$config['liip_imagine']['cache_prefix'];
                foreach ($filter_sets as $filter) {
                    $file = $imagine_cache_dir.'/'.$filter.'/'.$web_path;
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }

    public function getPhotoAbsolutePath()
    {
        return empty($this->photo) ? null : $this->getPhotoUploadRootDir().'/'.$this->photo;
    }

    public function getPhotoWebPath()
    {
        return empty($this->photo) ? null : $this->getPhotoUploadDir().'/'.$this->photo;
    }

    public function photoRemove()
    {
        $this->photoRemoveUpload();
        $this->photoRemoveUploadCache();
        $this->photo = null;
        $this->photo_coords = array();
    }

    public function setEmail($email)
    {
        $this->setUsername($email);
        return parent::setEmail($email);
    }

    public function photoRecountCoords()
    {
        $aratio = 3 / 4;
        list($width, $height) = getimagesize($this->getPhotoAbsolutePath());
        if ($width / $height > $aratio) {
            $w = (int)round($height * $aratio);
            $h = $height;
            $x = (int)round(($width - $w) / 2);
            $y = 0;
        } else {
            $w = $width;
            $h = (int)round($width / $aratio);
            $x = 0;
            $y = (int)round(($height - $h) / 2);
        }

        $this->setPhotoCoords(array(
            'w' => $w,
            'h' => $h,
            'x' => $x,
            'y' => $y,
        ));
    }

    public function serialize()
    {
        return serialize(array(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id,
            $this->roles,
            $this->white_ips,
        ));
    }

    public function unserialize($serialized)
    {
        list(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id,
            $this->roles,
            $this->white_ips
        ) = unserialize($serialized);
    }

    public function isEqualTo(UserInterface $user)
    {
        if ($user instanceof $this) {
            $isEqual = $user->getRoles() === $this->getRoles();
            return $isEqual;
        }
        return false;
    }

    public function setPhoneMobile($phoneMobile)
    {
        $this->phone_mobile = $phoneMobile;
        $this->phone_mobile_status = null;
        return $this;
    }

    public function getMobileFormat()
    {
        if (preg_match('#^(\d{3})(\d{3})(\d{2})(\d{2})$#misu', $this->getPhoneMobile(), $matches)) {
            return ('+7 ('.$matches[1].') '.$matches[2].'-'.$matches[3].'-'.$matches[4]);
        }
        return false;
    }
    
    public function getURoles()
    {
        $roles = $this->getRoles();
        if (in_array('ROLE_ADMIN', $roles)) {
            $roles = array('ROLE_ADMIN');
        } else {
            $roles = array_diff($roles, array(
                'ROLE_USER',
                'ROLE_USER_PAID',
                'ROLE_USER_PAID2',
                'ROLE_USER_FULL_PROFILE',
            ));
        }
        return $roles;
    }

    public function setURoles($uRoles)
    {
        $uRoles = (array)$uRoles;
        $roles = (array)$this->getRoles();
        $roles = array_intersect($roles, array(
            'ROLE_USER',
            'ROLE_USER_PAID',
            'ROLE_USER_PAID2',
            'ROLE_USER_FULL_PROFILE',
        ));
        if (in_array('ROLE_ADMIN', $uRoles)) {
            $add_roles = array('ROLE_ADMIN');
        } else {
            $add_roles = $uRoles;
        }
        $this->setRoles(array_merge($roles, $add_roles));
        return $this;
    }

    public function getUWhiteIps()
    {
        $ips = $this->getWhiteIps();
        $ips = implode(PHP_EOL, (array)$ips);
        return $ips;
    }

    public function setUWhiteIps($ips)
    {
        $ips = explode(PHP_EOL, $ips);
        foreach ($ips as $k => $ip) {
            $ip = trim($ip);
            if ($ip) {
                $ips[$k] = $ip;
            } else {
                unset($ips[$k]);
            }
        }
        $ips = array_unique($ips);
        sort($ips);
        $this->setWhiteIps($ips);
        return $this;
    }

    public function getCurrentDiscount2()
    {
        $discount = 0;

        $region = $this->getRegion();
        if ($region) {
            if ($this->isDiscount2FirstEnabled()) {
                $discount = $region->getDiscount2FirstAmount();
            } elseif ($this->isDiscount2SecondEnabled()) {
                $discount = $region->getDiscount2SecondAmount();
            }
        }

        return $discount;
    }

    /**
     * Get end date for second payment discount period # 1 or 2. For timers purposes i.e.
     *
     * @return \DateTime End date for discount period for second payment
     * (either the first or the second discount period!). Null if deiscount is not enabled
     */
    public function getDiscount2PeriodEndDate()
    {
        $region = $this->getRegion();
        if ($region && !$this->getPayment2Paid()) {
            $date = clone $this->getPayment1Paid();
            $now  = new  \DateTime();
            if ($date) {
                // end of first discount period
                // +1 because day of payment is on first discount period
                // as we can see below in isDiscount2[First|Second]Enabled()
                $date->add(new \DateInterval('P'.($region->getDiscount2FirstDays() + 1).'D'));
                if ($now < $date && $region->isDiscount2FirstEnabled()) {
                    return $date;
                } else {
                    $period2StartDate = $date->add(new \DateInterval('P'.$region->getDiscount2BetweenPeriodDays().'D'));
                    $period2EndDate   = clone $period2StartDate;
                    $period2EndDate->add(new \DateInterval('P'.$region->getDiscount2SecondDays().'D'));
                    if ($now < $period2EndDate && $region->isDiscount2SecondEnabled() && $now > $period2StartDate) {
                        return $period2EndDate;
                    }
                }
            }
        }
        return null;
    }

    public function isDiscount2FirstEnabled()
    {
        $enabled = false;

        $region = $this->getRegion();
        if ($region && !$this->getPayment2Paid()) {
            $date1 = $this->getPayment1Paid();
            if ($date1) {
                $after_date1 = $date1->diff(new \DateTime())->days;
                if ($region->isDiscount2FirstEnabled()
                    && $after_date1 <= $region->getDiscount2FirstDays()
                ) {
                    $enabled = true;
                }
            }
        }

        return $enabled;
    }

    public function isDiscount2SecondEnabled()
    {
        $enabled = false;

        $region = $this->getRegion();
        if ($region && !$this->getPayment2Paid()) {
            $date1 = $this->getPayment1Paid();
            if ($date1) {
                $after_date1 = $date1->diff(new \DateTime())->days;
                $betweenPeriodDays = $region->getDiscount2BetweenPeriodDays();
                $start_discount_2_second = $region->getDiscount2FirstDays() + $betweenPeriodDays;
                if ($region->isDiscount2SecondEnabled()
                    && $after_date1 > $start_discount_2_second
                    && $after_date1 <= $start_discount_2_second + $region->getDiscount2SecondDays()
                ) {
                    $enabled = true;
                }
            }
        }

        return $enabled;
    }

    /**
     * Возвращает сумму скидки для второй оплаты
     */
    public function getSecondPaidDiscount()
    {
        $region = $this->getRegion();

        if ($this->isDiscount2FirstEnabled()) {
            return $region->getDiscount2FirstAmount();
        } elseif ($this->isDiscount2SecondEnabled()) {
            return  $region->getDiscount2SecondAmount();
        }
        return 0;
    }

    public function getFullName()
    {
        return $this->getLastName().' '.$this->getFirstName().' '.$this->getPatronymic();
    }

    /**
     * Возвращает true, если для первой оплаты использовался Промокод
     *
     * @return bool
     */
    public function isPromoUsedFor1()
    {
        $logs = $this->getPaymentLogs();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $comment = json_decode($log->getComment(), true);
            if (isset($comment['categories']) && $log->getPaid()) {
                return (bool)$log->getPromoKey();
            }
        }
        return false;
    }

    /**
     * Возвращает true, если для второй оплаты использовался Промокод
     *
     * @return bool
     */
    public function isPromoUsedFor2()
    {
        $logs = $this->getPaymentLogs();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $comment = json_decode($log->getComment(), true);
            if (isset($comment['services']) && $log->getPaid()) {
                return (bool)$log->getPromoKey();
            }
        }
        return false;
    }

    /**
     * Возвращает true, если первая оплата была осуществлена в офисе
     *
     * @return bool
     */
    public function is1OfflinePaid()
    {
        $logs = $this->getPaymentLogs();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $comment = json_decode($log->getComment(), true);
            if (isset($comment['categories']) && $log->getPaid()) {
                return (!empty($comment['moderator_id']));
            }
        }
        return false;
    }

    /**
     * Возвращает true, если вторая оплата была осуществлена в офисе
     *
     * @return bool
     */
    public function is2OfflinePaid()
    {
        $logs = $this->getPaymentLogs();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $comment = json_decode($log->getComment(), true);
            if (isset($comment['services']) && $log->getPaid()) {
                return (!empty($comment['moderator_id']));
            }
        }
        return false;
    }

    public function is1PaidOnlineCash()
    {
        $logs = $this->getPaymentLogs();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $comment = json_decode($log->getComment(), true);
            if (isset($comment['categories']) && $log->getPaid()) {
                return (!isset($comment['moderator_id']) && $log->getSum() > 0);
            }
        }
        return false;
    }

    public function is2PaidOnlineCash()
    {
        $logs = $this->getPaymentLogs();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $comment = json_decode($log->getComment(), true);
            if (isset($comment['services']) && $log->getPaid()) {
                return (!isset($comment['moderator_id']) && $log->getSum() > 0);
            }
        }
        return false;
    }

    public function get1Sum()
    {
        $logs = $this->getPaymentLogs();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $comment = json_decode($log->getComment(), true);
            if (isset($comment['categories']) && $log->getPaid()) {
                return $log->getSum();
            }
        }
        return 0;
    }

    public function get2Sum()
    {
        $logs = $this->getPaymentLogs();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $comment = json_decode($log->getComment(), true);
            if (isset($comment['services']) && $log->getPaid()) {
                return $log->getSum();
            }
        }
        return 0;
    }

    public function isSomethingPaid()
    {
        return $this->get1Sum() > 0 || $this->get2Sum() > 0;
    }

    public function getCaptcha()
    {
    }

    public function setCaptcha($captcha)
    {
    }

    public function forcePromoInfo($categories, $services)
    {
        $this->promoKeyUsed1 = false;
        $this->promoKeyUsed2 = false;
        $this->sum1 = 0;
        $this->sum2 = 0;
        if ($this->getPayment1Paid() || $this->getPayment2Paid()) {
            $logs = $this->getPaymentLogs();
            foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
                if ($log->getPromoKey()) {
                    $comments = $log->getComment();
                    $comments = json_decode($comments);

                    if (!empty($comments->categories)) {
                        $userCategories = explode(',', $comments->categories);
                        foreach ($categories as $category) { /** @var $category \My\AppBundle\Entity\Category */
                            if (in_array($category->getId(), $userCategories)) {
                                $this->promoKeyUsed1 = true;
                                $this->sum1 = $log->getSum();
                            }
                        }
                    }

                    if (!empty($comments->services)) {
                        $userServices = explode(',', $comments->services);
                        foreach ($services as $service) { /** @var $service \My\AppBundle\Entity\Service */
                            if (in_array($service->getId(), $userServices)) {
                                if ($service->getType() == 'site_access') {
                                    $this->promoKeyUsed1 = true;
                                    $this->sum1 = $log->getSum();
                                } elseif ($service->getType() == 'training') {
                                    $this->promoKeyUsed2 = true;
                                    $this->sum2 = $log->getSum();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function reduceExamAttempts($examAttempts = 1)
    {
        $this->exam_attempts = $this->exam_attempts - $examAttempts;
    }

    protected function getPhotoUploadRootDir()
    {
        return __DIR__.'/../../../../web'.$this->getPhotoUploadDir();
    }

    protected function getPhotoUploadDir()
    {
        return '/uploads/photos';
    }

    public function checkPurchasedPackages()
    {
        $purchasedPackage = false;
        $packages = $this->getPackages();

        foreach ($packages as $package) {
            /** @var $package \My\AppBundle\Entity\DrivingPackage */
            if (!is_null($package->getSaleAt())) {
                $purchasedPackage = true;
                break;
            }
        }
        
        return $purchasedPackage;
    }
}
