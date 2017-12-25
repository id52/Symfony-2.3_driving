<?php

namespace My\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use My\AppBundle\Controller\ApiController;
use My\AppBundle\Entity\SecondPaymentPost;

class SecondPaymentPostService
{
    protected $em;
    protected $logger;
    protected $url;

    public function __construct(EntityManager $entityManager, Logger $payLog, $url)
    {
        $this->em     = $entityManager;
        $this->logger = $payLog;
        $this->url    = $url;
    }

    public function sendPayment($userId, $logId)
    {
        $secondPaymentPost = new SecondPaymentPost();
        $secondPaymentPost->setUserId($userId);
        $secondPaymentPost->setLogId($logId);

        $this->processPayment($secondPaymentPost);
    }

    public function processPayment(SecondPaymentPost $secondPaymentPost)
    {
        $userId = $secondPaymentPost->getUserId();
        $logId  = $secondPaymentPost->getLogId();

        $secondPaymentPost->setSendedAt(new \DateTime());

        $responseCode = $this->postPaymentData($userId, $logId);

        if ($responseCode > 0 && $responseCode != 403 && $responseCode != 404 && $responseCode < 500) {
            $secondPaymentPost->setArrivedAt(new \DateTime());
        }

        $this->em->persist($secondPaymentPost);
        $this->em->flush();
    }

    protected function postPaymentData($userId, $logId)
    {
        $postFields = http_build_query([
            'user_id' => $userId,
            'sign'    => crypt($userId, ApiController::$salt),
        ]);

        $curlHandler = curl_init($this->url);

        curl_setopt_array($curlHandler, [
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => 1,
            CURLOPT_POST           => 1,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response     = curl_exec($curlHandler);
        $responseCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);

        $this->logger->addInfo(
            'user_ID: '.$userId.'; '
            .'payment_log_ID: '.$logId.'; '
            .'response: '.strval(curl_error($curlHandler) ?: $response)
        );

        curl_close($curlHandler);
        return $responseCode;
    }
}
