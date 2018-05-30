<?php
require_once dirname(__DIR__) . '/set_paths_for_bin_scripts.php';

$config = require_once BASE_DIR . '/public/config.inc';

include CONFIG_SYS . "/loader.php";
include CONFIG_SYS . "/services.php";

$catalogConfig = require CONFIG_DIR . '/catalog.def';
$service = $catalogConfig['msad']['service']();
$service->setCacheLifetime(0);

$superInvigilators = OpenExam\Models\SuperInvigilator::find();
foreach($superInvigilators as $superInvigilator) {
  $superInvigilator->delete();
}

foreach ($service->getMembers('AKKA - AF8_24') as $member) {
  $superInvigilator = new OpenExam\Models\SuperInvigilator();
  $superInvigilator->user = $member->attr['userprincipalname'][0];
  $superInvigilator->save();
}
