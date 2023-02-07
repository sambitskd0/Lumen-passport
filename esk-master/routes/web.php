<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// $router->get('/', function () use ($router) {
//     return $router->app->version();
// });
$router->get('/key', function () {
    return \Illuminate\Support\Str::random(32);
});
/* Manage District Routes :: Swagatika :: 11-05-2022 */
$router->get("/testSambit", function () {
    return 1;
});

$router->post("/getDistrictById", "DistrictController@getDistrictById");
$router->post("/addDistrict", "DistrictController@addDistrict");
$router->post("/updateDistrict", "DistrictController@updateDistrict");
$router->post("/deleteDistrict", "DistrictController@deleteDistrict");
$router->post("/viewDistrict", "DistrictController@viewDistrict");

/* Manage Block Routes :: Saubhagya :: 12-05-2022 */
$router->post("/getBlockById", "BlockController@getBlockById");
$router->post("/addBlock", "BlockController@addBlock");
$router->post("/updateBlock", "BlockController@updateBlock");
$router->post("/deleteBlock", "BlockController@deleteBlock");
$router->post("/viewBlock", "BlockController@viewBlock");

/* Manage Cluster Routes :: Saubhagya :: 13-05-2022 */
$router->post("/getClusterById", "ClusterController@getClusterById");
$router->post("/addCluster", "ClusterController@addCluster");
$router->post("/updateCluster", "ClusterController@updateCluster");
$router->post("/deleteCluster", "ClusterController@deleteCluster");
$router->post("/viewCluster", "ClusterController@viewCluster");

/* Manage Nagar Nigam Routes :: Swagatika :: 13-05-2022 */
$router->post("/getNagarnigam", "NagarNigamController@getNagarnigam");
$router->post("/addNagarnigam", "NagarNigamController@addNagarnigam");
$router->post("/updateNagarnigam", "NagarNigamController@updateNagarnigam");
$router->post("/deleteNagarnigam", "NagarNigamController@deleteNagarnigam");
$router->post("/viewNagarnigam", "NagarNigamController@viewNagarnigam");

/* Manage Village Routes :: Manoj :: 13-05-2022 */
$router->post("/getVillage", "VillageController@getVillage");
$router->post("/addVillage", "VillageController@addVillage");
$router->post("/updateVillage", "VillageController@updateVillage");
$router->post("/deleteVillage", "VillageController@deleteVillage");
$router->post("/viewVillage", "VillageController@viewVillage");

/* Manage Appoint SUbject Routes :: Swagatika :: 16-05-2022 */
$router->post("/getAppointSubject", "AppointSubjectController@getAppointSubject");
$router->post("/addAppointSubject", "AppointSubjectController@addAppointSubject");
$router->post("/updateAppointSubject", "AppointSubjectController@updateAppointSubject");
$router->post("/deleteAppointSubject", "AppointSubjectController@deleteAppointSubject");
$router->post("/viewAppointSubject", "AppointSubjectController@viewAppointSubject");

/* Manage Annexture Routes :: Swagatika :: 18-05-2022 */
$router->post("/getAnnexture", "AnnextureController@getAnnexture");

// common function for getting all type of annexture details by :: Sambit Kumar Dalai :: 14-06-2022 */
$router->post("/getCommonAnnexture", "AnnextureController@getCommonAnnexture");



/* Manage Feedback Category Routes :: Manoj Kumar Baliarsingh :: 16-05-2022 */
$router->post("/addFeedbackCategory", "FeedbackCategoryController@addFeedbackCategory");
$router->post("/getFeedbackCategory", "FeedbackCategoryController@getFeedbackCategory");
$router->post("/updateFeedbackCategory", "FeedbackCategoryController@updateFeedbackCategory");
$router->post("/deleteFeedbackCategory", "FeedbackCategoryController@deleteFeedbackCategory");
$router->post("/viewFeedbackCategory", "FeedbackCategoryController@viewFeedbackCategory");

// Start: Nitish Nanda on 20-05-2022
// manage Subject
$router->post("/addSubject", "SubjectController@addSubjectCategory");
$router->post("/viewSubject", "SubjectController@viewSubjectCategory");
$router->post("/deleteSubject", "SubjectController@deleteSubjectCategory");
$router->post("/showSubject", "SubjectController@getSubjectCategory");
$router->post("/updateSubject", "SubjectController@updateSubjectCategory");
// End: By Nitish Nanda on 20-05-2022



