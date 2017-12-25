<?php

namespace My\AppBundle\Controller;

use Doctrine\ORM\Query;
use My\AppBundle\Entity\ApiQuestionLog;
use My\AppBundle\Entity\Document;
use My\AppBundle\Entity\Question;
use My\AppBundle\Entity\SupportDialog;
use My\AppBundle\Entity\SupportMessage;
use My\AppBundle\Entity\TestKnowledgeLog;
use My\AppBundle\Entity\TestLog;
use My\AppBundle\Entity\UserOldMobilePhone;
use My\AppBundle\Entity\UserStat;
use My\AppBundle\Form\Type\PhotoFormType;
use My\AppBundle\Form\Type\ProfileFormType;
use My\AppBundle\Form\Type\SupportMessageFormType;
use My\PaymentBundle\Entity\Log as PaymentLog;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Validator\Constraints as Assert;
use My\AppBundle\Util\Time;

class MyController extends MyAbstract
{
    public function profileAction()
    {
        return $this->render('AppBundle:My:profile.html.twig');
    }

    public function profileEditAction(Request $request)
    {
        $form = $this->createForm(new ProfileFormType(), $this->user, array(
            'validation_groups' => array('profile', 'user_edit'),
        ));
        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            $validator = $this->get('validator');

            $not_registration = $form->get('not_registration')->getData();
            if ($not_registration) {
                $names = array(
                    'place_country',
                    'place_region',
                    'place_city',
                    'place_street',
                    'place_house',
                    'place_apartament',
                );
            } else {
                $names = array(
                    'registration_country',
                    'registration_region',
                    'registration_city',
                    'registration_street',
                    'registration_house',
                    'registration_apartament',
                );
            }
            foreach ($names as $name) {
                $field = $form->get($name);
                $errors = $validator->validateValue($field->getData(), new Assert\NotBlank());
                if (count($errors) > 0) {
                    $field->addError(new FormError($errors->get(0)->getMessage()));
                }
            }
            if ($form->isValid()) {
                /** @var $user \My\AppBundle\Entity\User */
                $user = $form->getData();
                $user->addRole('ROLE_USER_FULL_PROFILE');
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                $this->get('security.context')->setToken($token);

                $uow = $this->em->getUnitOfWork();
                $orig = $uow->getOriginalEntityData($user);
                if ($orig['phone_mobile']
                    && $orig['phone_mobile_status'] == 'confirmed'
                    && $orig['phone_mobile'] != $user->getPhoneMobile()
                ) {
                    $old_phone = new UserOldMobilePhone();
                    $old_phone->setUser($user);
                    $old_phone->setPhone($orig['phone_mobile']);
                    $this->em->persist($old_phone);
                    $this->em->flush();
                }

                $this->em->persist($user);
                $this->em->flush();

                return $this->redirect($this->generateUrl('my_profile'));
            }
        }

        /** @var $user \My\AppBundle\Entity\User */
        $user = $this->getUser();

        $notifies = $this->em->getRepository('AppBundle:Setting')->findAll();

