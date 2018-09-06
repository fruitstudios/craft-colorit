<?php
namespace fruitstudios\palette\controllers;

use fruitstudios\palette\Palette;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class PresetsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionPresetIndex(): Response
    {
        $settings = Palette::$settings;

        return $this->renderTemplate('palette/settings/presets/index', [
            'presets' => [],
        ]);
    }

    public function actionEditPreset(): Response
    {
        $settings = Palette::$settings;

        return $this->renderTemplate('palette/settings/presets/_edit', [
            'preset' => false,
        ]);
    }
}


    // /**
    //  * @param int|null $planId
    //  * @param Plan|null $plan
    //  * @return Response
    //  * @throws HttpException
    //  */
    // public function actionEditPlan(int $planId = null, Plan $plan = null): Response
    // {
    //     $variables = [
    //         'planId' => $planId,
    //         'plan' => $plan,
    //     ];
    //     $variables['brandNewPlan'] = false;
    //     if (empty($variables['plan'])) {
    //         if (!empty($variables['planId'])) {
    //             $planId = $variables['planId'];
    //             try {
    //                 $variables['plan'] = Plugin::getInstance()->getPlans()->getPlanById($planId);
    //             } catch (InvalidConfigException $exception) {
    //                 throw new HttpException(404);
    //             }
    //             if (!$variables['plan']) {
    //                 throw new HttpException(404);
    //             }
    //         } else {
    //             $variables['brandNewPlan'] = true;
    //         }
    //     }
    //     if (!empty($variables['planId'])) {
    //         $variables['title'] = $variables['plan']->name;
    //     } else {
    //         $variables['title'] = Craft::t('commerce', 'Create a Subscription Plan');
    //     }
    //     $variables['entryElementType'] = Entry::class;
    //     $gateways = Plugin::getInstance()->getGateways()->getAllSubscriptionGateways();
    //     $variables['supportedGateways'] = $gateways;
    //     $variables['gatewayOptions'] = [['value' => '', 'label' => '-']];
    //     foreach ($gateways as $gateway) {
    //         $variables['gatewayOptions'][] = ['value' => $gateway->id, 'label' => $gateway->name];
    //     }
    //     return $this->renderTemplate('commerce/settings/subscriptions/_editPlan', $variables);
    // }
    // /**
    //  * @throws Exception
    //  * @throws HttpException if request does not match requirements
    //  * @throws InvalidConfigException if gateway does not support subscriptions
    //  * @throws \yii\web\BadRequestHttpException
    //  */
    // public function actionSavePlan()
    // {
    //     $request = Craft::$app->getRequest();
    //     $this->requirePostRequest();
    //     $gatewayId = $request->getBodyParam('gatewayId');
    //     $reference = $request->getBodyParam("gateway.{$gatewayId}.reference", '');
    //     $gateway = Plugin::getInstance()->getGateways()->getGatewayById($gatewayId);
    //     if ($gateway instanceof SubscriptionGateway) {
    //         $planData = $gateway->getSubscriptionPlanByReference($reference);
    //     } else {
    //         throw new InvalidConfigException('This gateway does not support subscription plans.');
    //     }
    //     $planInformationIds = $request->getBodyParam('planInformation');
    //     $plan = $gateway->getPlanModel();
    //     // Shared attributes
    //     $plan->id = $request->getParam('planId');
    //     $plan->gatewayId = $gatewayId;
    //     $plan->name = $request->getParam('name');
    //     $plan->handle = $request->getParam('handle');
    //     $plan->planInformationId = \is_array($planInformationIds) ? reset($planInformationIds) : null;
    //     $plan->reference = $reference;
    //     $plan->enabled = (bool)$request->getParam('enabled');
    //     $plan->planData = $planData;
    //     $plan->isArchived = false;
    //     // Save $plan
    //     if (Plugin::getInstance()->getPlans()->savePlan($plan)) {
    //         Craft::$app->getSession()->setNotice(Craft::t('commerce', 'Subscription plan saved.'));
    //         $this->redirectToPostedUrl($plan);
    //     } else {
    //         Craft::$app->getSession()->setError(Craft::t('commerce', 'Couldn’t save subscription plan.'));
    //     }
    //     // Send the productType back to the template
    //     Craft::$app->getUrlManager()->setRouteParams([
    //         'plan' => $plan
    //     ]);
    // }
    // /**
    //  * @return Response
    //  * @throws HttpException if request does not match requirements
    //  */
    // public function actionArchivePlan(): Response
    // {
    //     $this->requirePostRequest();
    //     $this->requireAcceptsJson();
    //     $planId = Craft::$app->getRequest()->getRequiredBodyParam('id');
    //     try {
    //         Plugin::getInstance()->getPlans()->archivePlanById($planId);
    //     } catch (Exception $exception) {
    //         return $this->asErrorJson($exception->getMessage());
    //     }
    //     return $this->asJson(['success' => true]);
    // }