// Start: Nitish Nanda on 23-05-2022
// manage Device Information
$router->post("/addDeviceInfo", "DeviceInfoController@addDeviceInfo");
$router->post("/viewDeviceInfo", "DeviceInfoController@viewDeviceInfo");
$router->post("/deleteDeviceInfo", "DeviceInfoController@deleteDevice");
$router->post("/getDeviceInfo", "DeviceInfoController@getDeviceInfo");
$router->post("/updateDeviceInfo", "DeviceInfoController@updateDeviceInfo");
$router->post("/getTeacherAccordingToSchool", "DeviceInfoController@getTeacherAccordingToSchool");
// End: By Nitish Nanda on 26-05-2022
// Start: By Manoj Kumar Baliarsingh on 19-05-2022
// manage AssetCategory 
$router->post("/addAssetCategory", "AssetCategoryController@addAssetCategory");
$router->post("/getAssetCategory", "AssetCategoryController@getAssetCategory");
$router->post("/updateAssetCategory", "AssetCategoryController@updateAssetCategory");
$router->post("/deleteAssetCategory", "AssetCategoryController@deleteAssetCategory");
$router->post("/viewAssetCategory", "AssetCategoryController@viewAssetCategory");
// End: By Manoj Kumar Baliarsingh on 19-05-2022

// Start: By Manoj Kumar Baliarsingh on 23-05-2022
// manage AssetItem 
$router->post("/addAssetItem", "AssetItemController@addAssetItem");
$router->post("/getAssetItem", "AssetItemController@getAssetItem");
$router->post("/updateAssetItem", "AssetItemController@updateAssetItem");
$router->post("/deleteAssetItem", "AssetItemController@deleteAssetItem");
$router->post("/viewAssetItem", "AssetItemController@viewAssetItem");
// End: By Manoj Kumar Baliarsingh on 23-05-2022

//INCENTIVE MODULE
// Start: By Manoj Kumar Baliarsingh on 31-05-2022
$router->post("/addIncentiveData", "IncentiveController@addIncentiveData");
$router->get("/getIncentiveData[/{id}]", "IncentiveController@getIncentiveData");
$router->post("/updateIncentiveData", "IncentiveController@updateIncentiveData");
$router->post("/deleteIncentiveData", "IncentiveController@deleteIncentiveData");
$router->post("/viewIncentiveData", "IncentiveController@viewIncentiveData");
// End: By Manoj Kumar Baliarsingh on 01-06-2022

// Start: Nitish Nanda on 27-05-2022
// manage GeoFencing Information
$router->post("/getGeoFencing", "GeoFencingController@getGeoFencing");
$router->post("/updateGeoFencing", "GeoFencingController@updateGeoFencing");
// End: Nitish Nanda on 01-06-2022


// Start: Nitish Nanda on 01-06-2022
// manage Subject Tagging
$router->post("/addSubjectTagging", "SubjectTaggingController@addSubjectTagging");
$router->post("/viewSubjectTagging", "SubjectTaggingController@viewSubjectTagging");
$router->post("/getSubjectTagging", "SubjectTaggingController@getSubjectTagging");
$router->post("/updateSubjectTagging", "SubjectTaggingController@updateSubjectTagging");
$router->post("/getSubjectName", "SubjectTaggingController@getSubjectName");
$router->post("/getSubject", "SubjectTaggingController@getSubject");
$router->post("/getSubjectAccordingToClass", "SubjectTaggingController@getSubjectAccordingToClass");
// End: Nitish Nanda on 03-06-2022



//INCENTIVE MODULE
// Start: By Manoj Kumar Baliarsingh on 07-06-2022
$router->post("/addIncentiveConfigData", "incentiveConfigController@addIncentiveConfigData");
$router->get("/getIncentiveConfigData[/{id}]", "incentiveConfigController@getIncentiveConfigData");
$router->post("/updateIncentiveConfigData", "incentiveConfigController@updateIncentiveConfigData");
$router->post("/deleteIncentiveConfigData", "incentiveConfigController@deleteIncentiveConfigData");
$router->post("/viewIncentiveConfigData", "incentiveConfigController@viewIncentiveConfigData");
$router->get("/getIncentiveName", "incentiveConfigController@getIncentiveName");
// End: By Manoj Kumar Baliarsingh on 07-06-2022

// Start: Nitish Nanda on 04-06-2022
// manage Shift Master

$router->post("/addShiftMaster", "ShiftMasterController@addShiftMaster");
$router->post("/viewShiftMaster", "ShiftMasterController@viewShiftMaster");
$router->post("/getShiftMaster", "ShiftMasterController@getShiftMaster");
$router->post("/updateShiftMaster", "ShiftMasterController@updateShiftMaster");
$router->post("/deleteShiftMaster", "ShiftMasterController@deleteShiftMaster");
// End: Nitish Nanda on 06-06-2022

// Start: Nitish Nanda on 07-06-2022
// manage Event Type
$router->post("/addEventType", "EventTypeController@addEventType");
$router->post("/viewEventType", "EventTypeController@viewEventType");
$router->post("/deleteEventType", "EventTypeController@deleteEventType");
$router->post("/getEventType", "EventTypeController@getEventType");
$router->post("/updateEventType", "EventTypeController@updateEventType");
// End: Nitish Nanda on 07-06-2022