        $notifies_mapped = [];
        foreach ($notifies as $notify) {
            $notifies_mapped[ $notify->getKey() ] = $notify->getValue();
        }
        return $this->render('AppBundle:My:profile_edit.html.twig', array(
            'form'      => $form->createView(),
            'photoForm' => $this->createForm(new PhotoFormType(), $this->user)->createView(),
            'cpassForm' => $this->createForm('change_password')->createView(),
            'user'      => $user,
            'notifies'  => $notifies_mapped,
        ));
    }

    public function profilePhotoAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $trans = $this->get('translator');
        $result = array();

        if ($request->isMethod('post')) {
            $form = $this->createForm(new PhotoFormType(), $this->user);
            $form->handleRequest($request);
            if ($form->isValid()) {
                /** @var $user \My\AppBundle\Entity\User */
                $user = $form->getData();
                $user->setUpdatedAt(new \DateTime());
                $this->em->persist($user);
                $this->em->flush();
                $user->photoRecountCoords();
                $this->em->persist($user);
                $this->em->flush();

                $imagine_config = $this->container->get('liip_imagine.filter.manager')->getFilterConfiguration();

                list($width, $height) = getimagesize($user->getPhotoAbsolutePath());
                $filter_pcp_config = $imagine_config->get('photo_crop_preview_new');
                $t_width = $filter_pcp_config['filters']['thumbnail']['size'][0];
                $t_height = $filter_pcp_config['filters']['thumbnail']['size'][1];
                $w_ratio = $width / $t_width;
                $h_ratio = $height / $t_height;
                $ratio = max($w_ratio, $h_ratio);

                $filter_ps_config = $imagine_config->get('photo_small');
                $preview_side_x = $filter_ps_config['filters']['resize']['size'][0];
                $preview_side_y = $filter_ps_config['filters']['resize']['size'][1];
                $coords = $user->getPhotoCoords();

                $result['html'] = $this->renderView('AppBundle:My:photo.html.twig', array(
                    'user'           => $user,
                    'preview_side_x' => $preview_side_x,
                    'preview_side_y' => $preview_side_y,
                    'coords'         => array(
                        'w' => round($coords['w'] / $ratio),
                        'h' => round($coords['h'] / $ratio),
                        'x' => round($coords['x'] / $ratio),
                        'y' => round($coords['y'] / $ratio),
                    ),
                ));
            } else {
                foreach ($form->getErrors() as $error) {
                    $result['errors'][] = $error->getMessage();
                }
            }
        } else {
            $result['errors'][] = $trans->trans('errors.not_post');
        }

        return new JsonResponse($result);
    }

    public function profilePhotoUpdateAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $trans = $this->get('translator');
        $result = array();

        if ($request->isMethod('post')) {
            if ($this->user->getPhoto()) {
                $imagine_config = $this->container->get('liip_imagine.filter.manager')->getFilterConfiguration();
                list($width, $height) = getimagesize($this->user->getPhotoAbsolutePath());
                $filter_pcp_config = $imagine_config->get('photo_crop_preview_new');
                $t_width = $filter_pcp_config['filters']['thumbnail']['size'][0];
                $t_height = $filter_pcp_config['filters']['thumbnail']['size'][1];
                $w_ratio = $width / $t_width;
                $h_ratio = $height / $t_height;
                $ratio = max($w_ratio, $h_ratio);

                $this->user->photoRemoveUploadCache();
                $this->user->setPhotoCoords(array(
                    'w' => round($request->get('coords_w') * $ratio),
                    'h' => round($request->get('coords_h') * $ratio),
                    'x' => round($request->get('coords_x') * $ratio),
                    'y' => round($request->get('coords_y') * $ratio),
                ));
                $this->em->persist($this->user);
                $this->em->flush();

                $result['photo_view'] = $this->renderView('AppBundle:My:photo_view.html.twig', array(
                    'user' => $this->user,
                ));
            } else {
                $result['errors'][] = $trans->trans('errors.photo_not_found');
            }
        } else {
            $result['errors'][] = $trans->trans('errors.not_post');
        }

        return new JsonResponse($result);
    }

    public function profilePhotoViewAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $trans = $this->get('translator');
        $result = array();

        if ($request->isMethod('post')) {
            if ($this->user->getPhoto()) {
                $result['photo_view'] = $this->renderView('AppBundle:My:photo_view.html.twig', array(
                    'user' => $this->user,
                ));
            } else {
                $result['photo_view'] = null;
            }
        } else {
            $result['errors'][] = $trans->trans('errors.not_post');
        }

        return new JsonResponse($result);
    }

    public function profilePhotoEditAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $trans = $this->get('translator');
        $result = array();

        if ($request->isMethod('post')) {
            if ($this->user->getPhoto()) {
                $imagine_config = $this->container->get('liip_imagine.filter.manager')->getFilterConfiguration();

                list($width, $height) = getimagesize($this->user->getPhotoAbsolutePath());
                $filter_pcp_config = $imagine_config->get('photo_crop_preview');
                $t_width = $filter_pcp_config['filters']['thumbnail']['size'][0];
                $t_height = $filter_pcp_config['filters']['thumbnail']['size'][1];
                $w_ratio = $width / $t_width;
                $h_ratio = $height / $t_height;
                $ratio = max($w_ratio, $h_ratio);

                $filter_ps_config = $imagine_config->get('photo_small');
                $preview_side_x = $filter_ps_config['filters']['resize']['size'][0];
                $preview_side_y = $filter_ps_config['filters']['resize']['size'][1];
                $coords = $this->user->getPhotoCoords();

                $result['html'] = $this->renderView('AppBundle:My:photo.html.twig', array(
                    'user'           => $this->user,
                    'preview_side_x' => $preview_side_x,
                    'preview_side_y' => $preview_side_y,
                    'coords'         => array(
                        'w' => round($coords['w'] / $ratio),
                        'h' => round($coords['h'] / $ratio),
                        'x' => round($coords['x'] / $ratio),
                        'y' => round($coords['y'] / $ratio),
                    ),
                ));
            } else {
                $result['errors'][] = $trans->trans('errors.photo_not_found');
            }
        } else {
            $result['errors'][] = $trans->trans('errors.not_post');
        }

        return new JsonResponse($result);
    }

    public function mobileStatusAjaxAction(Request $request)
    {
        $result = array();

        if ($request->isXmlHttpRequest() && $this->user->getPhoneMobile()) {
            $code = '';
            $symbols = str_split('1234567890');
            for ($i = 0; $i < 6; $i ++) {
                $code .= $symbols[mt_rand(0, count($symbols) - 1)];
            }
            $phone = '7'.strtr($this->user->getPhoneMobile(), array('+' => '', ' ' => ''));

            $sms_uslugi_ru = $this->get('sms_uslugi_ru');
            $text = $this->get('translator')->trans('mobile_confirm_text', array('%code%' => $code));
            $sended = $sms_uslugi_ru->query($phone, $text);

            if ($sended) {
                $this->user->setPhoneMobileStatus('sended');
                $this->user->setPhoneMobileCode($code);
                $this->em->persist($this->user);
                $this->em->flush();
            }
        }

        return new JsonResponse($result);
    }

    public function mobileConfirmAjaxAction(Request $request)
    {
        $result = array();

        if ($request->isXmlHttpRequest()
            && $this->user->getPhoneMobile()
            && 'sended' == $this->user->getPhoneMobileStatus()
        ) {
            if (strtoupper($request->get('code')) == $this->user->getPhoneMobileCode()) {
                $this->user->setPhoneMobileStatus('confirmed');
                $this->user->setPhoneMobileCode(null);

                $this->em->persist($this->user);
                $this->em->flush();

                $this->get('app.notify')->sendAfterConfirmMobile($this->user);
            } else {
                $result['error'] = true;
            }
        } else {
            $result['error'] = true;
        }

        return new JsonResponse($result);
    }

    public function uploadDocsAction(Request $request, $type)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $cntxt = $this->get('security.context');
        if (!$cntxt->isGranted('ROLE_USER_PAID2')) {
            throw $this->createNotFoundException();
        }

        $result = array(
            'files' => array(),
        );

        $notFilial = false;
        $region = $this->user->getRegion();
        if ($region) {
            $notFilial = $region->getFilialNotExisting();
        }

        if (!$notFilial) {
            throw $this->createNotFoundException();
        }

        $licm = $this->container->get('liip_imagine.cache.manager');
        foreach ($request->files as $files) {
            foreach ($files as $file) {
                /** @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */

                if ($file->getClientSize() > 5 * 1024 * 1024) {
                    $result = ['errors' => ['Слишком большой размер файла.']];
                }

                if (!in_array($file->getMimeType(), ['image/jpg', 'image/jpeg', 'image/png'])) {
                    $result = ['errors' => ['Неразрешённый формат файла.']];
                } else {
                    $doc = new Document();
                    $doc->setUploadFile($file);
                    $doc->setType($type);

                    $doc->setUser($this->user);

                    $this->em->persist($this->user);
                    $this->em->persist($doc);
                    $this->em->flush();

                    $result['files'][] = [
                        'id' => $doc->getId(),
                        'webPath' => $licm->getBrowserPath($doc->getWebPath(), 'image_profile_docs'),
                        'webPathOrig' => $doc->getWebPath(),
                    ];
                }
            }
        }

        return new JsonResponse($result);
    }

    public function deleteDocsAction(Request $request)
    {
        $id = $request->get('id');

        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $notFilial = false;
        $region = $this->user->getRegion();
        if ($region) {
            $notFilial = $region->getFilialNotExisting();
        }

        if (!$notFilial) {
            throw $this->createNotFoundException();
        }

        $doc = $this->em->find('AppBundle:Document', $id);
        if (!$doc) {
            throw $this->createNotFoundException();
        }

        if ($doc->getStatus() == 'confirm') {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('post')) {
            $this->user->removeDocument($doc);
            $doc->setUser();
        }
        $this->em->persist($this->user);
        $this->em->persist($doc);
        $this->em->flush();

        return new JsonResponse('sucess');
    }

    public function notifiesAction()
    {
        $notifies = $this->em->getRepository('AppBundle:Notify')->createQueryBuilder('n')
            ->andWhere('n.user = :user')->setParameter(':user', $this->user)
            ->orderBy('n.sended_at', 'DESC')
            ->getQuery()->getArrayResult();

        return $this->render('AppBundle:My:notifies.html.twig', array(
            'notifies' => $notifies,
        ));
    }

    public function notifyReadAction($id)
    {
        $notify = $this->em->getRepository('AppBundle:Notify')->find($id);
        if (!$notify) {
            throw $this->createNotFoundException('Notify for id "'.$id.'" not found.');
        }

        if (!$notify->getReaded()) {
            $notify->setReaded(true);
            $this->em->persist($notify);
            $this->em->flush();

            $notifies = $this->em->getRepository('AppBundle:Notify')->createQueryBuilder('n')
                ->select('COUNT(n)')
                ->andWhere('n.user = :user')->setParameter(':user', $this->user)
                ->andWhere('n.readed = :readed')->setParameter(':readed', false)
                ->getQuery()->getSingleScalarResult();
            $this->user->setNotifiesCnt($notifies);
            $this->em->persist($this->user);
            $this->em->flush();
        }

        return $this->render('AppBundle:My:notify_read.html.twig', array(
            'notify' => $notify,
        ));
    }

    public function prepareOweStagePaidAction()
    {
        $session = $this->get('session');
        $lastOweStage = $this->em->getRepository('AppBundle:OweStage')
            ->findOneBy(['user' => $this->user->getId()], ['end' => 'DESC']);

        $now = new \DateTime();
        if ($lastOweStage->getEnd() < $now) {
            $session->set('paid_owe_error', ['message' => 'Время для онлайн оплаты закончилось.']);

            return $this->redirect($this->generateUrl('my_payments'));
        }

        if (!$lastOweStage || $lastOweStage->getEnd() < new \DateTime()) {
            $session->set('paid_owe_error', ['message' => 'Не удалось найти параметры для оплаты задолженности.']);

            return $this->redirect($this->generateUrl('my_payments'));
        }

        $session->set('paid_owe_stage', ['sum' => $lastOweStage->getSum()]);

        return $this->redirect($this->generateUrl('my_payments_pay'));
    }

    public function choiceDriverPaymentAction(Request $request)
    {
        $cntxt = $this->get('security.context');
        if (!$cntxt->isGranted('ROLE_USER_PAID2') || $this->user->getFinalDocStatus() == 'doc_is_picked') {
            throw $this->createNotFoundException('Not found');
        }

        $region = $this->user->getRegion();
        if ($region) {
            $notFilial = $region->getFilialNotExisting();
        } else {
            throw $this->createNotFoundException('Not found region');
        }

        if (!$notFilial) {
            throw $this->createNotFoundException();
        }

        $category = $this->user->getCategory();
        if (!$category) {
            throw $this->createNotFoundException('Not found category');
        }
        $packages = $this->em->getRepository('AppBundle:DrivingPackage')->createQueryBuilder('p')
            ->andWhere('p.user = :user')->setParameter('user', $this->user)
            ->andWhere('p.sale_at IS NOT NULL')
            ->getQuery()->execute();

        if (count($packages)) {
            $primary = false;
        } else {
            $primary = true;
        }

        $drive_info = $this->user->getDriveInfo();

        $defaultOptions = array();

        // формируем данные для заполнения по умолчанию
        if (isset($drive_info['drive_condition'])) {
            $checkDrvConditions = $this->em->getRepository('AppBundle:DrivingConditions')
                ->getAvalableConditions(
                    $primary,
                    $category,
                    isset($drive_info['with_at']) ? $drive_info['with_at'] : null,
                    $this->user,
                    $drive_info['drive_condition']
                );
            if ($checkDrvConditions) {
                /** @var  $checkDrvCondition \My\AppBundle\Entity\DrivingConditions */
                $checkDrvCondition = $checkDrvConditions[0];
                $place = $this->em->getRepository('AppBundle:RegionPlace')->find($drive_info['place']);

                // Получаем еще раз список всех(без ограничения по id вождения ) доступных условий вождения с
                // указанными параметрами (будет использоваться для формирования списков условий и классов обслуживания)
                $drConditions = $this->em->getRepository('AppBundle:DrivingConditions')
                    ->getAvalableConditions(
                        $primary,
                        $category,
                        isset($drive_info['with_at']) ? $drive_info['with_at'] : null,
                        $this->user
                    );

                $drConditionsData = array();
                foreach ($drConditions as $condition) {
                    /** @var $condition \My\AppBundle\Entity\DrivingConditions */
                    $drConditionsData[$condition->getId()] = $condition->getName();
                }

                $services = $this->em->getRepository('AppBundle:ClassService')->createQueryBuilder('cs')
                    ->leftJoin('cs.conditions', 'c')
                    ->andWhere('c.id IN (:ids)')->setParameter('ids', $drConditions)
                    ->getQuery()->execute();

                $serviceData = array();
                foreach ($services as $service) {
                    /** @var $service \My\AppBundle\Entity\ClassService */
                    $serviceData[$service->getId()] = $service->getName();
                }

                $drvBuilder = $this->em->getRepository('AppBundle:RegionPlacePrice')->createQueryBuilder('rp')
                    ->andWhere('rp.category = :category')->setParameter('category', $this->user->getCategory())
                    ->andWhere('rp.place = :place')->setParameter('place', $place)
                    ->andWhere('rp.condition = :condition')->setParameter('condition', $checkDrvCondition)
                    ->groupBy('rp.category');

                if (isset($drive_info['with_at'])) {
                    $drvBuilder->andWhere('rp.with_at = :with_at')->setParameter('with_at', $drive_info['with_at']);
                }
            }
        }

        $checkDrvConditionsWithAt = $this->em->getRepository('AppBundle:DrivingConditions')
            ->getAvalableConditions($primary, $category, true, $this->user);
        $checkDrvConditionsWithNoAt = $this->em->getRepository('AppBundle:DrivingConditions')
            ->getAvalableConditions($primary, $category, false, $this->user);

        //Проверяем нужно ли показывать галочку выбора автомата
        if (isset($drive_info['with_at'])) {
            if ($drive_info['with_at']) {
                $drConditions = $checkDrvConditionsWithAt;
                $typeWithAt = 1;
            } else {
                $drConditions = $checkDrvConditionsWithNoAt;
                $typeWithAt = 2;
            }
        } elseif (!isset($drive_info['with_at']) && isset($drive_info['drive_condition'])) {
            $drConditions = $checkDrvConditionsWithNoAt;
            $typeWithAt = 2;
        } else {
            if ($checkDrvConditionsWithAt && !$checkDrvConditionsWithNoAt) {
                $typeWithAt = 1;
                $drConditions = $checkDrvConditionsWithAt;
            } elseif (!$checkDrvConditionsWithAt && $checkDrvConditionsWithNoAt) {
                $typeWithAt = 2;
                $drConditions = $checkDrvConditionsWithNoAt;
            } else {
                $drConditions = array_merge($checkDrvConditionsWithAt, $checkDrvConditionsWithNoAt);
                $typeWithAt = 0;
            }
        }

        $drConditionsIds = array();
        foreach ($drConditions as $condition) {
            /** @var $condition \My\AppBundle\Entity\DrivingConditions */
            $drConditionsIds[] = $condition->getId();
        }

        $showWarningText = false;
        if (count($drConditionsIds) == 0) {
            $showWarningText = true;
        }

        $placesQb = $this->em->getRepository('AppBundle:RegionPlace')->createQueryBuilder('pl')
            ->andWhere('pl.region = :region')->setParameter('region', $region)
            ->leftJoin('pl.categories', 'cat')
            ->andWhere('cat.id = :category')->setParameter('category', $category)
            ->leftJoin('pl.place_prices', 'pr')
            ->andWhere('pr.active = :active')->setParameter('active', true)
            ->andWhere('pr.category = :cat')->setParameter('cat', $category)
            ->leftJoin('pr.condition', 'dr_c')
            ->andWhere('dr_c.id IN (:ids)')->setParameter('ids', $drConditionsIds)
        ;

        if ($typeWithAt == 1) {
            $placesQb->andWhere('dr_c.with_at = :with_at')->setParameter('with_at', true);
        } elseif ($typeWithAt == 2) {
            $placesQb->andWhere('dr_c.with_at = :with_at')->setParameter('with_at', false);
        }

        $places = $placesQb->getQuery()->execute();

        $formErrors = array();
        if ($request->isMethod('post')) {
            $driving = $request->get('driving');
            $place = null;

            //проверям наличие необходимых данных для определения цены
            $drvCondition = isset($driving['driving_condition']) ? intval($driving['driving_condition']) : false;
            $place = isset($driving['place']) ? intval($driving['place']) : false;
            $classService = isset($driving['class_service']) ? intval($driving['class_service']) : false;
            if (!isset($drive_info['with_at']) && $typeWithAt === 0) {
                $with_at = isset($driving['with_at']) ? true : false;
            } elseif ($typeWithAt == 1) {
                $with_at = true;
            } elseif ($typeWithAt == 2) {
                $with_at = false;
            } else {
                $with_at = $drive_info['with_at'];
            }
            if (!$place) {
                $formErrors['place'] = 'Не выбрано место вождения.';
            } else {
                $place = $this->em->find('AppBundle:RegionPlace', $place);
                if (!$place) {
                    $formErrors['place'] = 'Не найдено место вождения.';
                    $place = false;
                }
            }
            if (!$classService) {
                $formErrors['class_service'] = 'Не выбран класс вождения.';
            } else {
                $classService = $this->em->find('AppBundle:ClassService', $classService);
                if (!$classService) {
                    $formErrors['class_service'] = 'Не найдено класса вождения.';
                    $classService = false;
                }
            }

            if (!$drvCondition) {
                $formErrors['class_service'] = 'Не выбрано условие вождения.';
            } else {
                $packages = $this->em->getRepository('AppBundle:DrivingPackage')->createQueryBuilder('p')
                    ->andWhere('p.user = :user')->setParameter('user', $this->user)
                    ->andWhere('p.sale_at IS NOT NULL')
                    ->getQuery()->execute();

                if (count($packages)) {
                    $primary = false;
                } else {
                    $primary = true;
                }

                $drvConditions = $this->em->getRepository('AppBundle:DrivingConditions')
                    ->getAvalableConditions($primary, $category, $with_at, $this->user, $drvCondition);
                if (!$drvConditions) {
                    $formErrors['driving_condition'] = 'Не найдено класса вождения.';
                    $drvCondition = false;
                } else {
                    /** @var  $drvCondition \My\AppBundle\Entity\DrivingConditions */
                    $drvCondition = $drvConditions[0];
                }
            }

            $drvPrice = $this->em->getRepository('AppBundle:RegionPlacePrice')->createQueryBuilder('rp')
                ->andWhere('rp.category = :category')->setParameter('category', $category)
                ->andWhere('rp.place = :place')->setParameter('place', $place)
                ->andWhere('rp.condition = :condition')->setParameter('condition', $drvCondition)
                ->andWhere('rp.with_at = :with_at')->setParameter('with_at', $with_at)
                ->groupBy('rp.category')
                ->getQuery()->execute();

            if (!$drvPrice) {
                $formErrors['driving_price'] = 'Не найдена цена на условие вождения.';
                $drvPrice = false;
            } else {
                /** @var  $drvPrice \My\AppBundle\Entity\RegionPlacePrice */
                $drvPrice = $drvPrice[0];
            }

            // записываем данные в сессию
            if ($category && $place && $classService && $drvCondition && $drvPrice) {
                if ($primary) {
                    $this->em->persist($this->user);
                    $this->em->flush();

                    $comments = array(
                        'categories'      => $category->getId(),
                        'paid'            => 'first_drive',
                        'drive_condition' => $drvCondition->getId(),
                        'place'           => $place->getId(),
                        'with_at'         => $with_at,
                    );
                } else {
                    $this->em->flush();
                    $comments = array(
                        'categories'      => $category->getId(),
                        'paid'            => 'second_drive',
                        'drive_condition' => $drvCondition->getId(),
                        'place'           => $place->getId(),
                        'with_at'         => $with_at,
                    );
                }

                $sum = max($drvPrice->getPrice(), 0);
                if ($sum == 0) {
                    $packages = $this->em->getRepository('AppBundle:DrivingPackage')
                        ->getNotSaleAndNotRezervPackages($drvCondition, $this->user);

                    if ($packages) {
                        $package = $packages[0];
                    }
                    /** @var $package \My\AppBundle\Entity\DrivingPackage */

                    $package->setUser($this->user);
                    $package->setRezervAt(new \DateTime());
                    $package->setSaleAt(new \DateTime());
                    $this->em->persist($package);

                    $this->user->addPackage($package);
                    $this->em->persist($this->user);

                    $log = new PaymentLog();
                    $log->setUser($this->user);
                    $log->setSum($drvPrice->getPrice());
                    $log->setComment(json_encode($comments));
                    $log->setPaid(true);
                    $log->setPackage($package);

                    $this->em->persist($log);
                    $this->em->flush();

                    return $this->redirect($this->generateUrl('my_payments'));
                }

                $session = $this->get('session');
                $session->set('payment', array(
                    'sum'     => $sum,
                    'comment' => $comments,
                ));
                $session->save();
                return $this->redirect($this->generateUrl('my_payments_pay'));
            }
        }

        return $this->render('AppBundle:My:choice_driving_condition.html.twig', array(
            'places'     => $places,
            'category'   => $category,
            'noChoiceAt' => isset($drive_info['with_at']) ? true : false,
            'type_at'    => $typeWithAt,
            'default'    => $defaultOptions,
            'warning'    => $showWarningText,
        ));
    }

    public function paymentsAction(Request $request)
    {
        if ('confirmed' != $this->user->getPhoneMobileStatus()) {
            return $this->render('AppBundle:My:payments_mobile_not_confirmed.html.twig');
        }

        if (!$this->user->getRegion()) {
            return $this->render('AppBundle:My:payments_region_not_found.html.twig');
        }

        if ($request->isMethod('post')) {
            if (!empty($request->get('terms_and_conditions'))) {
                $this->user->setTermsAndConditions(true);
                $this->em->persist($this->user);
                $this->em->flush();
            } else {
                $this->user->setTermsAndConditions(false);
                $this->em->persist($this->user);
                $this->em->flush();
            };

            $ids = explode(',', $request->get('ids'));

            $services_prices = $this->em->getRepository('AppBundle:ServicePrice')->createQueryBuilder('sp')
                ->andWhere('sp.active = :active')->setParameter(':active', true)
                ->andWhere('sp.region = :region')->setParameter(':region', $this->user->getRegion())
                ->leftJoin('sp.service', 's')->addSelect('s.type')
                ->andWhere('s.id IN (:ids)')->setParameter(':ids', $ids)
                ->getQuery()->getArrayResult();

            $is_second = false;
            $sum = 0;
            $services = array();
            foreach ($services_prices as $price) {
                $sum += $price[0]['price'];
                if ($this->user->getByApi()) {
                    $sum += $this->user->getByApiComb() ? $price[0]['price_comb'] : 0;
                    $sum += $this->user->getByApiExpr() ? $price[0]['price_expr'] : 0;
                }
                $services[] = $price[0]['service_id'];
                if ($price['type']) {
                    $is_second = true;
                }
            }

            //promo?
            $key = null;
            $promo_key = $request->get('promo_key');
            if ($promo_key) {
                $data = $this->em->getRepository('AppBundle:PromoKey')->createQueryBuilder('pk')
                    ->leftJoin('pk.logs', 'log', 'WITH', 'log.paid = :lp')->setParameter('lp', true)
                    ->addSelect('COUNT(log) logsCount')
                    ->leftJoin('pk.promo', 'pr')
                    ->andWhere('pk.hash = :pkh')->setParameter('pkh', $promo_key)
                    ->andWhere('pk.active = :pka')->setParameter('pka', true)
                    ->getQuery()->getResult();
                if ($data[0][0]) {
                    /** @var $key \My\AppBundle\Entity\PromoKey */
                    $key = $data[0][0];
                    $promo = $key->getPromo();
                    $logsCount = $data[0]['logsCount'];
                    $promoRestricted = $key->getPromo()->getRestricted();

                    if ($promo->getActive() && $key->getType() == 'training') {
                        if (($promoRestricted == 'keys' && $logsCount == 0)
                            || ($promoRestricted == 'users' && $logsCount < $promo->getMaxUsers())
                        ) {
                            $sum -= $key->getDiscount();
                            $sum = max($sum, 0);
                            $key->setActivated(new \DateTime());
                        }
                    }
                }
            }

            if ($is_second) {
                $sum -= $this->user->getCurrentDiscount2();
                $sum = max($sum, 0);
            }

            $comments = array('services' => implode(',', $services));
            if ($key) {
                $comments['key'] = $key->getHash();
            }

            if ($sum > 0) {
                $session = $this->get('session');
                $payment = array(
                    'sum'     => $sum,
                    'comment' => $comments,
                );
                if ($key) {
                    $payment['key_id'] = $key->getId();
                }
                $session->set('payment', $payment);
                $session->save();
                return $this->redirect($this->generateUrl('my_payments_pay'));
            } else {
                $this->em->getRepository('AppBundle:User')->removeTriedsAndReservists($this->getUser());
                $log = new PaymentLog();
                $log->setUser($this->user);
                $log->setSum($sum);
                $log->setPromoKey($key);
                $log->setComment(json_encode($comments));
                $log->setPaid(true);
                $this->em->persist($log);
                $this->em->flush();

                //check if all services has been paid
                $all_services = array();
                $services = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
                    ->andWhere('s.type IS NOT NULL')
                    ->getQuery()->getArrayResult();
                foreach ($services as $service) {
                    $all_services[] = $service['id'];
                }

                $logs = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
                    ->andWhere('l.user = :user')->setParameter(':user', $this->user)
                    ->andWhere('l.paid = :paid')->setParameter(':paid', true)
                    ->getQuery()->getArrayResult();
                foreach ($logs as $l) {
                    $comment = json_decode($l['comment'], true);
                    if (!empty($comment['services'])) {
                        $ids = explode(',', $comment['services']);
                        foreach ($ids as $id) {
                            if (in_array($id, $all_services)) {
                                unset($all_services[array_search($id, $all_services)]);
                            }
                        }
                    }
                }

                if (empty($all_services) && !$this->user->hasRole('ROLE_USER_PAID2')) {
                    $this->user->addRole('ROLE_USER_PAID2');
                    $this->user->setPayment2Paid(new \DateTime());
                    $this->em->persist($this->user);

                    /** @var $userStat UserStat */
                    $userStat = $this->user->getUserStat();
                    if ($userStat) {
                        $pay2Type = $key->getActivated() ? 'promo' : 'regular';

                        $userStat->setPay2Type($pay2Type);
                        $this->em->persist($userStat);
                    }

                    if ($this->user->getByApi()
                        && in_array($this->container->getParameter('server_type'), ['prod', 'qa'])) {
                        $this->get('app.second_payment_post')->sendPayment($this->user->getId(), $log->getId());
                    }

                    $this->em->flush();

                    $cntxt = $this->get('security.context');
                    $authManager = $this->get('security.authentication.manager');
                    $token = $cntxt->getToken();
                    $token->setUser($this->user);
                    $token = $authManager->authenticate($token);
                    $cntxt->setToken($token);

                    $this->get('app.notify')->sendAfterSecondPayment($this->user, ($key ? 'promo' : ''));
                } else {
                    $this->get('app.notify')->sendAfterPayment($this->user, ($key ? 'promo' : ''));
                }

                return $this->redirect($this->generateUrl('my_payments'));
            }
        }

        $services = array();

        $qb = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
            ->addSelect('rp.price, rp.price_comb, rp.price_expr')
            ->leftJoin('s.regions_prices', 'rp')
            ->andWhere('rp.active = :active')->setParameter(':active', true)
            ->andWhere('s.type != :type OR s.type IS NULL')->setParameter(':type', 'site_access')
            ->andWhere('rp.region = :region')->setParameter(':region', $this->user->getRegion())
        ;

        if ($this->user->getByApiComb()) {
            $qb->andWhere('s.display = :display')->setParameter('display', 'comb');
        }

        if ($this->user->getByApiExpr()) {
            $qb->andWhere('s.display = :display')->setParameter('display', 'expr');
        }

        $services_orig = $qb->getQuery()->getArrayResult();

        foreach ($services_orig as $service) {
            $price = $service['price'];
            if ($this->user->getByApi()) {
                $price += $this->user->getByApiComb() ? $service['price_comb'] : 0;
                $price += $this->user->getByApiExpr() ? $service['price_expr'] : 0;
            }
            $services[$service[0]['id']] = array_merge($service[0], array('price' => $price));
        }

        $categories = array();
        $categories_orig = $this->em->getRepository('AppBundle:Category')->createQueryBuilder('c')
            ->addSelect('cp.price')
            ->leftJoin('c.regions_prices', 'cp')
            ->andWhere('cp.region = :region')->setParameter(':region', $this->user->getRegion())
            ->getQuery()->getArrayResult();
        foreach ($categories_orig as $category) {
            $categories[$category[0]['id']] = array_merge($category[0], array('price' => $category['price']));
        }

        $is_paid_required = false;
        $paid_payments = array();
        $logs = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
            ->andWhere('l.user = :user')->setParameter(':user', $this->user)
            ->andWhere('l.paid = :paid')->setParameter(':paid', true)
            ->addOrderBy('l.updated_at', 'ASC')
            ->getQuery()->getResult();
        foreach ($logs as $log) { /** @var $log \My\PaymentBundle\Entity\Log */
            $name = null;
            $primary = null;
            $revertsLog = $this->em->getRepository('PaymentBundle:RevertLog')->findOneBy([
                'payment_log' => $log->getId(),
                'paid'        => true,
            ]);
            if ($log->getPackage()) {
                $drCondition = $log->getPackage()->getCondition();

                $name = $drCondition->getName();
                $primary = $drCondition->getIsPrimary();
            }
            $log = array(
                'id'                 => $log->getId(),
                's_type'             => $log->getSType(),
                's_id'               => $log->getSId(),
                'sum'                => $log->getSum(),
                'paid'               => $log->getPaid(),
                'comment'            => $log->getComment(),
                'created_at'         => $log->getCreatedAt(),
                'updated_at'         => $log->getUpdatedAt(),
                'promoKey'           => $log->getPromoKey(),
                'driving_name'       => $name,
                'driving_primary'    => $primary,
                'categories'         => array(),
                'services'           => array(),
                'driving_conditions' => array(),
                'owe_stage'          => $log->getOweStage(),
                'revert'             => $revertsLog,
            );
            $comment = json_decode($log['comment'], true);

            $log['driving_conditions'] = array();
            if ($name != null) {
                $paid_payments[] = $log;
            }
            if ($log['owe_stage']) {
                $paid_payments[] = $log;
            }

            //ID-модератора, который добавил пользователя
            $moderatorId = (!empty($comment['moderator_id'])) ? $comment['moderator_id'] : null;

            $log['services'] = array();
            if (!empty($comment['services'])) {
                $services_ids = explode(',', $comment['services']);
                foreach ($services_ids as $service_id) {
                    if (isset($services[$service_id])) {
                        $log['services'][$service_id] = $services[$service_id];
                        if (isset($services[$service_id]['type'])) {
                            $log['required'] = true;
                            $is_paid_required = true;
                        }
                    }
                    if (!$revertsLog) {
                        unset($services[$service_id]);
                    }
                }
                if (count($log['services']) > 0) {
                    if ($moderatorId) {
                        $log['moderator_id'] = $moderatorId;
                    }
                    $paid_payments[] = $log;
                }
            }

            $log['categories'] = array();
            if (!empty($comment['categories']) && $name == null) {
                $categories_ids = explode(',', $comment['categories']);
                foreach ($categories_ids as $category_id) {
                    if (isset($categories[$category_id])) {
                        $log['categories'][$category_id] = $categories[$category_id];
                    }
                }
                if (count($log['categories']) > 0) {
                    if (!empty($comment['auto_promo'])) {
                        $log['auto_promo'] = $comment['auto_promo'];
                    }
                    if ($moderatorId) {
                        $log['moderator_id'] = $moderatorId;
                    }
                    $paid_payments[] = $log;
                }
            }
        }

        $payments = array();
        $required_services = array();
        foreach ($services as $service_id => $service) {
            if ($service['type']) {
                $required_services[$service_id] = $service;
                unset($services[$service_id]);
            }
        }
        if (count($required_services) > 0) {
            $sum = 0;
            foreach ($required_services as $service) {
                $sum += $service['price'];
            }

            $date    = $this->user->getPayment1Paid();
            $dueDate = null;
            $region  = $this->user->getRegion();

            if ($date && $this->user->isDiscount2FirstEnabled()) {
                $dueDate = clone $date;
                $dueDate->add(new \DateInterval('P'.($region->getDiscount2FirstDays() + 1).'D'));
            } elseif ($date && $this->user->isDiscount2SecondEnabled()) {
                $dueDate = clone $date;
                $dueDate->add(new \DateInterval(
                    'P'.($region->getDiscount2FirstDays()
                        + $region->getDiscount2SecondDays() + 1).'D'
                ));
            }

            $countdown  = $dueDate ? $date->diff($dueDate, true) : null;
            $payments[] = [
                'required'     => true,
                'services'     => $required_services,
                'sum'          => $sum,
                'discount'     => $this->user->getCurrentDiscount2(),
                'end_time'     => $dueDate,
                'seconds_left' => Time::getAllSeconds($countdown),
            ];
        }

        foreach ($services as $service_id => $service) {
            $payments[] = array(
                'required' => false,
                'services' => array($service_id => $services[$service_id]),
                'sum'      => $service['price'],
            );
        }

        $session = $this->get('session');
        $owePaidError = $session->get('paid_owe_error');

        return $this->render('AppBundle:My:payments.html.twig', array(
            'is_paid_required' => $is_paid_required,
            'paid_payments'    => $paid_payments,
            'payments'         => $payments,
            'user'             => $this->getUser(),
            'owe_paid_error'   => $owePaidError,
        ));
    }

    public function paymentsSecondPaymentAction()
    {
        $services = array();
        $services_orig = $this->em->getRepository('AppBundle:Service')->createQueryBuilder('s')
            ->addSelect('rp.price, rp.price_comb, rp.price_expr')
            ->leftJoin('s.regions_prices', 'rp')
            ->andWhere('rp.active = :active')->setParameter(':active', true)
            ->andWhere('s.type    = :type')->setParameter(':type', 'training')
            ->andWhere('rp.region = :region')->setParameter(':region', $this->user->getRegion())
            ->getQuery()->getArrayResult();
        foreach ($services_orig as $service) {
            $price = $service['price'];
            if ($this->user->getByApi()) {
                $price += $this->user->getByApiComb() ? $service['price_comb'] : 0;
                $price += $this->user->getByApiExpr() ? $service['price_expr'] : 0;
            }
            $services[$service[0]['id']] = array_merge($service[0], array('price' => $price));
        }

        $logs = $this->em->getRepository('PaymentBundle:Log')->createQueryBuilder('l')
            ->andWhere('l.user = :user')->setParameter(':user', $this->user)
            ->andWhere('l.paid = :paid')->setParameter(':paid', true)
            ->getQuery()->getArrayResult();
        foreach ($logs as $l) {
            $revertsLog = $this->em->getRepository('PaymentBundle:RevertLog')->findOneBy([
                'payment_log' => $l['id'],
                'paid'        => true,
            ]);
            $comment = json_decode($l['comment'], true);
            if (!empty($comment['services'])) {
                $ids = explode(',', $comment['services']);
                foreach ($ids as $id) {
                    if (isset($services[$id]) && !$revertsLog) {
                        unset($services[$id]);
                    }
                }
            }
        }

        if (count($services) == 0) {
            return $this->redirect($this->generateUrl('my_payments'));
        }

        $sum = 0;
        foreach ($services as $service) {
            $sum += $service['price'];
        }

        return $this->render('AppBundle:My:payments_2.html.twig', array(
            'services' => $services,
            'sum'      => $sum,
            'discount' => $this->user->getCurrentDiscount2(),
        ));
    }

    public function paymentsPayAction()
    {
        $session = $this->get('session');
        $payment = $session->get('payment');
        $paymentOweStage = $session->get('paid_owe_stage');

        if ((!$payment || !isset($payment['sum']) || !isset($payment['comment'])) && !$paymentOweStage) {
            $session->remove('payment');
            return $this->redirect($this->generateUrl('my_payments'));
        }
        return $this->render('AppBundle:My:choose_payment.html.twig');
    }

    public function testAction(Request $request)
    {
        $session = $request->getSession();
        $s_name = 'test';
        $s_data = $session->get($s_name);
        $amountAnswers      = 0;

        if (!$s_data) {
            $version = $this->em->getRepository('AppBundle:TrainingVersion')->getVersionByUser($this->user);
            if (!$version) {
                throw $this->createNotFoundException('Training version not found.');
            }

            $questions = $this->em->getRepository('AppBundle:Question')->createQueryBuilder('q')
                ->andWhere('q.num IS NOT NULL')
                ->andWhere('q.is_pdd = :is_pdd')->setParameter(':is_pdd', true)
                ->leftJoin('q.versions', 'v')
                ->andWhere('v = :version')->setParameter(':version', $version)
                ->addOrderBy('q.num')
                ->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)->execute();

            $orig_questions = array();
            foreach ($questions as $question) {
                /** @var $question \My\AppBundle\Entity\Question */

                if (!isset($orig_questions[$question->getTicketNum()])) {
                    $orig_questions[$question->getTicketNum()] = array();
                }
                $orig_questions[$question->getTicketNum()][] = $question->getId();
            }

            $translator = $this->get('translator');

            $tickets = array_keys($orig_questions);
            $names_tickets = array();
            foreach ($tickets as $ticket) {
                $names_tickets[$ticket] = $translator->trans('ticket_num', array('%ticket%' => $ticket));
            }

            $form_factory = $this->get('form.factory');
            $form = $form_factory->createNamedBuilder('test')
                ->add('tickets', 'choice', array(
                    'expanded'    => true,
                    'multiple'    => true,
                    'required'    => true,
                    'choices'     => $names_tickets,
                    'constraints' => array(new Assert\NotBlank()),
                ))
                ->add('comments', 'checkbox', array('required' => false))
                ->add('time', 'checkbox', array('required' => false))
                ->getForm();
            $form->handleRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();
                $tickets = $data['tickets'];
                $questions = $orig_questions[$tickets[array_rand($tickets)]];
                $answers = array_fill(0, count($questions), null);

                $time = new \DateTime();
                $end_time = null;
                if ($data['time']) {
                    $end_time = clone $time;
                    $end_time->add(new \DateInterval('PT10M'));
                }

                $log = new TestLog();
                $log->setStartedAt($time);
                $log->setQuestions($questions);
                $log->setAnswers($answers);
                $log->setUser($this->user);
                $this->em->persist($log);
                $this->em->flush();

                $s_data = array(
                    'questions'                         => $questions,
                    'answers'                           => $answers,
                    'extra'                             => array(),
                    'log_id'                            => $log->getId(),
                    'current'                           => 0,
                    'comments'                          => $data['comments'],
                    'end_time'                          => $end_time,
                    'l_activity'                        => new \DateTime(),
                    'amount_of_questions_with_no_extra' => count($questions),
                );
                $session = $request->getSession();
                $session->set($s_name, $s_data);

                return $this->redirect($this->generateUrl('my_test'));
            }

            return $this->render('AppBundle:My:test_entrance.html.twig', array(
                'form' => $form->createView(),
            ));
        } else {
            /** @var $log \My\AppBundle\Entity\TestLog */
            $log = $this->em->getRepository('AppBundle:TestLog')->find($s_data['log_id']);

            if (!$log) {
                $session->remove($s_name);
                return $this->redirect($this->generateUrl('my_test'));
            }

            $questions = $s_data['questions'];
            $answers   = $s_data['answers'];

            if (!$this->settings['ticket_test_old_style'] && $request->get('question')) {
                $s_data['current'] = array_search($request->get('question'), $questions);
            }

            $num = $s_data['current'];

            $activity_limit = new \DateTime();
            $activity_limit->sub(new \DateInterval('PT60M'));
            if ($s_data['l_activity'] < $activity_limit) {
                $this->testEnd($s_name, $log);
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array('error' => 'longtime'));
                } else {
                    return $this->render('AppBundle:My:test_longtime.html.twig');
                }
            }
            $s_data['l_activity'] = new \DateTime();
            $session->set($s_name, $s_data);

            if ($s_data['end_time'] && $s_data['end_time'] < new \DateTime()) {
                $this->testEnd($s_name, $log);
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array('error' => 'timeout'));
                } else {
                    return $this->render('AppBundle:My:test_timeout.html.twig');
                }
            }

            $max_errors = false;
            $errors = 0;
            $errors_blocks = array();
            foreach ($answers as $answer) {
                if ($answer && !$answer['correct']) {
                    if ($answer['extra']) {
                        $max_errors = true;
                        break;
                    }

                    if (!isset($errors_blocks[$answer['block']])) {
                        $errors_blocks[$answer['block']] = 0;
                    }
                    $errors_blocks[$answer['block']] ++;
                    if ($errors_blocks[$answer['block']] > 1) {
                        $max_errors = true;
                        break;
                    }

                    $errors ++;
                    if ($errors > 2) {
                        $max_errors = true;
                        break;
                    }
                }

                if (isset($answer)) {
                    ++ $amountAnswers;
                }
            }

            if ($max_errors && !isset($s_data['continue'])) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array('error' => 'max_errors'));
                } else {
                    return $this->render('AppBundle:My:test_max_errors.html.twig');
                }
            }

            $isComplete = array_reduce($answers, function ($carry, $item) {
                $carry = $carry && ($item !== null);
                return $carry;
            }, true);

            if (!isset($questions[$num]) || (!$this->settings['ticket_test_old_style'] && $isComplete)) {
                $keys = array_keys($questions);
                if ($num > end($keys) || !$this->settings['ticket_test_old_style']) {
                    if ($request->isXmlHttpRequest()) {
                        $this->testEnd($s_name, $log, !isset($s_data['continue']));
                        $response = [
                            'complete'      => true,
                            'errors'        => $errors,
                            'max_errors'    => $max_errors,
                        ];
                        return new JsonResponse($response);
                    } else {
                        if (isset($s_data['continue'])) {
                            $this->testEnd($s_name, $log);
                            $stat = $this->getUserStat();
                            return $this->render('AppBundle:My:test_complete_errors.html.twig', array(
                                'themes_stat' => $stat['themes'],
                                'all_stat'    => $stat['all'],
                            ));
                        } else {
                            $this->testEnd($s_name, $log, true);
                            $stat = $this->getUserStat();
                            return $this->render('AppBundle:My:test_complete.html.twig', array(
                                'themes_stat' => $stat['themes'],
                                'all_stat'    => $stat['all'],
                            ));
                        }
                    }
                } else {
                    throw $this->createNotFoundException('Question for number "'.$num.'" in this test not found.');
                }
            }

            $question = $this->em->getRepository('AppBundle:Question')->find($questions[$num]);
            if (!$question) {
                throw $this->createNotFoundException('Question for id "'.$questions[$num].'" not found.');
            }

            /** @var $end_time \DateTime */
            $end_time = $s_data['end_time'];

            if ($request->isMethod('post')) {
                $c_answer = $request->get('answer');
                $q_answers = $question->getAnswers();
                $is_correct = false;

                if (isset($q_answers[$c_answer])) {
                    $answers[$num] = $q_answers[$c_answer];
                    $is_correct = $q_answers[$c_answer]['correct'];

                    if (!$is_correct) {
                        $is_extra = in_array($question->getId(), $s_data['extra']);
                        $answers[$num]['block'] = $question->getBlockNum();
                        $answers[$num]['extra'] = $is_extra;

                        if (!$is_extra) {
                            if (!$max_errors) {
                                $version = $this->em->getRepository('AppBundle:TrainingVersion')
                                    ->getVersionByUser($this->user);
                                if (!$version) {
                                    throw $this->createNotFoundException('Training version not found.');
                                }

                                $sql = 'SELECT q.id, SUBSTRING_INDEX(q.num, ".", 1) AS ticket,
                                        CEIL(SUBSTRING_INDEX(q.num, ".", -1) / 5) AS block FROM questions AS q
                                        LEFT JOIN training_versions_questions AS tvq ON q.id = tvq.question_id
                                        WHERE q.is_pdd = 1 AND tvq.version_id = :version AND q.id NOT IN ( :questions )
                                        HAVING block = :block AND ticket <> :ticket ORDER BY RAND() LIMIT 5';

                                $rsm = new Query\ResultSetMapping($this->em);
                                $rsm->addScalarResult('id', 'id', 'integer');
                                $query = $this->em->createNativeQuery($sql, $rsm);
                                $query->setParameters(array(
                                    'block'   => $question->getBlockNum(),
                                    'ticket'  => $question->getTicketNum(),
                                    'version' => $version,
                                    'questions' => $questions,
                                ));
                                $extra = $query->getArrayResult();
                                foreach ($extra as $row) {
                                    $s_data['extra'][] = $row['id'];
                                    if ($amountAnswers > $s_data['amount_of_questions_with_no_extra'] - 1) {
                                        $questions[] = $extra;
                                        $answers[]   = null;
                                    }
                                }

                                if ($end_time) {
                                    $end_time->add(
                                        new \DateInterval('PT' . $this->settings['final_exam_1_extra_time'] . 'M')
                                    );
                                }
                            }
                        }
                    }

                    if ($amountAnswers == $s_data['amount_of_questions_with_no_extra'] - 1) {
                        foreach ($s_data['extra'] as $extra) {
                            $questions[] = $extra;
                            $answers[]   = null;
                        }
                    }
                }

                if ($this->settings['ticket_test_old_style']) {
                    reset($questions);
                    while (key($questions) !== $num) {
                        next($questions);
                    }
                    next($questions);
                    $new_num = key($questions);
                    if (is_null($new_num)) {
                        $new_num = count($answers);
                    }
                    $s_data['current'] = $new_num;
                }

                $log->setAnswers($answers);
                $this->em->persist($log);
                $this->em->flush();

                $s_data['answers'] = $answers;
                $s_data['questions'] = $questions;

                $session->set($s_name, $s_data);
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array(
                        'correct'  => $is_correct,
                        'c_answer' => $c_answer,
                        'comment'  => (!$is_correct && $s_data['comments']) ? $question->getDescription() : '',
                    ));
                } else {
                    $params = array(
                        'num'        => $num,
                        'answers'    => $answers,
                        'question'   => $question,
                        'end_time'   => $end_time,
                        'rem_time'   => $end_time ? $end_time->diff(new \DateTime('now')) : null,
                        'is_comment' => $s_data['comments'],
                    );

                    $params['seconds_left'] = Time::getAllSeconds($params['rem_time']);

                    if ($is_correct) {
                        return $this->render('AppBundle:My:test_success.html.twig', $params);
                    } else {
                        return $this->render('AppBundle:My:test_error.html.twig', $params);
                    }
                }
            }

            $params = [
                'num'        => $num,
                'answers'    => $answers,
                'question'   => $question,
                'end_time'   => $end_time,
                'rem_time'   => $end_time ? $end_time->diff(new \DateTime('now')) : null,
                'is_comment' => $s_data['comments'],
                'errors'     => $errors,
                'max_errors' => $max_errors,
            ];

            $params['seconds_left'] = Time::getAllSeconds($params['rem_time']);

            if ($this->settings['ticket_test_old_style']) {
                if ($request->get('next')) {
                    $content = $this->renderView('AppBundle:My:test_in.html.twig', $params);
                    return new JsonResponse(array('content' => $content));
                } else {
                    return $this->render('AppBundle:My:test.html.twig', $params);
                }
            }

            $questionsEntitiesSource = $this->em->getRepository('AppBundle:Question')->createQueryBuilder('q')
                ->leftJoin('q.image', 'i')
                ->addSelect('i')
                ->andWhere('q.id IN (:questions)')->setParameter('questions', $questions)
                ->getQuery()->getResult();

            $questionsEntities = array_fill(0, count($questions), null);
            foreach ($questionsEntitiesSource as $quest) { /** @var $quest Question */
                $questionsEntities[array_search($quest->getId(), $questions)] = $quest;
            }

            $questAnswers = [];
            for ($i = 0; $i < count($questions); $i++) {
                if (isset($answers[$i]['correct'])) {
                    $questAnswers[$questions[$i]] = $answers[$i]['correct'];
                } else {
                    $questAnswers[$questions[$i]] = null;
                }
            }

            $params['all_questions'] = $questionsEntities;
            $params['quest_answers'] = $questAnswers;

            if ($request->get('question')) {
                $params['question'] = $this->em->getRepository('AppBundle:Question')->find($request->get('question'));
                $content = $this->renderView('AppBundle:My:test_in.html.twig', $params);
                return new JsonResponse(['content' => $content]);
            } elseif ($request->get('next')) {
                $content = $this->renderView('AppBundle:My:test_with_tiles.html.twig', $params);
                return new JsonResponse(['content' => $content]);
            } else {
                return $this->render('AppBundle:My:test.html.twig', $params);
            }
        }
    }

    protected function testEnd($s_name, TestLog $log, $is_passed = false)
    {
        $log->setEndedAt(new \DateTime());
        $log->setPassed($is_passed);
        $this->em->persist($log);
        $this->em->flush();

        $this->getRequest()->getSession()->remove($s_name);
    }

    public function testQuitAction(Request $request)
    {
        $request->getSession()->remove('test');

        return $this->redirect($this->generateUrl('my_test'));
    }

    public function testCommentAction(Request $request)
    {
        $s_name = 'test';
        $session = $request->getSession();
        $s_data = $session->get($s_name);
        $s_data['comments'] = !$s_data['comments'];
        $session->set($s_name, $s_data);

        return $this->redirect($this->generateUrl('my_test'));
    }

    public function testContinueAction(Request $request)
    {
        $s_name = 'test';
        $session = $request->getSession();
        $s_data = $session->get($s_name);
        $s_data['continue'] = true;
        $session->set($s_name, $s_data);

        return $this->redirect($this->generateUrl('my_test'));
    }

    public function testKnowledgeAction(Request $request)
    {
        $session = $request->getSession();
        $s_name = 'test_knowledge';
        $s_data = $session->get($s_name);
        if (!$s_data) {
            $version = $this->em->getRepository('AppBundle:TrainingVersion')->getVersionByUser($this->user);
            if (!$version) {
                throw $this->createNotFoundException('Training version not found.');
            }

            $questions = $this->em->getRepository('AppBundle:Question')->createQueryBuilder('q')
                ->leftJoin('q.theme', 't')->addSelect('t')
                ->andWhere('q.is_pdd = :is_pdd')->setParameter(':is_pdd', true)
                ->leftJoin('q.versions', 'v')
                ->andWhere('v = :version')->setParameter(':version', $version)
                ->addOrderBy('t.subject')
                ->addOrderBy('t.position')
                ->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)->execute();

            $orig_questions = array();
            $names_themes = array();
            foreach ($questions as $question) {
                /** @var $question \My\AppBundle\Entity\Question */

                if (!isset($orig_questions[$question->getTheme()->getId()])) {
                    $orig_questions[$question->getTheme()->getId()] = array();
                    $names_themes[$question->getTheme()->getId()] = $question->getTheme()->getTitle();
                }
                $orig_questions[$question->getTheme()->getId()][] = $question->getId();
            }

            $translator = $this->get('translator');

            $themes_cnt = array();
            foreach ($names_themes as $id => $name) {
                $cnt = count($orig_questions[$id]);
                $names_themes[$id] = $name.' '.$translator->transChoice(
                    'test_knowledge_questions_cnt',
                    $cnt,
                    array('%cnt%' => $cnt)
                );
                $themes_cnt[$id] = $cnt;
            }

            $form_factory = $this->get('form.factory');
            $form = $form_factory->createNamedBuilder('test_knowledge')
                ->add('themes', 'choice', array(
                    'expanded'    => true,
                    'multiple'    => true,
                    'required'    => true,
                    'choices'     => $names_themes,
                    'constraints' => array(new Assert\NotBlank()),
                ))
                ->add('comments', 'checkbox', array('required' => false))
                ->add('time', 'checkbox', array('required' => false))
                ->getForm();
            if ($request->isMethod('post')) {
                $form->handleRequest($request);
                $data = $form->getData();

                $questions_limit = 20;

                if (isset($data['themes']) && is_array($data['themes']) && count($data['themes']) > 0) {
                    $sum = 0;
                    foreach ($data['themes'] as $id) {
                        $sum += count($orig_questions[$id]);
                    }
                    if ($sum < $questions_limit) {
                        $error = new FormError($translator->trans('test_knowledge_not_enough_questions'));
                        $form->get('themes')->addError($error);
                    }
                }

                if ($form->isValid()) {
                    $questions = array();

                    $new_orig_questions = array();
                    foreach ($data['themes'] as $id) {
                        $new_orig_questions[$id] = $orig_questions[$id];
                    }
                    $orig_questions = $new_orig_questions;

                    $orig_questions_count = count($orig_questions, COUNT_RECURSIVE) - count($orig_questions);
                    if ($orig_questions_count > 0) {
                        foreach ($orig_questions as $j => $q) {
                            $get_questions = floor(count($q) * $questions_limit / $orig_questions_count);
                            if ($get_questions > 0) {
                                if ($get_questions > count($q)) {
                                    $get_questions = count($q);
                                }
                                $keys = (array)array_rand($q, $get_questions);
                                $questions = array_merge(
                                    $questions,
                                    array_intersect_key($q, array_fill_keys($keys, null))
                                );
                                foreach ($keys as $k) {
                                    unset($orig_questions[$j][$k]);
                                }
                            }
                        }

                        $add_questions = $questions_limit - count($questions);
                        if ($add_questions > 0) {
                            $orig_questions_united = array();
                            foreach ($orig_questions as $j => $q) {
                                foreach ($q as $k => $v) {
                                    $orig_questions_united[$j.'_'.$k] = $v;
                                }
                            }
                            if ($add_questions > count($orig_questions_united)) {
                                $add_questions = count($orig_questions_united);
                            }
                            if ($add_questions > 0) {
                                $keys = (array)array_rand($orig_questions_united, $add_questions);
                                $questions = array_merge(
                                    $questions,
                                    array_intersect_key($orig_questions_united, array_fill_keys($keys, null))
                                );
                                foreach ($keys as $key) {
                                    list($j, $k) = explode('_', $key);
                                    unset($orig_questions[$j][$k]);
                                }
                            }
                        }

                        shuffle($questions);
                    }

                    $answers = array_fill(0, count($questions), null);

                    $time =  new \DateTime();
                    $end_time = null;
                    if ($data['time']) {
                        $end_time = clone $time;
                        $end_time->add(new \DateInterval('PT10M'));
                    }

                    $log = new TestKnowledgeLog();
                    $log->setStartedAt($time);
                    $log->setQuestions($questions);
                    $log->setAnswers($answers);
                    $log->setUser($this->user);
                    $this->em->persist($log);
                    $this->em->flush();

                    $s_data= array(
                        'questions'  => $questions,
                        'answers'    => $answers,
                        'log_id'     => $log->getId(),
                        'current'    => 0,
                        'comments'   => $data['comments'],
                        'end_time'   => $end_time,
                        'l_activity' => new \DateTime(),
                    );
                    $session = $request->getSession();
                    $session->set($s_name, $s_data);

                    return $this->redirect($this->generateUrl('my_test_knowledge'));
                }
            }

            return $this->render('AppBundle:My:test_knowledge_entrance.html.twig', array(
                'form'       => $form->createView(),
                'themes_cnt' => $themes_cnt,
            ));
        } else {
            $log = $this->em->getRepository('AppBundle:TestKnowledgeLog')->find($s_data['log_id']);
            if (!$log) {
                $session->remove($s_name);
                return $this->redirect($this->generateUrl('my_test_knowledge'));
            }

            $questions = $s_data['questions'];
            $answers = $s_data['answers'];
            $num = $s_data['current'];

            $activity_limit = new \DateTime();
            $activity_limit->sub(new \DateInterval('PT60M'));
            if ($s_data['l_activity'] < $activity_limit) {
                $this->testKnowledgeEnd($s_name, $log);
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array('error' => 'longtime'));
                } else {
                    return $this->render('AppBundle:My:test_knowledge_longtime.html.twig');
                }
            }
            $s_data['l_activity'] = new \DateTime();
            $session->set($s_name, $s_data);

            if ($s_data['end_time'] && $s_data['end_time'] < new \DateTime()) {
                $this->testKnowledgeEnd($s_name, $log);
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array('error' => 'timeout'));
                } else {
                    return $this->render('AppBundle:My:test_knowledge_timeout.html.twig');
                }
            }

            $max_errors = 2;
            $errors = 0;
            foreach ($answers as $answer) {
                if ($answer && !$answer['correct']) {
                    $errors++;
                }
            }
            if ($errors >= $max_errors && !isset($s_data['continue'])) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array('error' => 'max_errors'));
                } else {
                    return $this->render('AppBundle:My:test_knowledge_max_errors.html.twig');
                }
            }

            if (!isset($questions[$num])) {
                $keys = array_keys($questions);
                if ($num > end($keys)) {
                    if ($request->isXmlHttpRequest()) {
                        $this->testKnowledgeEnd($s_name, $log, !isset($s_data['continue']));
                        $response = [
                            'complete'      => true,
                            'errors'        => $errors,
                            'max_errors'    => $max_errors,
                        ];
                        return new JsonResponse($response);
                    } else {
                        if (isset($s_data['continue'])) {
                            $this->testKnowledgeEnd($s_name, $log);
                            $stat = $this->getUserStat();
                            return $this->render('AppBundle:My:test_knowledge_complete_errors.html.twig', array(
                                'themes_stat' => $stat['themes'],
                                'all_stat'    => $stat['all'],
                            ));
                        } else {
                            $this->testKnowledgeEnd($s_name, $log, true);
                            $stat = $this->getUserStat();
                            return $this->render('AppBundle:My:test_knowledge_complete.html.twig', array(
                                'themes_stat' => $stat['themes'],
                                'all_stat'    => $stat['all'],
                            ));
                        }
                    }
                } else {
                    throw $this->createNotFoundException('Question for number "'.$num.'" in this test not found.');
                }
            }

            $question = $this->em->getRepository('AppBundle:Question')->find($questions[$num]);
            if (!$question) {
                throw $this->createNotFoundException('Question for id "'.$questions[$num].'" not found.');
            }

            /** @var $end_time \DateTime */
            $end_time = $s_data['end_time'];

            if ($request->isMethod('post')) {
                $c_answer = $request->get('answer');
                $q_answers = $question->getAnswers();

                if (isset($q_answers[$c_answer])) {
                    $s_data['answers'][$num] = $q_answers[$c_answer];
                }

                reset($questions);
                while (key($questions) !== $num) {
                    next($questions);
                }
                next($questions);
                $new_num = key($questions);
                if (is_null($new_num)) {
                    $new_num = count($answers);
                }
                $s_data['current'] = $new_num;
                $session->set($s_name, $s_data);

                $log->setAnswers($s_data['answers']);
                $this->em->persist($log);
                $this->em->flush();

                $is_correct = isset($q_answers[$c_answer]) && $q_answers[$c_answer]['correct'];
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array(
                        'correct'  => $is_correct,
                        'c_answer' => $c_answer,
                        'comment'  => (!$is_correct && $s_data['comments']) ? $question->getDescription() : '',
                        'errors'   => $is_correct ? $errors : ($errors + 1),
                    ));
                } else {
                    $params = array(
                        'num'        => $num,
                        'answers'    => $answers,
                        'question'   => $question,
                        'end_time'   => $end_time,
                        'rem_time'   => $end_time ? $end_time->diff(new \DateTime('now')) : null,
                        'is_comment' => $s_data['comments'],
                    );

                    $params['seconds_left'] = Time::getAllSeconds($params['rem_time']);

                    if ($is_correct) {
                        return $this->render('AppBundle:My:test_knowledge_success.html.twig', $params);
                    } else {
                        return $this->render('AppBundle:My:test_knowledge_error.html.twig', $params);
                    }
                }
            }

            $params = array(
                'num'        => $num,
                'answers'    => $answers,
                'question'   => $question,
                'end_time'   => $end_time,
                'rem_time'   => $end_time ? $end_time->diff(new \DateTime('now')) : null,
                'is_comment' => $s_data['comments'],
                'errors'     => $errors,
                'max_errors' => $max_errors,
            );

            $params['seconds_left'] = Time::getAllSeconds($params['rem_time']);

            if ($request->get('next')) {
                $content = $this->renderView('AppBundle:My:test_knowledge_in.html.twig', $params);
                return new JsonResponse(array('content' => $content));
            } else {
                return $this->render('AppBundle:My:test_knowledge.html.twig', $params);
            }
        }
    }

    protected function testKnowledgeEnd($s_name, TestKnowledgeLog $log, $is_passed = false)
    {
        $log->setEndedAt(new \DateTime());
        $log->setPassed($is_passed);
        $this->em->persist($log);
        $this->em->flush();

        $this->getRequest()->getSession()->remove($s_name);
    }

    public function testKnowledgeQuitAction(Request $request)
    {
        $request->getSession()->remove('test_knowledge');

        return $this->redirect($this->generateUrl('my_test_knowledge'));
    }

    public function testKnowledgeCommentAction(Request $request)
    {
        $s_name = 'test_knowledge';
        $session = $request->getSession();
        $s_data = $session->get($s_name);
        $s_data['comments'] = !$s_data['comments'];
        $session->set($s_name, $s_data);

        return $this->redirect($this->generateUrl('my_test_knowledge'));
    }

    public function testKnowledgeContinueAction(Request $request)
    {
        $s_name = 'test_knowledge';
        $session = $request->getSession();
        $s_data = $session->get($s_name);
        $s_data['continue'] = true;
        $session->set($s_name, $s_data);

        return $this->redirect($this->generateUrl('my_test_knowledge'));
    }

    public function statAction()
    {
        $stat = $this->getUserStat();

        return $this->render('AppBundle:My:stat.html.twig', array(
            'themes_stat' => $stat['themes'],
            'all_stat'    => $stat['all'],
        ));
    }

    public function readDiscount2FirstAction()
    {
        $this->user->setDiscount2NotifyFirst(true);
        $this->em->persist($this->user);
        $this->em->flush();
        return new JsonResponse();
    }

    public function readDiscount2SecondAction()
    {
        $this->user->setDiscount2NotifySecond(true);
        $this->em->persist($this->user);
        $this->em->flush();
        return new JsonResponse();
    }

    public function drivingAction($number = null)
    {
        $notFilial = false;
        $region = $this->user->getRegion();
        if ($region) {
            $notFilial = $region->getFilialNotExisting();
        }

        if (!$notFilial) {
            throw $this->createNotFoundException();
        }

        if ($number !== null) {
            /** @var  $package \My\AppBundle\Entity\DrivingPackage */
            $package = $this->em->getRepository('AppBundle:DrivingPackage')->createQueryBuilder('p')
                ->andWhere('p.user = :user')->setParameter('user', $this->user)
                ->andWhere('p.sale_at IS NOT NULL')
                ->andWhere('p.number = :number')->setParameter('number', $number)
                ->getQuery()->getOneOrNullResult();
            if ($package) {
                $package->setStatus('received');
                $package->setReceivedAt(new \DateTime());

                $this->em->persist($package);
                $this->em->flush();
            } else {
                throw $this->createNotFoundException('Not found driving tickets.');
            }
        }
        $packages = $this->em->getRepository('AppBundle:DrivingPackage')->createQueryBuilder('p')
            ->andWhere('p.user = :user')->setParameter('user', $this->user)
            ->andWhere('p.sale_at IS NOT NULL')
            ->leftJoin('p.tickets', 't')->addSelect('t')
            ->addOrderBy('p.sale_at', 'ASC')
            ->getQuery()->execute();

        $conditions = array();
        foreach ($packages as $package) {
            /** @var $package \My\AppBundle\Entity\DrivingPackage */
            $condition = $package->getCondition();
            $packageTickets = $package->getTickets();

            $conditions[$condition->getId()]['name'] = $condition->getName();
            $conditions[$condition->getId()]['packages'][$package->getNumber()]['package_status']
                = $package->getStatus();

            if ($package->getStatus() == 'received' || $package->getStatus() == 'given_into_hands') {
                if (!isset($conditions[$condition->getId()]['packages'][$package->getNumber()]['tickets'])) {
                    $conditions[$condition->getId()]['packages'][$package->getNumber()]['tickets'] = array();
                }
                foreach ($packageTickets as $ticket) {
                    $conditions[$condition->getId()]['packages'][$package->getNumber()]['tickets'][] = $ticket;
                }
            }
        }

        return $this->render('@App/My/drivings.html.twig', array(
            'conditions'     => $conditions,
            'confirmed_docs' => $this->user->getConfirmDocsIsSend(),
        ));
    }

    public function saveTicketAjaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $notFilial = false;
        $region = $this->user->getRegion();
        if ($region) {
            $notFilial = $region->getFilialNotExisting();
        }

        if (!$notFilial) {
            throw $this->createNotFoundException();
        }

        $date = trim($request->get('date'));
        $name = trim($request->get('name'));
        $comment = trim($request->get('comment'));
        $rating = intval($request->get('rating'));
        $id = $request->get('id');

        $userDocs = $this->em->getRepository('AppBundle:Document')->findBy(array('user' => $this->user));
        $confirmedDocs = true;
        foreach ($userDocs as $userDoc) {
            /** @var  $userDoc \My\AppBundle\Entity\Document */
            if ($userDoc->getStatus() != 'confirm') {
                $confirmedDocs = false;
                break;
            }
        }

        if (!$confirmedDocs) {
            return new JsonResponse(array(
                'success' => false,
                'message' => 'Дождитесь подтверждения всех документов',
                'id'      => $id,
            ));
        }

        if (!preg_match('/\d{1,2}.\d{1,2}.(19|20)\d{2}/', $request->get('date'), $matches)) {
            return new JsonResponse(array(
                'success' => false,
                'message' => 'Неверный формат даты',
                'id'      => $id,
            ));
        }

        try {
            $sendedDate = new \DateTime($date);
        } catch (\Exception $e) {
            return new JsonResponse(array(
                'success' => false,
                'message' => 'Неверный формат даты',
                'id'      => $id,
            ));
        }
        if ($sendedDate > new \DateTime()) {
            return new JsonResponse(array(
                'success' => false,
                'message' => 'Указанная дата еще не наступила',
                'id'      => $id,
            ));
        }

        if (!preg_match('/(\D+\s)(\D+\s)(\D+)/', $name, $matches)) {
            return new JsonResponse(array(
                'success' => false,
                'message' => 'Неверный формат имени, должно быть 3 слова, разделенных пробелом',
                'id'      => $id,
            ));
        }

        if (!$comment || strlen($comment) < 6) {
            return new JsonResponse(array(
                'success' => false,
                'message' => 'Коментарий должен быть не пустым и быть длиннее шести символов',
                'id'      => $id,
            ));
        }

        if ($rating <= 0 || $rating > 5) {
            return new JsonResponse(array(
                'success' => false,
                'message' => 'Оценка должна быть в диапозоне от 1 до 5',
                'id'      => $id,
            ));
        }

        /** @var  $ticket \My\AppBundle\Entity\DrivingTicket */
        $ticket = $this->em->getRepository('AppBundle:DrivingTicket')->createQueryBuilder('t')
            ->andWhere('t.drive_date IS NULL')
            ->andWhere('t.id = :id')->setParameter('id', $id)
            ->leftJoin('t.package', 'p')
            ->andWhere('p.user = :user')->setParameter('user', $this->user)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!$ticket) {
            return new JsonResponse(array(
                'success' => false,
                'message' => 'Не удалось найти билет вождения',
                'id'      => $id,
            ));
        }

        $ticket->setDriveDate($sendedDate);
        $ticket->setName($name);
        $ticket->setComment($comment);
        $ticket->setRating($rating);

        $this->em->persist($ticket);
        $this->em->flush();

        return new JsonResponse(array(
            'success' => true,
            'message' => 'Данные успешно сохранены',
            'id'      => $id,
        ));
    }

    public function feedbackAction(Request $request, EngineInterface $templating)
    {
        $feedbackEmailsRepo = $this->em->getRepository('AppBundle:FeedbackEmail');
        $feedbackEmailsSource = $feedbackEmailsRepo->findAll();
        $feedbackEmails = array();
        $feedbackEmailsPlain = array();
        foreach ($feedbackEmailsSource as $email) {
            /** @var $email \My\AppBundle\Entity\FeedbackEmail */

            $sbjs = $email->getSubjects();
            if ($sbjs) {
                $sbjs = explode(PHP_EOL, $sbjs);
                $sbjs_cnt = count($sbjs);
                if ($sbjs_cnt > 0) {
                    $feedbackEmails[$email->getName()] = array();
                    $id = $email->getId();
                    for ($i = 0; $i < $sbjs_cnt; $i ++) {
                        $feedbackEmails[$email->getName()][$id.'_'.$i] = $sbjs[$i];
                        $feedbackEmailsPlain[$id.'_'.$i] = $sbjs[$i];
                    }
                }
            }
        }

        $form_factory = $this->get('form.factory');
        $form = $form_factory->createNamedBuilder('feedback')
            ->add('destination', 'choice', array(
                'choices'     => $feedbackEmails,
                'empty_value' => '',
                'constraints' => array(new Assert\NotBlank()),
            ))
            ->add('text', 'textarea', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('max' => 2000)),
                ),
            ))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $dest_value = $form->get('destination')->getData();
            $e_id = explode('_', $dest_value);
            $e_id = $e_id[0];

            $destination = $feedbackEmailsRepo->find($e_id);
            if ($destination) {
                $subject = isset($feedbackEmailsPlain[$dest_value]) ? $feedbackEmailsPlain[$dest_value] : '';

                $message = $form->get('text')->getData();
                $message = $templating->render('AppBundle::_email.html.twig', array(
                    'message' => $message,
                    'title' => 'ДО - '.$destination->getName().' - '.$subject,
                ));

                /** @var $email \Swift_Mime_Message */
                $email = \Swift_Message::newInstance()
                    ->setFrom(array($this->user->getEmail() => $this->user->getFullName()))
                    ->setTo($destination->getEmail())
                    ->setSubject('ДО - '.$destination->getName().' - '.$subject)
                    ->setBody($message, 'text/html')
                ;
                $this->get('swiftmailer.mailer.directly_mailer')->send($email);

                return $this->redirect($this->generateUrl('my_feedback_success'));
            } else {
                $form->addError(new FormError('Что-то пошло не так!'));
            }
        }

        return $this->render('AppBundle:My:feedback.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function feedbackSuccessAction()
    {
        return $this->render('AppBundle:My:feedback_success.html.twig');
    }

    public function feedbackTeacherAction(Request $request)
    {
        $cntxt = $this->get('security.context');
        if (false === $cntxt->isGranted('ROLE_USER_PAID2')) {
            throw $this->createNotFoundException();
        }

        $subjectsRepo = $this->em->getRepository('AppBundle:Subject');
        $subjects = $subjectsRepo->findAll();
        $subjectsThemes = array();
        $subjectsThemesPlain = array();
        foreach ($subjects as $subject) {
            /** @var $subject \My\AppBundle\Entity\Subject */

            $themes = $subject->getThemes();
            $subjectsThemes[$subject->getTitle()] = array();
            $id = $subject->getId();
            foreach ($themes as $theme) {
                /** @var $theme \My\AppBundle\Entity\Theme */

                $i = $theme->getId();
                $subjectsThemes[$subject->getTitle()][$id.'_'.$i] = $theme->getTitle();
                $subjectsThemesPlain[$id.'_'.$i] = $theme->getTitle();
            }
        }

        $form_factory = $this->get('form.factory');
        $form = $form_factory->createNamedBuilder('feedback_teacher')
            ->add('destination', 'entity', array(
                'class'       => 'AppBundle:FeedbackTeacherEmail',
                'property'    => 'name',
                'empty_value' => '',
                'constraints' => array(new Assert\NotBlank()),
            ))
            ->add('theme', 'choice', array(
                'choices' => $subjectsThemes,
                'empty_value' => '',
                'constraints' => array(new Assert\NotBlank()),
            ))
            ->add('text', 'textarea', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('max' => 2000)),
                ),
            ))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $destination = $form->get('destination')->getData();

            $theme_value = $form->get('theme')->getData();
            $s_id = explode('_', $theme_value);
            $s_id = $s_id[0];

            $subject = $subjectsRepo->find($s_id);
            if ($subject) {
                $subj = isset($subjectsThemesPlain[$theme_value]) ? $subjectsThemesPlain[$theme_value] : '';

                $message = $form->get('text')->getData();
                $message = $this->get('templating')->render('AppBundle::_email.html.twig', array(
                    'message' => $message,
                    'title' => 'ДО - '.$destination->getName().' - '.$subject,
                ));


                /** @var $email \Swift_Mime_Message */
                $email = \Swift_Message::newInstance()
                    ->setFrom(array($this->user->getEmail() => $this->user->getFullName()))
                    ->setTo($destination->getEmail())
                    ->setSubject('ДО - '.$subject->getTitle().' - '.$subj)
                    ->setBody($message, 'text/html')
                ;
                $this->get('swiftmailer.mailer.directly_mailer')->send($email);

                return $this->redirect($this->generateUrl('my_feedback_teacher_success'));
            } else {
                $form->addError(new FormError('Что-то пошло не так!'));
            }
        }

        return $this->render('AppBundle:My:feedback_teacher.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function feedbackTeacherSuccessAction()
    {
        return $this->render('AppBundle:My:feedback_teacher_success.html.twig');
    }

    public function changePasswordAction(Request $request)
    {
        $form = $this->createForm('change_password');
        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            //check old password
            if ($form->isValid()) {
                //set new passwords and flush (second param)
                $this->user->setPlainPassword(trim($form->get('new_password')->getData()));
                $this->container->get('fos_user.user_manager')->updateUser($this->user, true);

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array('success' => true));
                } else {
                    return $this->redirect($this->generateUrl('my_profile'));
                }
            } elseif ($request->isXmlHttpRequest()) {
                return new JsonResponse(array('errors' => $this->getErrorMessages($form)));
            }
        }

        return $this->render('AppBundle:My:change_password.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function supportDialogsAction()
    {
        $dialogs = $this->em->getRepository('AppBundle:SupportDialog')->getUserDialogs($this->getUser());

        return $this->render('AppBundle:My:support_dialogs.html.twig', array(
            'dialogs' => $dialogs,
        ));
    }

    public function supportNewAction(Request $request)
    {
        $categoriesTree = array();
        $categories = $this->em->getRepository('AppBundle:SupportCategory')->createQueryBuilder('sc')
            ->orderBy('sc.createdAt')
            ->orderBy('sc.parent')
            ->getQuery()->getResult();
        foreach ($categories as $category) { /** @var $category \My\AppBundle\Entity\SupportCategory */
            if ($category->getParent()) {
                //for optgroup
                if (!isset($categoriesTree[$category->getParent()->getName()])) {
                    $categoriesTree[$category->getParent()->getName()] = array();
                }
                $categoriesTree[$category->getParent()->getName()][$category->getId()] = $category->getName();
            }
        }

        $message = new SupportMessage();
        $form = $this->createForm(new SupportMessageFormType(), $message);
        $form->add('category', 'choice', array(
            'empty_value' => 'Выберите тему обращения',
            'choices'     => $categoriesTree,
            'constraints' => array(new Assert\NotBlank()),
            'mapped'      => false,
        ));

        if ($request->isMethod('post')) {
            $form->submit($request);
            if ($form->isValid()) {
                $category_id = $form->get('category')->getData();
                $category = $this->em->getRepository('AppBundle:SupportCategory')->find($category_id);
                if ($category) {
                    //count limit date to answer
                    $daysToAnswer = $this->settings['support_days_to_answer'];
                    //one day before for short loop body
                    if (date('H') <= 12) {
                        $limitDate = new \DateTime('yesterday midnight');
                    } else {
                        $limitDate = new \DateTime('today midnight');
                    }
                    //get lists of holidays and exceptions (for weekends)
                    $holidaysRaw = $this->em->getRepository('AppBundle:Holiday')->createQueryBuilder('h')
                        ->orderBy('h.entry_value')
                        ->andWhere('h.entry_value >= :prevYear')->setParameter('prevYear', $limitDate)
                        ->getQuery()->getResult();
                    $holidays = array();
                    $exceptions = array();
                    foreach ($holidaysRaw as $holiday) {
                        /** @var $holiday \My\AppBundle\Entity\Holiday */

                        if ($holiday->getException()) {
                            $exceptions[] = $holiday->getEntryValue();
                        } else {
                            $holidays[] = $holiday->getEntryValue();
                        }
                    }
                    $oneDay = new \DateInterval('P1D');
                    while ($daysToAnswer) {
                        $limitDate->add($oneDay);
                        //saturday or sunday?
                        if (($limitDate->format('N') > 5 && !in_array($limitDate, $exceptions))
                            || in_array($limitDate, $holidays)
                        ) {
                            continue;
                        }
                        $daysToAnswer --;
                    }

                    $dialog = new SupportDialog;
                    $dialog->setCategory($category);
                    $dialog->setUser($this->getUser());
                    $dialog->setLastMessageText($message->getText());
                    $dialog->setLastMessageTime(new \DateTime());
                    $dialog->setUserRead(true);
                    $dialog->setAnswered(false);
                    $dialog->setLimitAnswerDate($limitDate);

                    $message->setDialog($dialog);
                    $message->setUser($this->getUser());

                    $this->em->persist($dialog);
                    $this->em->persist($message);
                    $this->em->flush();

                    $url = $this->generateUrl('my_support_dialog_show', array('id' => $dialog->getId()));
                    return $this->redirect($url);
                }
            }
        }

        return $this->render('AppBundle:My:support_new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function supportDialogShowAction(Request $request, $id)
    {
        $dialog = $this->em->getRepository('AppBundle:SupportDialog')->find($id);
        if ($dialog) {
            //mark dialog read
            $dialog->setUserRead(true);
            $message = new SupportMessage();
            $form = $this->createForm(new SupportMessageFormType(), $message);

            if ($request->isMethod('post')) {
                $form->submit($request);
                if ($form->isValid()) {
                    //count limit date to answer
                    $daysToAnswer = $this->settings['support_days_to_answer'];
                    //one day before for short loop body
                    if (date('H') <= 12) {
                        $limitDate = new \DateTime('yesterday midnight');
                    } else {
                        $limitDate = new \DateTime('today midnight');
                    }
                    //get lists of holidays and exceptions (for weekends)
                    $holidaysRaw = $this->em->getRepository('AppBundle:Holiday')->createQueryBuilder('h')
                        ->orderBy('h.entry_value')
                        ->andWhere('h.entry_value >= :prevYear')
                        ->setParameter('prevYear', new \DateTime('yesterday midnight'))
                        ->getQuery()->getResult();
                    $holidays = array();
                    $exceptions = array();
                    foreach ($holidaysRaw as $holiday) {
                        /** @var $holiday \My\AppBundle\Entity\Holiday */

                        if ($holiday->getException()) {
                            $exceptions[] = $holiday->getEntryValue();
                        } else {
                            $holidays[] = $holiday->getEntryValue();
                        }
                    }
                    $oneDay = new \DateInterval('P1D');
                    while ($daysToAnswer) {
                        $limitDate->add($oneDay);
                        //saturday or sunday?
                        if (($limitDate->format('N') < 6 || in_array($limitDate, $exceptions))
                            && !in_array($limitDate, $holidays)
                        ) {
                            $daysToAnswer--;
                        }
                    }

                    //mark dialog as unanswered, because we have new message from user
                    $dialog->setAnswered(false);
                    $dialog->setLastMessageText($message->getText());
                    $dialog->setLastMessageTime(new \DateTime());
                    $dialog->setLimitAnswerDate($limitDate);

                    $message->setDialog($this->em->getReference('AppBundle:SupportDialog', $id));
                    $message->setUser($this->getUser());

                    $this->em->persist($message);
                    $this->em->flush();

                    return $this->redirect($this->generateUrl('my_support_dialog_show', array('id' => $id)));
                }
            }

            //save dialog (there can be no new messages so flush above won't work)
            $this->em->flush();
            return $this->render('AppBundle:My:support_dialog_show.html.twig', array(
                'dialog' => $dialog,
                'form'   => $form->createView(),
            ));
        } else {
            throw $this->createNotFoundException('Dialog with id '.$id.' wasn\'t found.');
        }
    }

    public function passAction()
    {
        return $this->render('AppBundle:My:pass.html.twig');
    }

    public function medAction()
    {
        $article_med = $this->em->getRepository('AppBundle:Article')->createQueryBuilder('a')
            ->andWhere('a.url = :url')->setParameter('url', 'med')
            ->getQuery()->getOneOrNullResult();
        ;

        return $this->render('AppBundle:My:med.html.twig', [
            'article_med' => $article_med,
        ]);
    }

    public function pdfAction()
    {
        $user = $this->getUser();

        $final_exams_logs_repository = $this->em->getRepository('AppBundle:FinalExamLog');
        if (!$final_exams_logs_repository->isPassed($this->user)) {
            return $this->forward('AppBundle:My:profile');
        }

        $file = $this->get('kernel')->getRootDir().'/../src/My/AppBundle/Resources/pdf/certificate.pdf';
        $pff = $this->get('pdf_form_filler');

        $date = $final_exams_logs_repository->getPassedDate($user);
        if (!$date) {
            $date = new \DateTime();
        }
        $months = array(
            1 => 'января',
            'февраля',
            'марта',
            'апреля',
            'мая',
            'июня',
            'июля',
            'августа',
            'сентября',
            'октября',
            'ноября',
            'декабря'
        );
        $pdf = $pff->fill($file, [
            'number'                 => '№ '.$user->getId(),
            'Surname'                => $user->getLastName(),
            'First_name_Second_name' => $user->getFirstName().' '.$user->getPatronymic(),
            'City'                   => $user->getRegion()->getName(),
            'Date'                   => $date->format('d').' '.$months[$date->format('n')].' '.$date->format('Y').' г.'
        ]);
        $response = new BinaryFileResponse($pdf);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="certificate.pdf"');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }

    public function apiQuestionsAction(Request $request)
    {
        $radios = array('want_pay', 'training_after', 'not_going');

        if ($request->isMethod('post')) {
            $radio = $request->get('radio');
            $months = intval($request->get('training_after_months'));

            if (in_array($radio, $radios) && ($radio != 'training_after' || ($months > 0 && $months < 100))) {
                $log = new ApiQuestionLog();
                $log->setUser($this->user);
                $log->setRadio($radio);
                if ($radio == 'training_after') {
                    $log->setMonths($months);
                }
                $this->em->persist($log);
                $this->em->flush();
            }

            return $this->redirect($this->generateUrl('homepage'));
        }

        return $this->render('AppBundle:My:api_questions.html.twig');
    }

    public function buyAttemptsAction(Request $request)
    {
        $session = $this->get('session');
        $session->remove('payment');

        if ($request->isMethod('post')) {
            $id = $request->get('ids');
            $attempt = $this->em->getRepository('AppBundle:AttemptsPackage')->find($id);
            if ($attempt) {
                $payment['sum'] = $attempt->getCost();
                $payment['comment']['attemptsPackage'] = $attempt->getId();

                if (!empty($this->get('request')->get('final_exam'))) {
                    $payment['comment']['final_exam'] = true;
                } elseif (!empty($this->get('request')->get('subject_id'))) {
                    $payment['comment']['subject_id'] = $this->get('request')->get('subject_id');
                }

                $session->set('payment', $payment);
                $session->save();
                return $this->redirect($this->generateUrl('my_payments_pay'));
            }
        }

        /** @var $attemptPackages \My\AppBundle\Entity\AttemptsPackage */
        $attemptPackages = $this->em->getRepository('AppBundle:AttemptsPackage')->createQueryBuilder('ap')
            ->orderBy('ap.number_of_attempts')
            ->getQuery()->getResult();
        return $this->render('@App/My/my_buy_attempts.html.twig', array(
            'attemptPackages'   => $attemptPackages,
        ));
    }

    public function printAction()
    {
        $region = $this->user->getRegion();
        if ($region && !$region->getFilialNotExisting()) {
            throw $this->createNotFoundException('Not found page');
        }

        return $this->render('@App/My/agreement.html.twig', array());
    }

    public function termsAndConditionsAction()
    {
        return $this->render('@App/My/terms_and_conditions.html.twig', array());
    }

    public function printTermsAndConditionsEducationServiceAction()
    {
        $user         = $this->getUser();
        $userCategory = strtolower($user->getCategory()->getName());
        $view         = null;

        if ($user->getRegion()->getFilialNotExisting()) {
            if ($userCategory == 'b') {
                $view = '@App/My/terms_and_conditions_education_service_vir_sc_cat_' . $userCategory . '.html.twig';
            } else {
                $view = '@App/My/terms_and_conditions.html.twig';
            }
        } elseif (in_array($userCategory, ['a', 'b'])) {
            $view = '@App/My/terms_and_conditions_education_service_cat_'.$userCategory.'.html.twig';
        }

        return $this->render($view, [
            'user' => $user,
        ]);
    }

    public function printTermsAndConditionsEducationServiceBlankAction()
    {
        $user         = $this->getUser();
        $userCategory = strtolower($user->getCategory()->getName());
        $view         = null;

        if ($user->getRegion()->getFilialNotExisting()) {
            if (in_array($userCategory, ['a', 'b'])) {
                $view = '@App/My/terms_and_conditions_education_service_blank_vir_sc_cat_'.$userCategory.'.html.twig';
            }
        } elseif (in_array($userCategory, ['a', 'b'])) {
            $view = '@App/My/terms_and_conditions_education_service_blank_cat_'.$userCategory.'.html.twig';
        }

        return $this->render($view, [
            'user' => $user,
        ]);
    }

//    public function printTermsAndConditionsAgreementAndOfferAction()
//    {
//        /** @var $user \My\AppBundle\Entity\User */
//        $user         = $this->getUser();
//        $userCategory = strtolower($user->getCategory()->getName());
//
//        $view = null;
//        if (in_array($userCategory, ['a', 'b'])) {
//            $view = '@App/My/terms_and_conditions_agreement_and_offer_cat_'.$userCategory.'.html.twig';
//        }
//
//        return $this->render($view, [
//            'user' => $user,
//        ]);
//    }

    public function printTermsAndConditionsAgreementAction()
    {
        return $this->render('@App/My/terms_and_conditions_agreement.html.twig');
    }

    public function printTermsAndConditionsAgreementPersDataAction()
    {
        $user         = $this->getUser();
        $userCategory = strtolower($user->getCategory()->getName());
        $view         = null;

        if (in_array($userCategory, ['a', 'b'])) {
            $view = '@App/My/terms_and_conditions_agreement_pers_data_cat_'.$userCategory.'.html.twig';
        }

        return $this->render($view, [
            'user' => $user,
        ]);
    }

    public function printTermsAndConditionsOfferAction()
    {
        $user         = $this->getUser();
        $userCategory = strtolower($user->getCategory()->getName());
        $view         = null;
        $userRegion   = $user->getRegion()->getFilialNotExisting();

        if ($userRegion) {
            if (in_array($userCategory, ['a', 'b'])) {
                $view = '@App/My/terms_and_conditions_offer_vir_sc_cat_'.$userCategory.'.html.twig';
            }
        } elseif (in_array($userCategory, ['a', 'b'])) {
            $view = '@App/My/terms_and_conditions_offer_cat_'.$userCategory.'.html.twig';
        }

        return $this->render($view, [
            'user' => $user,
        ]);
    }

    public function treatyOnNonDisclosureAction()
    {
        $notFilial = false;
        $region = $this->user->getRegion();
        if ($region) {
            $notFilial = $region->getFilialNotExisting();
        }

        if (!$notFilial) {
            throw $this->createNotFoundException();
        }

        return $this->render('@App/My/treaty_on_non_disclosure.html.twig', array());
    }

    public function attemptsPackageStatAction()
    {
        $boughtPackages = $this->em->getRepository('AppBundle:ExamAttemptLog')->createQueryBuilder('eal')
            ->leftJoin('eal.attempts_package', 'ap')
            ->select('eal.created_at, eal.amount, ap.name')
            ->andWhere('eal.user = :user')->setParameter('user', $this->user)
            ->andWhere('eal.attempts_package IS NOT NULL')
            ->andWhere('eal.amount > 0')
            ->orderBy('eal.created_at', 'DESC')
            ->getQuery()->getArrayResult();

        $sumPackages = 0;
        foreach ($boughtPackages as $pack) {
            $sumPackages += $pack['amount'];
        }

        $usedAttempts = $this->em->getRepository('AppBundle:ExamAttemptLog')->createQueryBuilder('eal')
            ->leftJoin('eal.subject', 's')
            ->select('eal.created_at, s.title')
            ->andWhere('eal.user = :user')->setParameter('user', $this->user)
            ->andWhere('eal.attempts_package IS NULL')
            ->andWhere('eal.amount < 0')
            ->orderBy('eal.created_at', 'DESC')
            ->getQuery()->getArrayResult();

        $sumAttempts = count($usedAttempts);

        $examErrorsCount       = $this->get('app.exam_attempts')->getErrorsCountByUser($this->user);
        $attemptsToResetRemain = $this->settings['attempts_to_reset'] - $examErrorsCount;

        $paidAttemptsRemain = $sumPackages - $sumAttempts;

        return $this->render('@App/My/attempts_package_stat.html.twig', [
            'packages'                 => $boughtPackages,
            'sum_packages'             => $sumPackages,
            'attempts'                 => $usedAttempts,
            'sum_attempts'             => $sumAttempts,
            'attempts_to_reset_remain' => $attemptsToResetRemain,
            'paid_attempts_remain'     => $paidAttemptsRemain,
        ]);
    }
}