// Start: Nitish Nanda on 08-06-2022
// manage Event Category
$router->post("/getEvent", "EventCategoryController@getEvent");
$router->post("/addEventCategory", "EventCategoryController@addEventCategory");
$router->post("/viewEventCategory", "EventCategoryController@viewEventCategory");
$router->post("/getEventCategory", "EventCategoryController@getEventCategory");
$router->post("/deleteEventCategory", "EventCategoryController@deleteEventCategory");
$router->post("/updateEventCategory", "EventCategoryController@updateEventCategory");
// End: Nitish Nanda on 12-06-2022

// Start: Nitish Nanda on 13-06-2022
// manage Event Master
$router->post("/getEventName", "EventMasterController@getEventName");
$router->post("/eventName", "EventMasterController@eventName");
$router->post("/getEventCategoryName", "EventMasterController@getEventCategoryName");
$router->post("/addEventMaster", "EventMasterController@addEventMaster");
$router->post("/viewEventMaster", "EventMasterController@viewEventMaster");
$router->post("/deleteEventMaster", "EventMasterController@deleteEventMaster");
$router->post("/getEventMaster", "EventMasterController@getEventMaster");
$router->post("/updateEventMaster", "EventMasterController@updateEventMaster");
// End: Nitish Nanda on 14-06-2022


// Start: Nitish Nanda on 16-06-2022
// manage StudentGrade Master
$router->post("/viewStudentGradeMaster", "StudentGradeController@viewStudentGradeMaster");
// End: Nitish Nanda on 16-06-2022

// Start: Nitish Nanda on 22-06-2022
// manage Examination Master
$router->get("/getClassName", "ExaminationMasterController@getClassName");
$router->post("/addExaminationMaster", "ExaminationMasterController@addExaminationMaster");
$router->post("/viewExaminationMaster", "ExaminationMasterController@viewExaminationMaster");
$router->post("/deleteExaminationMaster", "ExaminationMasterController@deleteExaminationMaster");
$router->get("/getExaminationMaster[/{id}]", "ExaminationMasterController@getExaminationMaster");
$router->post("/updateExaminationMaster", "ExaminationMasterController@updateExaminationMaster");
$router->post("/getClassAccordingToExamType", "ExaminationMasterController@getClassAccordingToExamType");
// End: Nitish Nanda on 23-06-2022

// Start: Nitish Nanda on 24-06-2022
// manage MarkConfiguration
$router->post("/getClassByTermId", "MarkConfigurationController@getClassByTermId");
$router->post("/addMarkConfiguration", "MarkConfigurationController@addMarkConfiguration");
$router->post("/getSubjectForMarkConfiguration", "MarkConfigurationController@getSubjectForMarkConfiguration");

/* View attachment ::Nitish Nanda :: 22-08-2022 */
$router->get('/getFile/{filepath:[a-zA-Z0-9-_\/.]+}~{extension:[a-zA-Z0-9]{3,4}+}', 'CommonMasterController@getAfile');

/* Manage Notification Category Routes :: Manoj Kumar Baliarsingh :: 22-08-2022 */
$router->post("/addNotificationCategory", "NotificationCategoryController@addNotificationCategory");
$router->post("/getNotificationCategoryData", "NotificationCategoryController@getNotificationCategoryData");
$router->post("/updateNotificationCategory", "NotificationCategoryController@updateNotificationCategory");
// $router->post("/deleteNotificationCategory", "NotificationCategoryController@deleteNotificationCategory");
$router->post("/viewNotificationCategory", "NotificationCategoryController@viewNotificationCategory");

/* Manage Notification Component Routes :: Manoj Kumar Baliarsingh :: 22-08-2022 */
$router->post("/addNotificationComponent", "NotificationComponentController@addNotificationComponent");
$router->post("/getNotificationComponentData", "NotificationComponentController@getNotificationComponentData");
$router->post("/updateNotificationComponent", "NotificationComponentController@updateNotificationComponent");
// $router->post("/deleteNotificationComponent", "NotificationComponentController@deleteNotificationComponent");
$router->post("/viewNotificationComponent", "NotificationComponentController@viewNotificationComponent");
$router->post("/getNotificationCategoryName", "NotificationComponentController@getNotificationCategoryName");


/* Common Service Routes :: Deepti Ranajn :: 01-09-2022 */
$router->post("/getDistrict", "CommonServiceController@getDistrict");
$router->post("/getBlock", "CommonServiceController@getBlock");
$router->post("/getCluster", "CommonServiceController@getCluster");

// $router->get('/', function () use ($router) {
//     return $router->app->version();
// });

$router->group(
    [
        'middleware' => 'auth',
    ],
    function ($router) {
        $router->get('/', function () use ($router) {
            return $router->app->version();
        });
    }
);
    